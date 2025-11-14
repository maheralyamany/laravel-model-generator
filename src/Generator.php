<?php

namespace MaherAlyamany\ModelGenerator;

use MaherAlyamany\ModelGenerator\Config\MConfig;
use MaherAlyamany\ModelGenerator\Model\EloquentModel;
use MaherAlyamany\ModelGenerator\Processor\ProcessorInterface;
use IteratorAggregate;

class Generator
{
    /**
     * @var ProcessorInterface[]
     */
    protected array $processors;

    /**
     * @param ProcessorInterface[]|IteratorAggregate $processors
     */
    public function __construct(iterable $processors)
    {
        if ($processors instanceof IteratorAggregate) {
            $this->processors = iterator_to_array($processors);
        } else {
            $this->processors = $processors;
        }
    }

    public function generateModel(MConfig $config): EloquentModel
    {
        $model = new EloquentModel();

        $this->sortProcessorsByPriority();

        foreach ($this->processors as $processor) {
            $processor->process($model, $config);
        }

        return $model;
    }

    protected function sortProcessorsByPriority(): void
    {
        usort($this->processors, function (ProcessorInterface $one, ProcessorInterface $two) {
            if ($one->getPriority() == $two->getPriority()) {
                return 0;
            }

            return $one->getPriority() < $two->getPriority() ? 1 : -1;
        });
    }
}
