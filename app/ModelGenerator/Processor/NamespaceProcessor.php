<?php

namespace App\ModelGenerator\Processor;

use App\CodeGenerator\Model\NamespaceModel;
use App\ModelGenerator\Config\Config;
use App\ModelGenerator\Model\EloquentModel;

class NamespaceProcessor implements ProcessorInterface
{
    public function process(EloquentModel $model, Config $config): void
    {
        $model->setNamespace(new NamespaceModel($config->getNamespace()));
    }

    public function getPriority(): int
    {
        return 6;
    }
}
