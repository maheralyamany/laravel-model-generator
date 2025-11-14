<?php

namespace MaherAlyamany\ModelGenerator\Processor;

use MaherAlyamany\ModelGenerator\Config\MConfig;
use MaherAlyamany\ModelGenerator\Model\EloquentModel;

interface ProcessorInterface
{
    public function process(EloquentModel $model, MConfig $config): void;
    public function getPriority(): int;
}
