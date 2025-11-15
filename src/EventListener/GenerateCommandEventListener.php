<?php

namespace ModelGenerator\EventListener;

use ModelGenerator\TypeRegistry;
use Illuminate\Console\Events\CommandStarting;

class GenerateCommandEventListener
{
    private const SUPPORTED_COMMANDS = [
        'maheralyamany:generate:model',
        'maheralyamany:generate:models',
    ];

    public function __construct(private TypeRegistry $typeRegistry) {}

    public function handle(CommandStarting $event): void
    {
        if (!in_array($event->command, self::SUPPORTED_COMMANDS)) {
            return;
        }
        $this->typeRegistry->registerAllTypes();
        $userTypes = config('model-generator.db_types', [])??[];

        foreach ($userTypes as $type => $value) {
            $this->typeRegistry->registerType($type, $value);
        }
    }
}
