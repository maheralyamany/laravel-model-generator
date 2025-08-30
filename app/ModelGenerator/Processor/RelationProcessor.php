<?php

namespace App\ModelGenerator\Processor;

use App\CodeGenerator\Model\ArgumentModel;
use App\CodeGenerator\Model\BaseMethodModel;
use App\CodeGenerator\Model\DocBlockModel;
use App\CodeGenerator\Model\MethodModel;
use App\CodeGenerator\Model\PropertyModel;
use App\CodeGenerator\Model\UseClassModel;
use App\ModelGenerator\Command\TablesNamespacesTrait;
use App\ModelGenerator\Config\MConfig;
use App\ModelGenerator\Helper\EmgHelper;
use App\ModelGenerator\Helper\MFormatter;
use App\ModelGenerator\Helper\Prefix;
use App\ModelGenerator\Model\BelongsTo;
use App\ModelGenerator\Model\BelongsToMany;
use App\ModelGenerator\Model\EloquentModel;
use App\ModelGenerator\Model\HasMany;
use App\ModelGenerator\Model\HasOne;
use App\ModelGenerator\Model\Relation;
use Illuminate\Database\DatabaseManager;

class RelationProcessor implements ProcessorInterface
{
    use TablesNamespacesTrait;
    public function __construct(private DatabaseManager $databaseManager) {}
    public function process(EloquentModel $model, MConfig $config): void
    {
        $schemaManager = $this->databaseManager->connection($config->getConnection())->getDoctrineSchemaManager();
        $prefixedTableName = Prefix::add($model->getTableName());
        $tables = $schemaManager->listTables();
        $moveRelations = [];
        foreach ($tables as $table) {
            $foreignKeys = $schemaManager->listTableForeignKeys($table->getName());
            foreach ($foreignKeys as $name => $foreignKey) {
                $localColumns = $foreignKey->getLocalColumns();
                if (count($localColumns) !== 1) {
                    continue;
                }
                $isCascade = ($foreignKey->onDelete() == 'CASCADE');
                if ($table->getName() === $prefixedTableName) {
                    $relation = new BelongsTo(
                        Prefix::remove($foreignKey->getForeignTableName()),
                        $foreignKey->getLocalColumns()[0],
                        $foreignKey->getForeignColumns()[0]
                    );
                    $relation->setPrefix($config->getPrefix());
                    $this->addUses($model, $config, $relation->getTableName());
                    $moveRelations = $model->addRelation($relation, $moveRelations, $isCascade);
                } elseif ($foreignKey->getForeignTableName() === $prefixedTableName) {
                    if (count($foreignKeys) === 2 && count($table->getColumns()) === 2) {
                        $keys = array_keys($foreignKeys);
                        $key = array_search($name, $keys) === 0 ? 1 : 0;
                        $tableName = Prefix::remove($table->getName());
                        $foreignColumn = $localColumns[0];
                        $localColumn = $foreignKey->getForeignColumns()[0];
                        $mrelation = new HasMany($tableName, $foreignColumn, $localColumn);
                        $mrelation->setPrefix($config->getPrefix());
                        $this->addUses($model, $config, $tableName);
                        $moveRelations = $model->addRelation($mrelation, $moveRelations, $isCascade);

                        $secondForeignKey = $foreignKeys[$keys[$key]];
                        $localColumn =  $secondForeignKey->getLocalColumns()[0];
                        $secondForeignTable = Prefix::remove($secondForeignKey->getForeignTableName());
                        $relation = new BelongsToMany($secondForeignTable, $tableName, $foreignColumn, $localColumn);
                        $relation->setPrefix($config->getPrefix());
                        $this->addUses($model, $config, $secondForeignTable);
                        // dd($relation, $mrelation);
                        $moveRelations = $model->addRelation($relation, $moveRelations, $isCascade);
                        break;
                    } else {
                        $tableName = Prefix::remove($table->getName());
                        $foreignColumn = $localColumns[0];
                        $localColumn = $foreignKey->getForeignColumns()[0];
                        if (EmgHelper::isColumnUnique($table, $foreignColumn)) {
                            $relation = new HasOne($tableName, $foreignColumn, $localColumn);
                        } else {
                            $relation = new HasMany($tableName, $foreignColumn, $localColumn);
                        }
                        $relation->setPrefix($config->getPrefix());
                        $this->addUses($model, $config, $tableName);
                        $moveRelations = $model->addRelation($relation, $moveRelations, $isCascade);
                    }
                }
            }
        }

        //owner_id

        //dd($model->getRelations());
        $this->addOwnerIdMethod($model);
        $this->addMoveRelations($model, $moveRelations);
        if ($config->getHasCreateMethod() && !$model->isKeysEqualsColumns())
            $this->addCreateOrUpdateMethod($model);
    }
    public function addOwnerIdMethod(EloquentModel $model): EloquentModel
    {
        if ($model->hasColumn('owner_id')) {
            $method = new MethodModel("ownerId");
            $params = ['Interact with the  owner_id attribute.'];
            $params[] = '@return \Illuminate\Database\Eloquent\Casts\Attribute';
            $formatter = new MFormatter();
            $space = 3;
            $formatter->line('return \Illuminate\Database\Eloquent\Casts\Attribute::make(', $space);
            $formatter->line('get: fn(mixed $value) => parseIntId($value ?? 0),', $space + 1);
            $formatter->line('set: fn(mixed $value) =>  isEmptyOrZero($value) ? user_id() : $value', $space + 1);
            $formatter->line(');', $space);
            $stub =  $formatter->render();
            $method->setBody($stub);
            $method->setDocBlock(new DocBlockModel($params));
            $model->addMethod($method);
        }
        return $model;
    }
    public function addCreateOrUpdateMethod(EloquentModel $model): EloquentModel
    {
        //dd($model->getTableColumns());
        $primary = $model->getPrimaryKeyColumn();

        $relation = ($primary->Extra != 'auto_increment' && $primary->Field != 'id') ? collect($model->getRelations())->filter(function (Relation $relation) use ($primary) {
            return $relation->getForeignColumnName() == $primary->Field;
        }) : null;


        $cascadeRelation  = $model->getCascadeAndBelongsToRelation();
        $mstrColumnName = 'doc_mst_id';
        $mstrMethodName = 'docMstr';


        if ($cascadeRelation != null) {
            $mstrColumnName = $cascadeRelation->getForeignColumnName();
            $mstrMethodName = $cascadeRelation->getMethodName();
        }
        $hasRelation  = $relation != null;
        $hasMstr  = !$hasRelation && (key_exists($mstrMethodName, $model->getRelationsClass()) || $cascadeRelation != null);
        $hasDtls  = key_exists("details", $model->getRelationsClass());
        $isMstrM = (($hasDtls && !$hasRelation) || !($hasMstr || $hasRelation));
        $relations = (!($hasMstr || $hasRelation)) ? $model->getBelongsToManyRelations() : [];

        if ($hasMstr || $hasRelation) {
            $this->getDtlDynamicContent($model, $hasRelation, $mstrColumnName);
        } else
            $this->getDynamicContent($model);
        $dynamicContent = $model->getCreateOrUpdate();
        $formatter = new MFormatter();
        $space = 3;
        if ($isMstrM) {
            $formatter->line('$isUpdate=!is_null($item);');
            $formatter->line('try {');
            // $space += 1;
            $formatter->line('static::beginTransaction();', $space);
        }


        if ($hasMstr) {
            $formatter->line('$docDtls = getRequestDocInputsDtls($request);', $space - 1);
            $formatter->line('foreach ($docDtls as  $data) {', $space);

            if ($primary == null || ($primary != null && ($primary->Extra != 'auto_increment' || $primary->Field ==  $mstrColumnName))) {
                $formatter->line(sprintf("	\$item = static::new()->where('%s', \$mst->id)->first();", $mstrColumnName), $space + 1);
            } else
                $formatter->line(sprintf("	\$item = static::new()->where('%s', \$mst->id)->where('%s', \$data['%s'])->first();", $mstrColumnName, $primary->Field, $primary->Field), $space + 1);
        } elseif ($hasRelation) {
            $formatter->line(sprintf("	\$item = static::new()->where('%s', \$data['%s'])->first();", $primary->Field, $primary->Field), $space - 1);
        }
        $formatter->line("%%BaseVariables%%", $space);
        $formatter->line('if (is_null($item)) {', $space);
        $formatter->line("%%StoreInputs%%", $space);
        $formatter->line('} else {', $space);
        $formatter->line(" %%UpdateInputs%%", $space);
        $formatter->line('}', $space);
        if ($hasMstr) {
            $formatter->line('}', $space);
        } elseif ($hasDtls && !$hasRelation) {
            $dtlclass =  $model->getRelationsClass()["details"];
            $formatter->line('$res = ' . $dtlclass . '::createOrUpdate($request, $item);', $space);
            $formatter->line(" if (!\$res['status']){", $space);
            $formatter->line(" static::rollback();", $space + 1);
            $formatter->line(" return \$res;", $space + 1);
            $formatter->line(" }", $space);
        }
        if (count($relations) > 0) {
            collect($relations)->each(function (BelongsToMany $reltion) use (&$formatter, $space) {
                $localColumnName = $reltion->getLocalColumnName();
                $method = $reltion->getMethodName();
                $formatter->line(sprintf("if (checkRequestHas(\$request, '%s')) {", $localColumnName), $space);
                $formatter->line(sprintf("\$item->%s()->sync(\$request->get('%s'));", $method, $localColumnName), $space + 1);
                $formatter->line(" }", $space);
            });
        }
        if ($isMstrM) {
            $formatter->line('static::commitModel($item,$isUpdate);', $space);
        }
        $formatter->line('return ["status" => true];', $space);

        if ($isMstrM) {
            $formatter->line('} catch (\Exception $ex) {', $space - 1);
            $formatter->line('static::rollback();', $space);
            $formatter->line('return  static::getExeptionErrorArray($ex);', $space);
            $formatter->line('}', $space - 1);
        }
        $stub =  $formatter->render();
        $start = '%%';
        $end = '%%';
        foreach ($dynamicContent as $name => $vars) {
            $replace = $start . $name . $end;
            $stub = MFormatter::replace(' ', $replace, $vars, $stub);
        }
        if ($hasMstr) {
            $stub = str_replace('$request->' . $mstrColumnName, '$mst->id', $stub);
        }
        $method = new MethodModel("createOrUpdate");
        if (!$hasRelation)
            $method->addArgument(new ArgumentModel('request'));
        $param = '@param ';
        if ($hasMstr) {
            $mstclass =  $model->getRelationsClass()[$mstrMethodName];
            $param .= $mstclass . ' $mst';

            $method->addArgument(new ArgumentModel('mst'));
        } elseif ($hasRelation) {
            $method->addArgument(new ArgumentModel('data'));
            $param .= "array \$data";
        } else {
            $method->addArgument(new ArgumentModel('item', '', 'null'));
            $param .= "static \$item";
        }
        $method->setStatic(true);
        $method->setBody($stub);
        $params = ['create or update model'];
        if (!$hasRelation)
            $params[] = ' @param \Illuminate\Http\Request|object|\stdClass $request';
        $params[] =  $param;
        $params[] = '@return array';
        $method->setDocBlock(new DocBlockModel($params));
        $model->addMethod($method);



        return $model;
    }


    public function getDtlDynamicContent(EloquentModel $model, $hasRelation, $mstrColumnName): EloquentModel
    {
        $columns = $model->getTableColumns();
        if (sizeof($columns) > 0) {
            $storeInputs = new MFormatter();
            $baseVariables = new MFormatter();
            $updateInputs = new MFormatter();
            $storeInputs->line('$item = static::mCreate([', 1);
            foreach ($columns as $col) {
                if ($col->Extra != 'auto_increment' && $col->Field != 'deleted_at') {
                    if (($col->Field == 'doc_mst_id' || $col->Field == $mstrColumnName) || $col->Key == "PRI") {
                        if ($hasRelation) {
                            $updateInputs->line(sprintf("\$item->%s =\$data['%s'];", $col->Field, $col->Field), 1);
                            $storeInputs->line(sprintf("'%s' => \$data['%s'],", $col->Field, $col->Field), 2);
                        } else {
                            $updateInputs->line(sprintf("\$item->%s =\$mst->id;", $col->Field), 1);
                            $storeInputs->line(sprintf("'%s' => \$mst->id,", $col->Field), 2);
                        }
                    } else {

                        $def = static::getColumnDefaultValue($col);
                        $storeInputs->line(sprintf("'%s' => \$data['%s'] %s,", $col->Field, $col->Field, $def), 2);
                        $updateInputs->line(sprintf("\$item->%s = \$data['%s'] %s;", $col->Field, $col->Field, $def), 1);
                    }
                }
            }
            $storeInputs->line(']);', 1);
            $updateInputs->line('$item->save();', 1);
            $dynamicContent = [
                'StoreInputs' => $storeInputs->render(),
                'BaseVariables' => $baseVariables->render(),
                'UpdateInputs' => $updateInputs->render()
            ];
            $model->setCreateOrUpdate($dynamicContent);
        }
        return $model;
    }
    function array_group_by_key(array $array, string $key): array
    {

        $collect = collect($array);
        $grouped = $collect->mapToGroups(function ($col) use ($key) {
            $col = (array) $col;
            return [$col[$key] => $col];
        })->mapWithKeys(fn($x, $k) => [
            $k => (object) $x[0],
        ])->toArray();
        return $grouped;
    }
    function array_keys_exists(array $keys, array $arr)
    {
        return !array_diff_key(array_flip($keys), $arr);
    }
    public function getDynamicContent(EloquentModel $model): EloquentModel
    {
        $columns = $model->getTableColumns();
        if (count($columns) > 0) {
            $storeInputs = new MFormatter();
            $baseVariables = new MFormatter();

            $updateInputs = new MFormatter();
            $storeInputs->line('$item = static::Create([', 1);


            if (collect($columns)->filter(fn($col) => (in_array($col->Field, ['mixed_code', 'level_id'])))->count() == 2) {
                $columns = collect($columns)->filter(fn($col) => (!in_array($col->Field, ['mixed_code', 'level_id'])))->toArray();
                $baseVariables->line("\$mixed_code =getDyRequestMixedCode(\$request);", 1);

                $updateInputs->line("\$item->level_id = \$request->level_id;", 1);


                $storeInputs->line("'level_id' => \$request->level_id,", 2);
                $updateInputs->line("\$item->mixed_code =\$mixed_code;", 1);
                $storeInputs->line("'mixed_code' => \$mixed_code,", 2);
            }


            foreach ($columns as $col) {
                if ($col->Extra != 'auto_increment' && $col->Field != 'deleted_at') {
                    if ($col->Field == 'program_id') {
                        $baseVariables->line("\$program_id =getRequestProgramId(\$request);", 1);
                        $updateInputs->line(sprintf("\$item->%s =\$program_id;", $col->Field), 1);
                        $storeInputs->line(sprintf("'%s' => \$program_id,", $col->Field), 2);
                    } else if ($col->Field == 'mixed_code') {
                        $baseVariables->line("\$mixed_code =getDyRequestMixedCode(\$request);", 1);
                        $updateInputs->line(sprintf("\$item->%s =\$mixed_code;", $col->Field), 1);
                        $storeInputs->line(sprintf("'%s' => \$mixed_code,", $col->Field), 2);
                    } else {
                        $def = static::getColumnDefaultValue($col);

                        $storeInputs->line(sprintf("'%s' => \$request->%s %s,", $col->Field, $col->Field, $def), 2);
                        $updateInputs->line(sprintf("\$item->%s = \$request->%s %s;", $col->Field, $col->Field, $def), 1);
                    }
                }
            }
            $storeInputs->line(']);', 1);
            $updateInputs->line('$item->saveUpdate();', 1);
            $dynamicContent = [
                'StoreInputs' => $storeInputs->render(),
                'BaseVariables' => $baseVariables->render(),
                'UpdateInputs' => $updateInputs->render()
            ];
            $model->setCreateOrUpdate($dynamicContent);
        }
        return $model;
    }
    public static function getColumnDefaultValue($col): string
    {
        $def = '';
        if (($col->Default != null && $col->Default != 'NULL')) {
            $m = str_replace("'", '', $col->Default);

            if (str_contains($col->Default, '[]'))
                $def = sprintf(" ?? %s", $m);
            else
                $def = sprintf(" ?? '%s'", $m);
        } elseif ($col->Null == 'YES') {
            $def = " ?? null";
        }
        return $def;
    }
    public function addMoveRelations(EloquentModel $model, $moveRelations): EloquentModel
    {
        if (sizeof($moveRelations) > 0) {
            $property = new PropertyModel('moveRelations');
            $property->setAccess('protected')->setValue($moveRelations)->setDocBlock(new DocBlockModel([' Relations that added to model .', '', '@var array']));
            $model->addProperty($property);
        }
        return $model;
    }
    public function addUses(EloquentModel $model, MConfig $config, $tableName): EloquentModel
    {
        $relationUses = $this->getUseNamespace($tableName, $config);
        if (!empty($relationUses)) {
            $model->addUses(new UseClassModel(ltrim($relationUses, '\\')));
        }
        return $model;
    }
    public function getPriority(): int
    {
        return 5;
    }
}
