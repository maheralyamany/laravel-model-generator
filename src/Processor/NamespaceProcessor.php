<?php

namespace ModelGenerator\Processor;

use ModelGenerator\CodeGenerator\Model\NamespaceModel;
use ModelGenerator\Config\MConfig;
use ModelGenerator\Model\EloquentModel;

class NamespaceProcessor implements ProcessorInterface
{
    public function process(EloquentModel $model, MConfig $config): void
    {
        $model->setNamespace(new NamespaceModel($config->getNamespace()));
    }

    public function getPriority(): int
    {
        return 6;
    }
}
