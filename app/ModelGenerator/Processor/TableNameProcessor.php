<?php

namespace App\ModelGenerator\Processor;

use App\Abstracts\MySqlDbPlatform;
use App\CodeGenerator\Exception\GeneratorException;
use App\CodeGenerator\Model\ClassNameModel;
use App\CodeGenerator\Model\DocBlockModel;
use App\CodeGenerator\Model\PropertyModel;
use App\CodeGenerator\Model\UseClassModel;
use App\ModelGenerator\Config\MConfig;
use App\ModelGenerator\Helper\EmgHelper;
use App\ModelGenerator\Helper\Prefix;
use App\ModelGenerator\Model\EloquentModel;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;

class TableNameProcessor implements ProcessorInterface
{
    public function __construct(private DatabaseManager $databaseManager) {}

    public function process(EloquentModel $model, MConfig $config): void
    {
        $className = $config->getClassName();
        $baseClassName = $config->getBaseClassName();
        $tableName = $config->getTableName() ?: EmgHelper::getTableNameByClassName($className);


        $prefixedTableName = Prefix::add($tableName);

        if (! MySqlDbPlatform::hasTable($prefixedTableName)) {

            throw new GeneratorException(sprintf('Table %s does not exist', $prefixedTableName));
        }

        $model->setName(new ClassNameModel($className, EmgHelper::getShortClassName($baseClassName)))->addUses(new UseClassModel(ltrim($baseClassName, '\\')));
        $model->setTableName($tableName);

        if ($model->getTableName() !== EmgHelper::getTableNameByClassName($className)) {
            $property = new PropertyModel('table', 'protected', $model->getTableName());
            $property->setDocBlock(new DocBlockModel('The table associated with the model.', '', '@var string'));
            $model->addProperty($property);
        }
    }

    public function getPriority(): int
    {
        return 10;
    }
}
