<?php

namespace App\ModelGenerator\Provider;

use App\ModelGenerator\Command\GenerateModelCommand;
use App\ModelGenerator\Command\GenerateModelsCommand;
use App\ModelGenerator\EventListener\GenerateCommandEventListener;
use App\ModelGenerator\Generator;
use App\ModelGenerator\Processor\CustomPrimaryKeyProcessor;
use App\ModelGenerator\Processor\CustomPropertyProcessor;
use App\ModelGenerator\Processor\FieldProcessor;
use App\ModelGenerator\Processor\NamespaceProcessor;
use App\ModelGenerator\Processor\RelationProcessor;
use App\ModelGenerator\Processor\TableNameProcessor;
use App\ModelGenerator\TypeRegistry;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class GeneratorServiceProvider extends ServiceProvider
{
    public const PROCESSOR_TAG = 'model_generator.processor';

    public function register()
    {
        $this->commands([
            GenerateModelCommand::class,
            GenerateModelsCommand::class,
        ]);

        $this->app->singleton(TypeRegistry::class);
        $this->app->singleton(GenerateCommandEventListener::class);

        $this->app->tag([
            FieldProcessor::class,
            NamespaceProcessor::class,
            RelationProcessor::class,
            CustomPropertyProcessor::class,
            TableNameProcessor::class,
            CustomPrimaryKeyProcessor::class,
        ], self::PROCESSOR_TAG);

        $this->app->bind(Generator::class, function ($app) {
            return new Generator($app->tagged(self::PROCESSOR_TAG));
        });
    }

    public function boot()
    {
        Event::listen(CommandStarting::class, [GenerateCommandEventListener::class, 'handle']);
    }
}
