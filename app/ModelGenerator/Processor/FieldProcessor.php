<?php

namespace App\ModelGenerator\Processor;

use App\CodeGenerator\Model\ArgumentModel;
use App\CodeGenerator\Model\DocBlockModel;
use App\CodeGenerator\Model\MethodModel;
use App\CodeGenerator\Model\PropertyModel;
use App\CodeGenerator\Model\UseClassModel;
use App\CodeGenerator\Model\UseTraitModel;
use App\CodeGenerator\Model\VirtualPropertyModel;
use App\ModelGenerator\Config\Config;
use App\ModelGenerator\Helper\MFormatter;
use App\ModelGenerator\Helper\Prefix;
use App\ModelGenerator\Model\EloquentModel;
use App\ModelGenerator\TypeRegistry;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Config as Conf;

class FieldProcessor implements ProcessorInterface
{
    public function __construct(private DatabaseManager $databaseManager, private TypeRegistry $typeRegistry)
    {
    }

    public function process(EloquentModel $model, Config $config): void
    {
        $connection = $config->getConnection();

        $database = null;
        if ($connection == null) {
            $connection = config('database.default', 'mysql');
        }

        $database = $config->getDatabaseName();
        $table_name = $model->getTableName();

        $model->setDatabase($database);
        $instance = \App\Abstracts\MySqlDbPlatform::get();
        $columns = $instance->getListTableColumns($table_name, $database);
        $fillable = [];
        $forcedNullStrings = [];
        $forcedNullNumbers = [];
        $casts = [];
        $dates = [];
        foreach ($columns as $col) {
            //dd($col);
            // $colName=sprintf("'%s'",$col->Field);
            $colName = $col->Field;
            $model->addProperty(new VirtualPropertyModel(
                $colName,
                $instance->resolveType($col->DataType, ($col->Null == 'YES'))
            ));

            if ($col->Extra != 'auto_increment') {
                $fillable[] = $colName;
            }
            if ($col->Null == 'YES') {
                if (in_array($col->DataType, $instance->getStringTypeMapping()) || in_array($col->DataType, $instance->getDateTypeMapping())) {
                    $forcedNullStrings[] = $colName;
                }
                if (in_array($col->DataType, $instance->getNumberTypeMapping())) {
                    $forcedNullNumbers[] = $colName;
                }
            }
            if (in_array($col->DataType, $instance->getDateTypeMapping())) {
                $dates[] = $colName;
            }

            if ($col->Key != "PRI") {
                if (!is_null($cast = $instance->getCustomCastingTypeMapping($col->DataType))) {
                    $casts[] = sprintf("'%s' =>%s", $colName, $cast);
                } elseif (key_exists($col->DataType, $instance->getCastingTypeMapping())) {
                    $casts[] = sprintf("'%s' =>'%s'", $colName, $instance->getCastingTypeMapping()[$col->DataType]);
                }
            }
        }

        $props = [

           /*  'excludeLogging' => ['access' => 'public', 'value' => true, 'doc' => ['Do not track additions, modifications and deletions.', '', '@var bool']], */
            'table' => ['access' => 'protected', 'value' => $table_name, 'doc' => ['The database table used by the model.', '', '@var string']],

            'fillable' => ['access' => 'protected', 'value' => $fillable, 'doc' => [' Attributes that should be mass-assignable.', '', '@var array']],
            'forcedNullStrings' => ['access' => 'protected', 'value' => $forcedNullStrings, 'doc' => [' Attributes that should be string null.', '', '@var array']],
            'forcedNullNumbers' => ['access' => 'protected', 'value' => $forcedNullNumbers, 'doc' => [' Attributes that should be number null.', '', '@var array']],
            'casts' => ['access' => 'protected', 'value' => $casts, 'doc' => ['The attributes that should be casted to native types.', '', '@var array']],
            'dates' => ['access' => 'protected', 'value' => $dates, 'doc' => ['The attributes that should be date  types.', '', '@var array']],
        ];
        if (in_array('level_id', $fillable) && in_array('mixed_code', $fillable)) {
            $model->addUses(new UseClassModel(ltrim("App\Traits\Auth\MixedLevelsDataTrait", '\\')));
            $model->addTrait(new UseTraitModel('MixedLevelsDataTrait'));
        }
        if (in_array('deleted_at', $fillable)) {
            $model->addUses(new UseClassModel(ltrim("Illuminate\Database\Eloquent\SoftDeletes", '\\')));
            $model->addTrait(new UseTraitModel('SoftDeletes'));
        }
        foreach ($props as $key => $val) {
            if ((is_array($val['value']) && sizeof($val['value']) > 0) || (!is_array($val['value']) && !is_null($val['value']))) {
                $property = new PropertyModel($key);
                $property->setAccess($val['access'])->setValue($val['value'])->setDocBlock(new DocBlockModel($val['doc']));
                $model->addProperty($property);
            }
        }
        $model->setTableColumns($columns);
        //$this->addCreateOrUpdateMethod($model, $columns);
    }

    public function process2(EloquentModel $model, Config $config): void
    {
        /*  // $instance = \App\Abstracts\MySqlDbPlatform::get();

        $schemaManager = $this->databaseManager->connection($config->getConnection())->getDoctrineSchemaManager();
        // $this->databaseManager->connection($config->getConnection())->select()
        $tableDetails = $schemaManager->listTableDetails(Prefix::add($model->getTableName()));
        $primaryColumnNames = $tableDetails->getPrimaryKey() ? $tableDetails->getPrimaryKey()->getColumns() : [];
        $table_name = $tableDetails->getName();
        $columnNames = [];
        $nullStringColumns = [];
        $nullNumberColumns = [];
        $castColumns = [];
        $columnsTypes = [];
        //dd($tableDetails->getColumns());
        foreach ($tableDetails->getColumns() as $column) {
            $colName = $column->getName();

            $columnsTypes[$colName] = $column->getType()->getName();

            $model->addProperty(new VirtualPropertyModel(
                $column->getName(),
                $this->typeRegistry->resolveType($column->getType()->getName())
            ));

            if (!$column->getAutoincrement()) {
                $columnNames[] = $colName;
            }
        }
        dd($columnsTypes);

        $props = [
            'connection' => ['access' => 'protected', 'value' => 'mysql_base', 'doc' => '@var string'],
            'table' => ['access' => 'protected', 'value' => $table_name, 'doc' => '@var string'],
            'fillable' => ['access' => 'protected', 'value' => $columnNames, 'doc' => '@var array'],
            'forcedNullStrings' => ['access' => 'protected', 'value' => $nullStringColumns, 'doc' => '@var array'],
            'forcedNullNumbers' => ['access' => 'protected', 'value' => $nullNumberColumns, 'doc' => '@var array'],
            'casts' => ['access' => 'protected', 'value' => $castColumns, 'doc' => '@var array'],
        ];

        foreach ($props as $key => $val) {
            if ((is_array($val['value']) && sizeof($val['value']) > 0) || (!is_array($val['value']) && !is_null($val['value']))) {
                $property = new PropertyModel($key);
                $property->setAccess($val['access'])->setValue($val['value'])->setDocBlock(new DocBlockModel($val['doc']));
                $model->addProperty($property);
            }
        } */

        /*  $tableProperty = new PropertyModel('table');
    $tableProperty->setAccess('protected')->setValue($table_name)->setDocBlock(new DocBlockModel('@var string'));
    $model->addProperty($tableProperty);
    $fillableProperty = new PropertyModel('fillable');
    $fillableProperty->setAccess('protected')->setValue($columnNames)->setDocBlock(new DocBlockModel('@var array'));
    $model->addProperty($fillableProperty);
    if (sizeof($nullStringColumns) > 0) {
    $nullStringProperty = new PropertyModel('forcedNullStrings');
    $nullStringProperty->setAccess('protected')->setValue($nullStringColumns)->setDocBlock(new DocBlockModel('@var array'));
    $model->addProperty($nullStringProperty);
    }
    if (sizeof($nullNumberColumns) > 0) {
    $nullNumberProperty = new PropertyModel('forcedNullNumbers');
    $nullNumberProperty->setAccess('protected')->setValue($nullNumberColumns)->setDocBlock(new DocBlockModel('@var array'));
    $model->addProperty($nullNumberProperty);
    }
    if (sizeof($castColumns) > 0) {
    $castProperty = new PropertyModel('casts');
    $castProperty->setAccess('protected')->setValue($castColumns)->setDocBlock(new DocBlockModel('@var array'));
    $model->addProperty($castProperty);
    } */
    }

    public function getPriority(): int
    {
        return 5;
    }
}
