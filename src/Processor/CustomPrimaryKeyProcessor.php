<?php

namespace ModelGenerator\Processor;

use ModelGenerator\CodeGenerator\Model\DocBlockModel;
use ModelGenerator\CodeGenerator\Model\PropertyModel;
use ModelGenerator\Config\MConfig;
use ModelGenerator\Helper\MgPrefix;
use ModelGenerator\Model\EloquentModel;
use ModelGenerator\Illuminate\MDbManager;
use ModelGenerator\TypeRegistry;


class CustomPrimaryKeyProcessor implements ProcessorInterface
{
    public function __construct(private MDbManager $mDbManager, private TypeRegistry $typeRegistry)
    {}

    public function process(EloquentModel $model, MConfig $config): void
    {

        $schemaManager = $this->mDbManager->connection($config->getConnection())->getDoctrineSchemaManager();

        $tableDetails = $schemaManager->listTableDetails(MgPrefix::add($model->getTableName()));
        $primaryKey = $tableDetails->getPrimaryKey();
        if ($primaryKey === null) {
            return;
        }

        $columns = $primaryKey->getColumns();
        if (count($columns) !== 1) {
            return;
        }

        $column = $tableDetails->getColumn($columns[0]);
        if ($column->getName() !== 'id') {
            $primaryKeyProperty = new PropertyModel('primaryKey', 'protected', $column->getName());
            $primaryKeyProperty->setDocBlock(
                new DocBlockModel('The primary key for the model.', '', '@var string')
            );
            $model->addProperty($primaryKeyProperty);
        }

        if ($column->getType()->getName() !== 'integer') {
            $keyTypeProperty = new PropertyModel(
                'keyType',
                'protected',
                $this->typeRegistry->resolveType($column->getType()->getName())
            );
            $keyTypeProperty->setDocBlock(
                new DocBlockModel('The "type" of the auto-incrementing ID.', '', '@var string')
            );
            $model->addProperty($keyTypeProperty);
        }

        if (!$column->getAutoincrement()) {
            $autoincrementProperty = new PropertyModel('incrementing', 'public', false);
            $autoincrementProperty->setDocBlock(
                new DocBlockModel('Indicates if the IDs are auto-incrementing.', '', '@var bool')
            );
            $model->addProperty($autoincrementProperty);
        }
    }

    public function getPriority(): int
    {
        return 6;
    }
}
