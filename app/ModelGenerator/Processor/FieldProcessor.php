<?php

namespace App\ModelGenerator\Processor;

use App\CodeGenerator\Model\ArgumentModel;
use App\CodeGenerator\Model\DocBlockModel;
use App\CodeGenerator\Model\MethodModel;
use App\CodeGenerator\Model\PropertyModel;
use App\CodeGenerator\Model\UseClassModel;
use App\CodeGenerator\Model\UseTraitModel;
use App\CodeGenerator\Model\VirtualPropertyModel;
use App\ModelGenerator\Config\MConfig;
use App\ModelGenerator\Helper\MFormatter;
use App\ModelGenerator\Helper\Prefix;
use App\ModelGenerator\Model\EloquentModel;
use App\ModelGenerator\TypeRegistry;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Config as Conf;
use Illuminate\Support\Str;

class FieldProcessor implements ProcessorInterface
{
    public function __construct(private DatabaseManager $databaseManager, private TypeRegistry $typeRegistry) {}

    public function process(EloquentModel $model, MConfig $config): void
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
        $customCasts = [];
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
                    $customCasts[$colName] = $cast;
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

                $model->addOrReplaceProperty($property);
            }
        }

        $model->setTableColumns($columns);
        $this->addCustomCastingMethod($model,  $customCasts);
    }


    public  function addCustomCastingMethod(EloquentModel $model,  array $customCasts): EloquentModel
    {
        if (count($customCasts) > 0) {
            $model->addUses(new UseClassModel(ltrim("Illuminate\Database\Eloquent\Casts\Attribute", '\\')));
            foreach ($customCasts as $key => $value) {
                $colName = Str::snake($key);
                $method = new MethodModel($colName, "protected");
                $stub =  "return Attribute::make(
			get: {get},
			set: {set}
		);";
                $stub = str_replace("{get}", $value['get'], $stub);
                $stub = str_replace("{set}", $value['set'], $stub);

                $params = ['Interact with the  ' . $key . ' attribute.'];
                $params[] = '@return \Illuminate\Database\Eloquent\Casts\Attribute';
                $method->setBody($stub);
                $method->setDocBlock(new DocBlockModel($params));
                $model->addMethod($method);
            }
        }
        return $model;
    }
    public function getPriority(): int
    {
        return 5;
    }
}
