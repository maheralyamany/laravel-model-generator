<?php

namespace ModelGenerator;

use ModelGenerator\Command\GenerateModelCommand;
use ModelGenerator\Command\GenerateModelsCommand;
use ModelGenerator\EventListener\GenerateCommandEventListener;
use ModelGenerator\Generator;
use ModelGenerator\Processor\CustomPrimaryKeyProcessor;
use ModelGenerator\Processor\CustomPropertyProcessor;
use ModelGenerator\Processor\FieldProcessor;
use ModelGenerator\Processor\NamespaceProcessor;
use ModelGenerator\Processor\RelationProcessor;
use ModelGenerator\Processor\TableNameProcessor;
use ModelGenerator\TypeRegistry;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use ModelGenerator\Illuminate\MDbManager;

class ModelGeneratorServiceProvider extends ServiceProvider
{
     /**
     * @var bool
     */
    protected $defer = true;
    public const PROCESSOR_TAG = 'model_generator.processor';
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/model-generator.php', 'model-generator');
        $this->app->singleton('db.mmanager', fn($app) => new MDbManager($app['db']));
        $this->app->alias('db.mmanager', MDbManager::class);
        $this->app->singleton(MDbManager::class, fn($app) => $app['db.mmanager']);

        $this->app->singleton(TypeRegistry::class, fn($app) => new TypeRegistry($app['db.mmanager']));
        //$this->app->singleton(TypeRegistry::class);
        $this->app->singleton(GenerateCommandEventListener::class);
    }
    public function boot()
    {
        if ($this->app->runningInConsole()) {

            // Publish config
            $this->publishes([
                __DIR__ . '/../config/model-generator.php' => config_path('model-generator.php'),
            ], 'model-generator');

            $this->commands([
                GenerateModelCommand::class,
                GenerateModelsCommand::class,
            ]);
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
            Event::listen(CommandStarting::class, [GenerateCommandEventListener::class, 'handle']);
        }
    }
}
