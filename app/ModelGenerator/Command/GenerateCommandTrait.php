<?php

namespace App\ModelGenerator\Command;

use App\ModelGenerator\Config\MConfig;
use App\ModelGenerator\Exception\GeneratorException;
use App\ModelGenerator\Helper\EmgHelper;
use App\ModelGenerator\Helper\Prefix;
use App\ModelGenerator\Model\EloquentModel;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputOption;
use App\ModelGenerator\Generator;
use Illuminate\Database\DatabaseManager;
trait GenerateCommandTrait
{
    use TablesNamespacesTrait;

    protected function createConfig(): MConfig
    {
        return (new MConfig())
            ->setTableName($this->option('table-name'))
            ->setNamespace($this->option('namespace'))
            ->setBaseClassName($this->option('base-class-name'))
            ->setNoTimestamps($this->option('no-timestamps'))
            ->setDateFormat($this->option('date-format'))
            ->setPrefix($this->option('prefix'))
            ->setConnection($this->option('connection'));
    }
    protected function setPrefix($config)
    {
        Prefix::setPrefix(($this->option('prefix') ?? $this->databaseManager->connection($config->getConnection())->getTablePrefix()));
    }
    protected function getAllowTables(): array
    {
        $allowed = $this->option('allow-tables') ?? '';
        if (is_null($allowed) || empty($allowed)) {
            return [];
        }
        $tables = explode(',', $allowed);
        foreach ($tables as $i => $tbl) {
            $tables[$i] = trim($tbl);
        }
        return $tables;
    }
    protected function generateModel(MConfig $config, $tableName, $hasCreateMethod)
    {
        try {
            $config->setTableName($tableName);
            $config->setClassName(EmgHelper::getClassNameByTableName($tableName));
            $config->setNamespace($this->resolveNamespace($tableName, $config));
            $config->setHasCreateMethod($hasCreateMethod);
            $model = $this->generator->generateModel($config);
            $outputFilepath =  $this->saveModel($model, $tableName, $config);
            $this->output->writeln(sprintf("Model %s generated at \n %s", $model->getName()->getName(), $outputFilepath));
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
    protected function saveModel(EloquentModel $model, $tableName, MConfig $config): string
    {

        $content = $model->render();

        $outputFilepath = $this->resolveOutputPath($tableName, $config) . '/' . $model->getName()->getName() . '.php';
        if (!$this->option('no-backup') && file_exists($outputFilepath)) {
            rename($outputFilepath, $outputFilepath . '~');
        }
        file_put_contents($outputFilepath, $content);
        return $outputFilepath;
    }
    protected function emptyPath(): void
    {
        $path = app()->path('Models');
        if (File::exists($path)) {
            $directories =  File::directories($path);
            // dd([$path, $directories]);
            foreach ($directories as $directoriy) {
                File::deleteDirectory($directoriy);
            }
        }
        //file_put_contents($outputFilepath, $content);
    }

    protected function resolveOutputPath($tableName, MConfig $config): string
    {
        $path = $this->option('output-path');
        if ($path === null) {
            $namespace = $this->resolveNamespace($tableName, $config);
            if ($namespace == null) {
                $namespace = 'App\Models';
            }
            $path = str_replace("App\\", '', $namespace);
        }
        if ($path === null) {
            $path = app()->path('Models');
        } elseif (!str_starts_with($path, '/')) {
            $path = app()->path($path);
        }

        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new GeneratorException(sprintf('Could not create directory %s', $path));
            }
        }

        if (!is_writeable($path)) {
            throw new GeneratorException(sprintf('%s is not writeable', $path));
        }

        return $path;
    }

    protected function getCommonOptions(): array
    {
        return [
            ['table-name', 'tn', InputOption::VALUE_OPTIONAL, 'Name of the table to use', null],
            ['prefix', 'pr', InputOption::VALUE_OPTIONAL, 'prefix of the table to use', ''],
            ['output-path', 'op', InputOption::VALUE_OPTIONAL, 'Directory to store generated model', config('model_generator.output_path')],
            ['namespace', 'ns', InputOption::VALUE_OPTIONAL, 'Namespace of the model', config('model_generator.namespace', 'App\Models')],
            ['base-class-name', 'bc', InputOption::VALUE_OPTIONAL, 'Model parent class', config('model_generator.base_class_name', 'Illuminate\Database\Eloquent\Model')],
            ['no-timestamps', 'ts', InputOption::VALUE_OPTIONAL, 'Set timestamps property to false', config('model_generator.no_timestamps', false)],
            ['date-format', 'df', InputOption::VALUE_OPTIONAL, 'dateFormat property', config('model_generator.date_format')],
            ['connection', 'cn', InputOption::VALUE_OPTIONAL, 'Connection property', config('model_generator.connection')],
            ['no-backup', 'b', InputOption::VALUE_OPTIONAL, 'Backup existing model', config('model_generator.no_backup', true)],
        ];
    }
}
