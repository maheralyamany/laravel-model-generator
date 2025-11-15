<?php

namespace MaherAlyamany\ModelGenerator\Processor;


use MaherAlyamany\ModelGenerator\CodeGenerator\Exception\GeneratorException;
use MaherAlyamany\ModelGenerator\CodeGenerator\Model\ClassNameModel;
use MaherAlyamany\ModelGenerator\CodeGenerator\Model\DocBlockModel;
use MaherAlyamany\ModelGenerator\CodeGenerator\Model\PropertyModel;
use MaherAlyamany\ModelGenerator\CodeGenerator\Model\UseClassModel;
use MaherAlyamany\ModelGenerator\Config\MConfig;
use MaherAlyamany\ModelGenerator\Helper\EmgHelper;
use MaherAlyamany\ModelGenerator\Helper\Prefix;
use MaherAlyamany\ModelGenerator\Model\EloquentModel;

use Illuminate\Support\Facades\Schema;
use MaherAlyamany\ModelGenerator\Schema\MDbManager;

class TableNameProcessor implements ProcessorInterface
{
    public function __construct(private MDbManager $mDbManager) {}

    public function process(EloquentModel $model, MConfig $config): void
    {
        $className = $config->getClassName();
        $baseClassName = $config->getBaseClassName();
        $tableName = $config->getTableName() ?: EmgHelper::getTableNameByClassName($className);


        $prefixedTableName = Prefix::add($tableName);

        if (! $this->mDbManager->get()->hasTable($prefixedTableName)) {

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
