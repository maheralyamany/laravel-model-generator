<?php

namespace ModelGenerator\Processor;

use ModelGenerator\Config\MConfig;
use ModelGenerator\Model\EloquentModel;

interface ProcessorInterface
{
    public function process(EloquentModel $model, MConfig $config): void;
    public function getPriority(): int;
}
