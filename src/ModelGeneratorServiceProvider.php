<?php
namespace MaherAlyamany\ModelGenerator;
use MaherAlyamany\ModelGenerator\Command\GenerateModelCommand;
use MaherAlyamany\ModelGenerator\Command\GenerateModelsCommand;
use MaherAlyamany\ModelGenerator\EventListener\GenerateCommandEventListener;
use MaherAlyamany\ModelGenerator\Generator;
use MaherAlyamany\ModelGenerator\Processor\CustomPrimaryKeyProcessor;
use MaherAlyamany\ModelGenerator\Processor\CustomPropertyProcessor;
use MaherAlyamany\ModelGenerator\Processor\FieldProcessor;
use MaherAlyamany\ModelGenerator\Processor\NamespaceProcessor;
use MaherAlyamany\ModelGenerator\Processor\RelationProcessor;
use MaherAlyamany\ModelGenerator\Processor\TableNameProcessor;
use MaherAlyamany\ModelGenerator\TypeRegistry;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use MaherAlyamany\ModelGenerator\Schema\MDbManager;
class ModelGeneratorServiceProvider extends ServiceProvider
{
    public const PROCESSOR_TAG = 'model_generator.processor';
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/model_generator.php', 'model_generator');
        $this->app->singleton('db.mmanager', fn($app) => new MDbManager($app['db']));
        $this->app->alias('db.mmanager', MDbManager::class);
        $this->app->singleton(MDbManager::class, fn($app) => $app['db.mmanager']);
        $this->commands([
            GenerateModelCommand::class,
            GenerateModelsCommand::class,
        ]);
        $this->app->singleton(TypeRegistry::class, fn($app) => new TypeRegistry($app['db.mmanager']));
        //$this->app->singleton(TypeRegistry::class);
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
        // Publish config
       /*  $this->publishes([
            __DIR__ . '/config/model_generator.php' => config_path('model_generator.php'),
        ], 'model_generator'); */
        Event::listen(CommandStarting::class, [GenerateCommandEventListener::class, 'handle']);
    }
}
