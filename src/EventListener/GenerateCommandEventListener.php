<?php

namespace MaherAlyamany\ModelGenerator\EventListener;

use MaherAlyamany\ModelGenerator\TypeRegistry;
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
        $userTypes = config('model_generator.db_types', []);

        foreach ($userTypes as $type => $value) {
            $this->typeRegistry->registerType($type, $value);
        }
    }
}
