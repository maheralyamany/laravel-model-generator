<?php

namespace App\ModelGenerator\Processor;

use App\ModelGenerator\Config\Config;
use App\ModelGenerator\Model\EloquentModel;

interface ProcessorInterface
{
    public function process(EloquentModel $model, Config $config): void;
    public function getPriority(): int;
}
