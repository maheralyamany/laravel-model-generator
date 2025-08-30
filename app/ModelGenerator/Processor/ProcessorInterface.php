<?php

namespace App\ModelGenerator\Processor;

use App\ModelGenerator\Config\MConfig;
use App\ModelGenerator\Model\EloquentModel;

interface ProcessorInterface
{
    public function process(EloquentModel $model, MConfig $config): void;
    public function getPriority(): int;
}
