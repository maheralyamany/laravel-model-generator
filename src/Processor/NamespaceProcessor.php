<?php

namespace MaherAlyamany\ModelGenerator\Processor;

use MaherAlyamany\CodeGenerator\Model\NamespaceModel;
use MaherAlyamany\ModelGenerator\Config\MConfig;
use MaherAlyamany\ModelGenerator\Model\EloquentModel;

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
