<?php

namespace ModelGenerator\Processor;

use ModelGenerator\CodeGenerator\Model\ArgumentModel;
use ModelGenerator\CodeGenerator\Model\BaseMethodModel;
use ModelGenerator\CodeGenerator\Model\DocBlockModel;
use ModelGenerator\CodeGenerator\Model\MethodModel;
use ModelGenerator\CodeGenerator\Model\PropertyModel;
use ModelGenerator\CodeGenerator\Model\UseClassModel;
use ModelGenerator\CodeGenerator\Model\TableColumn;

use ModelGenerator\Config\MConfig;
use ModelGenerator\Helper\MgHelper;
use ModelGenerator\Helper\MgFormatter;
use ModelGenerator\Helper\MgPrefix;
use ModelGenerator\Model\BelongsTo;
use ModelGenerator\Model\BelongsToMany;
use ModelGenerator\Model\EloquentModel;
use ModelGenerator\Model\HasMany;
use ModelGenerator\Model\HasOne;
use ModelGenerator\Model\Relation;
use ModelGenerator\Illuminate\MDbManager;

class RelationProcessor implements ProcessorInterface
{

    public function __construct(private MDbManager $mDbManager) {}
    public function process(EloquentModel $model, MConfig $config): void
    {
        $schemaManager = $this->mDbManager->connection($config->getConnection())->getDoctrineSchemaManager();
        $prefixedTableName = MgPrefix::add($model->getTableName());
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
                        MgPrefix::remove($foreignKey->getForeignTableName()),
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
                        $tableName = MgPrefix::remove($table->getName());
                        $foreignColumn = $localColumns[0];
                        $localColumn = $foreignKey->getForeignColumns()[0];
                        $mrelation = new HasMany($tableName, $foreignColumn, $localColumn);
                        $mrelation->setPrefix($config->getPrefix());
                        $this->addUses($model, $config, $tableName);
                        $moveRelations = $model->addRelation($mrelation, $moveRelations, $isCascade);

                        $secondForeignKey = $foreignKeys[$keys[$key]];
                        $localColumn =  $secondForeignKey->getLocalColumns()[0];
                        $secondForeignTable = MgPrefix::remove($secondForeignKey->getForeignTableName());
                        $relation = new BelongsToMany($secondForeignTable, $tableName, $foreignColumn, $localColumn);
                        $relation->setPrefix($config->getPrefix());
                        $this->addUses($model, $config, $secondForeignTable);
                        // dd($relation, $mrelation);
                        $moveRelations = $model->addRelation($relation, $moveRelations, $isCascade);
                        break;
                    } else {
                        $tableName = MgPrefix::remove($table->getName());
                        $foreignColumn = $localColumns[0];
                        $localColumn = $foreignKey->getForeignColumns()[0];
                        if (MgHelper::isColumnUnique($table, $foreignColumn)) {
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





        if ($config->getHasCreateMethod() && !$model->isKeysEqualsColumns())
            $this->addCreateOrUpdateMethod($model);
    }

    public function addCreateOrUpdateMethod(EloquentModel $model): EloquentModel
    {
        //dd($model->getTableColumns());
        $primary = $model->getPrimaryKeyColumn();
        $this->getDynamicContent($model);
        $dynamicContent = $model->getCreateOrUpdate();
        $formatter = new MgFormatter();
        $space = 3;
        $formatter->line('try {');
        $formatter->line("%%BaseVariables%%", $space);
        $formatter->line('if (is_null($item)) {', $space);
        $formatter->line("%%StoreInputs%%", $space);
        $formatter->line('} else {', $space);
        $formatter->line(" %%UpdateInputs%%", $space);
        $formatter->line('}', $space);
        $formatter->line('return ["status" => true];', $space);
        $formatter->line('} catch (\Exception $ex) {', $space - 1);
        $formatter->line('return  ["status" => false];', $space);
        $formatter->line('}', $space - 1);
        $stub =  $formatter->render();
        $start = '%%';
        $end = '%%';
        foreach ($dynamicContent as $name => $vars) {
            $replace = $start . $name . $end;
            $stub = MgFormatter::replace(' ', $replace, $vars, $stub);
        }
        $method = new MethodModel("createOrUpdate");
        $method->addArgument(new ArgumentModel('request'));
        $param = '@param ';
        $method->addArgument(new ArgumentModel('item', '', 'null'));
        $param .= "static \$item";
        $method->setStatic(true);
        $method->setBody($stub);
        $params = ['create or update model'];
        $params[] = ' @param \Illuminate\Http\Request|object|\stdClass $request';
        $params[] =  $param;
        $params[] = '@return array';
        $method->setDocBlock(new DocBlockModel($params));
        $model->addMethod($method);
        return $model;
    }


    
    public function array_group_by_key(array $array, string $key): array
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
    public function array_keys_exists(array $keys, array $arr)
    {
        return !array_diff_key(array_flip($keys), $arr);
    }
    public function getDynamicContent(EloquentModel $model): EloquentModel
    {
        $columns = $model->getTableColumns();
        if (count($columns) > 0) {
            $storeInputs = new MgFormatter();
            $baseVariables = new MgFormatter();
            $updateInputs = new MgFormatter();
            $storeInputs->line('$item = static::Create([', 1);
            foreach ($columns as $col) {
                if ($col->Extra != 'auto_increment' && $col->Field != 'deleted_at') {
                    $def = static::getColumnDefaultValue($col);
                    $storeInputs->line(sprintf("'%s' => \$request->%s %s,", $col->Field, $col->Field, $def), 2);
                    $updateInputs->line(sprintf("\$item->%s = \$request->%s %s;", $col->Field, $col->Field, $def), 1);
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
    public static function getColumnDefaultValue(TableColumn $col): string
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

    public function addUses(EloquentModel $model, MConfig $config, $tableName): EloquentModel
    {
        $relationUses = $config->getUseNamespace($tableName);
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
