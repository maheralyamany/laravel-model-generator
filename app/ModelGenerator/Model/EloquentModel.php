<?php

namespace App\ModelGenerator\Model;

use App\CodeGenerator\Model\ClassModel;
use App\CodeGenerator\Model\DocBlockModel;
use App\CodeGenerator\Model\MethodModel;
use App\CodeGenerator\Model\VirtualPropertyModel;
use App\ModelGenerator\Exception\GeneratorException;
use App\ModelGenerator\Helper\EmgHelper;
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Database\Eloquent\Relations\HasOne as EloquentHasOne;
use Illuminate\Support\Str;

class EloquentModel extends ClassModel
{
    protected string $tableName;


    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function addRelation(Relation $relation, array $moveRelations, bool $isCascade): array
    {

        $relation->setIsCascade($isCascade);
        $methodName = $relation->initMethodName();
        $relationClass = $relation->getRelatedClass();
        if ($relation instanceof HasOne) {
            $name = Str::singular(Str::camel($methodName));
            $docBlock = sprintf('@return \%s', EloquentHasOne::class);
            if (!$isCascade) {
                $moveRelations[] = $name;
            }

            $virtualPropertyType = $relationClass;
        } elseif ($relation instanceof HasMany) {
            $name = Str::plural(Str::camel($methodName));
            $docBlock = sprintf('@return \%s', EloquentHasMany::class);
            if (!$isCascade) {
                $moveRelations[] = $name;
            }

            $virtualPropertyType = sprintf('%s[]', $relationClass);
        } elseif ($relation instanceof BelongsTo) {
            $name = Str::singular(Str::camel($methodName));
            $docBlock = sprintf('@return \%s', EloquentBelongsTo::class);

            $virtualPropertyType = $relationClass;
        } elseif ($relation instanceof BelongsToMany) {
            $name = Str::plural(Str::camel($methodName));
            $docBlock = sprintf('@return \%s', EloquentBelongsToMany::class);
            if (!$isCascade) {
                $moveRelations[] = $name;
            }

            $virtualPropertyType = sprintf('%s[]', $relationClass);
        } else {
            throw new GeneratorException('Relation not supported');
        }

        $relation->setMethodName($name);
        $this->addCascadeRelation($relation);
        $this->addRelationClass($name, $relationClass);
        $method = new MethodModel($name);
        $method->setBody($this->createRelationMethodBody($relation));
        $method->setDocBlock(new DocBlockModel($docBlock));

        $this->addMethod($method);
        $this->addProperty(new VirtualPropertyModel($name, $virtualPropertyType));
        return $moveRelations;
    }

    protected function createRelationMethodBody(Relation $relation): string
    {
        $reflectionObject = new \ReflectionObject($relation);
        $name = Str::camel($reflectionObject->getShortName());
        $refName = /* $this->getNamespace()->getNamespace() . '\\' .  */ EmgHelper::getClassNameByTableName($relation->getTableName()) . '::class';
        $arguments = [];

        if ($relation instanceof BelongsToMany) {
            $defaultJoinTableName = EmgHelper::getDefaultJoinTableName(
                $this->getTableName(),
                $relation->getTableName()
            );
            $joinTableName = $relation->getJoinTable() === $defaultJoinTableName
                ? null
                : EmgHelper::getClassNameByTableName($relation->getJoinTable()) . '::class';
            $arguments[] = $joinTableName;

            $arguments[] = $this->resolveArgument(
                $relation->getForeignColumnName(),
                EmgHelper::getDefaultForeignColumnName($this->getTableName())
            );
            $arguments[] = $this->resolveArgument(
                $relation->getLocalColumnName(),
                EmgHelper::getDefaultForeignColumnName($relation->getTableName())
            );
            /*  dd([$relation->getLocalColumnName(), EmgHelper::getDefaultForeignColumnName($relation->getTableName())]); */
        } elseif ($relation instanceof HasMany) {
            $arguments[] = $this->resolveArgument(
                $relation->getForeignColumnName(),
                EmgHelper::getDefaultForeignColumnName($this->getTableName())
            );
            $arguments[] = $this->resolveArgument(
                $relation->getLocalColumnName(),
                EmgHelper::DEFAULT_PRIMARY_KEY
            );
        } else {
            $arguments[] = $this->resolveArgument(
                $relation->getForeignColumnName(),
                EmgHelper::getDefaultForeignColumnName($relation->getTableName())
            );
            $arguments[] = $this->resolveArgument(
                $relation->getLocalColumnName(),
                EmgHelper::DEFAULT_PRIMARY_KEY
            );
        }

        return sprintf('return $this->%s(%s,%s)->withoutGlobalScopes();', $name, $refName, $this->createRelationMethodArguments($arguments));
    }

    protected function createRelationMethodArguments(array $array): string
    {
        $array = array_reverse($array);
        $milestone = false;
        foreach ($array as $key => &$item) {
            if (!$milestone) {
                if (!is_string($item)) {
                    unset($array[$key]);
                } else {
                    $milestone = true;
                }
            } else {
                if ($item === null) {
                    $item = 'null';

                    continue;
                }
            }
            if (str_ends_with($item, '::class')) {
                $item = sprintf("%s", $item);
            } else {
                $item = sprintf("'%s'", $item);
            }
        }

        return implode(', ', array_reverse($array));
    }

    protected function resolveArgument(string $actual, string $default): ?string
    {
        // return $actual === $default ? null : $actual;
        return $actual === EmgHelper::DEFAULT_PRIMARY_KEY ? null : $actual;
    }
}
