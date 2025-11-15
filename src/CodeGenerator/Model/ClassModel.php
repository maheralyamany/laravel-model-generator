<?php

namespace MaherAlyamany\ModelGenerator\CodeGenerator\Model;


use MaherAlyamany\ModelGenerator\CodeGenerator\Exception\GeneratorException;
use MaherAlyamany\ModelGenerator\CodeGenerator\Model\Traits\DocBlockTrait;
use MaherAlyamany\ModelGenerator\CodeGenerator\RenderableModel;
use MaherAlyamany\ModelGenerator\CodeGenerator\Model\TableColumn;
use MaherAlyamany\ModelGenerator\Model\Relation;
use MaherAlyamany\ModelGenerator\Schema\MDbManager;

/**
 * Class ClassModel
 * @package MaherAlyamany\ModelGenerator\CodeGenerator\Model
 */
class ClassModel extends RenderableModel
{
    use DocBlockTrait;
    protected $createOrUpdate = [];
    protected $relationsClass = [];
    protected string $database;

    /**
     * @var Relation[]
     */
    protected $cascadeRelations = [];
    /**
     * Summary of tableColumns
     * @var TableColumn[]
     */
    protected $tableColumns = [];
    protected $primaryKeys = [];
    /**
     * @var ClassNameModel
     */

    protected $name;

    /**
     * @var NamespaceModel
     */
    protected $namespace;

    /**
     * @var UseClassModel[]
     */
    protected $uses = [];

    /**
     * @var UseTraitModel[]
     */
    protected $traits = [];

    /**
     * @var ConstantModel[]
     */
    protected $constants = [];

    /**
     * @var BasePropertyModel[]
     */
    protected $properties = [];

    /**
     * @var BaseMethodModel[]
     */
    protected $methods = [];

    /**
     * {@inheritDoc}
     */
    public function toLines()
    {
        $lines = [];
        $lines[] = $this->ln('<?php');
        if ($this->namespace !== null) {
            $lines[] = $this->ln($this->namespace->render());
        }
        if (count($this->uses) > 0) {
            $lines[] = $this->renderArrayLn($this->uses);
        }
        $this->prepareDocBlock();
        if ($this->docBlock !== null) {
            $lines[] = $this->docBlock->render();
        }
        $lines[] = $this->name->render();
        if (count($this->traits) > 0) {
            $lines[] = $this->renderArrayLn($this->traits, 4);
        }
        if (count($this->constants) > 0) {
            $lines[] = $this->renderArrayLn($this->constants, 4);
        }
        $this->processProperties($lines);
        $this->processMethods($lines);
        /**
         * Fix the bug with empty line before closing bracket
         */
        $lines[count($lines) - 1] = rtrim($lines[count($lines) - 1]);
        $lines[] = $this->ln('}');

        return $lines;
    }

    /**
     * @return ClassNameModel
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param ClassNameModel $name
     *
     * @return $this
     */
    public function setName(ClassNameModel $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return NamespaceModel
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param NamespaceModel $namespace
     *
     * @return $this
     */
    public function setNamespace(NamespaceModel $namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @return UseClassModel[]
     */
    public function getUses()
    {
        return $this->uses;
    }

    /**
     * @param UseClassModel $use
     *
     * @return self
     */
    public function addUses(UseClassModel $use): self
    {
        $this->uses[] = $use;

        return $this;
    }

    /**
     * @return UseTraitModel[]
     */
    public function getTraits()
    {
        return $this->traits;
    }

    /**
     * @param UseTraitModel
     *
     * @return $this
     */
    public function addTrait(UseTraitModel $trait)
    {
        $this->traits[] = $trait;

        return $this;
    }

    /**
     * @return ConstantModel[]
     */
    public function getConstants()
    {
        return $this->constants;
    }

    /**
     * @param ConstantModel $constant
     *
     * @return $this
     */
    public function addConstant(ConstantModel $constant)
    {
        $this->constants[] = $constant;

        return $this;
    }

    /**
     * @return BasePropertyModel[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param BasePropertyModel $property
     *
     * @return $this
     */
    public function addProperty(BasePropertyModel $property)
    {
        if (!($property instanceof VirtualPropertyModel)) {
            $this->properties[] = $property;
        }

        return $this;
    }
    /**
     * @param BasePropertyModel $property
     *
     * @return $this
     */
    public function addOrReplaceProperty(BasePropertyModel $property)
    {
        if (!($property instanceof VirtualPropertyModel)) {
            $oldIndex = -1;
            for ($i = 0; $i < count($this->properties); $i++) {
                $p = $this->properties[$i];

                if ($p->getName() === $property->getName()) {
                    $oldIndex = $i;
                    break;
                }
            }
            if ($oldIndex < 0)
                $this->properties[] = $property;
            else
                $this->properties[$oldIndex] = $property;
        }

        return $this;
    }

    /**
     * @return BaseMethodModel[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param BaseMethodModel
     *
     * @return $this
     */
    public function addMethod(BaseMethodModel $method)
    {
        $this->methods[] = $method;

        return $this;
    }

    /**
     * Convert virtual properties and methods to DocBlock content
     */
    protected function prepareDocBlock()
    {
        $content = [];

        foreach ($this->properties as $property) {
            if ($property instanceof VirtualPropertyModel) {
                $content[] = $property->toLines();
            }
        }

        foreach ($this->methods as $method) {
            if ($method instanceof VirtualMethodModel) {
                $content[] = $method->toLines();
            }
        }

        if ($content) {
            if ($this->docBlock === null) {
                $this->docBlock = new DocBlockModel();
            }

            $this->docBlock->addContent($content);
        }
    }

    /**
     * @param array $lines
     */
    protected function processProperties(&$lines)
    {
        $properties = array_filter($this->properties, function ($property) {
            return !$property instanceof VirtualPropertyModel;
        });
        if (count($properties) > 0) {
            $lines[] = $this->renderArrayLn($properties, 4, str_repeat(PHP_EOL, 2));
        }
    }

    /**
     * @param array $lines
     * @throws GeneratorException
     */
    protected function processMethods(&$lines)
    {
        $methods = array_filter($this->methods, function ($method) {
            return !$method instanceof VirtualMethodModel;
        });
        if (count($methods) > 0) {
            $lines[] = $this->renderArray($methods, 4, str_repeat(PHP_EOL, 2));
        }
    }

    /**
     * @return mixed
     */
    public function getCreateOrUpdate()
    {
        return $this->createOrUpdate;
    }

    /**
     * @param mixed $createOrUpdate
     * @return self
     */
    public function setCreateOrUpdate($createOrUpdate): self
    {
        $this->createOrUpdate = $createOrUpdate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRelationsClass()
    {
        return $this->relationsClass;
    }

    /**

     * @param mixed $relationName
     * @param mixed $relationClass
     * @return self
     */
    public function addRelationClass($relationName, $relationClass): self
    {
        $this->relationsClass[$relationName] = $relationClass;
        return $this;
    }

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @param string $database
     * @return self
     */
    public function setDatabase(string $database): self
    {
        $this->database = $database;
        return $this;
    }
    /**
     *
     * @return Relation[]
     */
    public function getRelations()
    {
        return $this->cascadeRelations;
    }
    /**
     *
     * @return Relation[]
     */
    public function getBelongsToManyRelations()
    {
        return collect($this->cascadeRelations)->filter(fn($r) => ($r::class === 'MaherAlyamany\ModelGenerator\Model\BelongsToMany'))->toArray();
    }
    /**
     *
     * @return Relation|null
     */
    public function getCascadeAndBelongsToRelation()
    {
        $relarions = collect($this->cascadeRelations)->filter(fn(Relation $r) => ($r::class === 'MaherAlyamany\ModelGenerator\Model\BelongsTo' && $r->getIsCascade()));
        if ($relarions->count() == 1)
            return $relarions->first();
        return null;
    }
    /**
     *
     * @param Relation[] $relations
     * @return self
     */
    public function setRelations($relations): self
    {
        $this->cascadeRelations = $relations;
        return $this;
    }
    /**
     *
     * @param Relation  $relation
     * @return self
     */
    public function addCascadeRelation($relation): self
    {
        $instance = MDbManager::getSchema();
        $columns = $instance->getListTableColumns($relation->getTableName(), $this->getDatabase());
        $relation->setColumns($columns);
        $this->cascadeRelations[] = $relation;
        return $this;
    }


    /**
     * @return TableColumn[]
     */
    public function getTableColumns()
    {
        return $this->tableColumns;
    }
    public function isKeysEqualsColumns()
    {
        $cols = count($this->getTableColumns());
        return ($cols == 0) || $cols === count($this->getPrimaryKeyColumns());
    }
    public function hasColumn($columnName): bool
    {
        return  collect($this->tableColumns)->filter(fn($col) => ($col->Field == $columnName))->count() > 0;
    }
    public function getPrimaryKeyColumns()
    {
        if (count($this->primaryKeys) == 0) {
            if (count($this->tableColumns) > 0) {
                $primaryKeys = [];
                collect($this->tableColumns)->each(function ($column, $k) use (&$primaryKeys) {
                    if ($column->Key === "PRI")
                        $primaryKeys[] = $column;
                });
                $this->primaryKeys = $primaryKeys;
            }
        }
        return $this->primaryKeys;
    }
    public function getPrimaryKeyColumn()
    {
        return collect($this->getPrimaryKeyColumns())->first();
    }

    /**
     * @param TableColumn[] $tableColumns
     * @return self
     */
    public function setTableColumns($tableColumns): self
    {
        $this->tableColumns = $tableColumns;
        $this->getPrimaryKeyColumns();
        return $this;
    }
}
