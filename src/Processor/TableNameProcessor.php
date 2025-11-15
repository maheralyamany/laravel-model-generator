<?php

namespace ModelGenerator\Processor;


use ModelGenerator\CodeGenerator\Exception\GeneratorException;
use ModelGenerator\CodeGenerator\Model\ClassNameModel;
use ModelGenerator\CodeGenerator\Model\DocBlockModel;
use ModelGenerator\CodeGenerator\Model\PropertyModel;
use ModelGenerator\CodeGenerator\Model\UseClassModel;
use ModelGenerator\Config\MConfig;
use ModelGenerator\Helper\MgHelper;
use ModelGenerator\Helper\MgPrefix;
use ModelGenerator\Model\EloquentModel;

use Illuminate\Support\Facades\Schema;
use ModelGenerator\Illuminate\MDbManager;

class TableNameProcessor implements ProcessorInterface
{
    public function __construct(private MDbManager $mDbManager) {}

    public function process(EloquentModel $model, MConfig $config): void
    {
        $className = $config->getClassName();
        $baseClassName = $config->getBaseClassName();
        $tableName = $config->getTableName() ?: MgHelper::getTableNameByClassName($className);


        $prefixedTableName = MgPrefix::add($tableName);

        if (! $this->mDbManager->get()->hasTable($prefixedTableName)) {

            throw new GeneratorException(sprintf('Table %s does not exist', $prefixedTableName));
        }

        $model->setName(new ClassNameModel($className, MgHelper::getShortClassName($baseClassName)))->addUses(new UseClassModel(ltrim($baseClassName, '\\')));
        $model->setTableName($tableName);

        if ($model->getTableName() !== MgHelper::getTableNameByClassName($className)) {
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
