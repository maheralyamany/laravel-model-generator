<?php

namespace ModelGenerator\Command;

use ModelGenerator\Config\MConfig;
use ModelGenerator\Exception\GeneratorException;
use ModelGenerator\Helper\MgHelper;
use ModelGenerator\Helper\MgPrefix;
use ModelGenerator\Model\EloquentModel;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputOption;
use ModelGenerator\Generator;
use ModelGenerator\Helper\MgPathHelper;

trait GenerateCommandTrait
{


    protected function createConfig(): MConfig
    {

        return MConfig::new($this);
    }
    protected function setPrefix($config)
    {
        MgPrefix::setPrefix(($this->option('prefix') ?? $this->mDbManager->connection($config->getConnection())->getTablePrefix()));
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
    protected function generateModel(MConfig $config, $tableName)
    {
        try {
            $config->setTableName($tableName);
            if (is_null($config->getClassName()))
                $config->setClassName(MgHelper::getClassNameByTableName($tableName));
            $config->setNamespace($config->getTableNamespace($tableName));

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

        $outputFilepath = $config->getOutputPath($tableName) . '/' . $model->getName()->getName() . '.php';
        if ($config->getHasBackup() && file_exists($outputFilepath)) {
            rename($outputFilepath, $outputFilepath . '~');
        }
        file_put_contents($outputFilepath, $content);
        return $outputFilepath;
    }
    protected function emptyPath(MConfig $config): void
    {
        $path = $config->getOutputPath();
        MgPathHelper::emptyDirectory($path);
        //file_put_contents($outputFilepath, $content);
    }



    protected function getCommonOptions(): array
    {
        return [

            ['prefix', 'pr', InputOption::VALUE_OPTIONAL, 'prefix of the table to use', ''],
            ['output-path', 'op', InputOption::VALUE_OPTIONAL, 'Directory to store generated model', config('model-generator.output_path')],
            ['namespace', 'ns', InputOption::VALUE_OPTIONAL, 'Namespace of the model', config('model-generator.namespace', 'App\Models')],
            ['base-class-name', 'bc', InputOption::VALUE_OPTIONAL, 'Model parent class', config('model-generator.base_class_name', 'Illuminate\Database\Eloquent\Model')],
            ['no-timestamps', 'ts', InputOption::VALUE_OPTIONAL, 'Set timestamps property to false', config('model-generator.no_timestamps', false)],
            ['date-format', 'df', InputOption::VALUE_OPTIONAL, 'dateFormat property', config('model-generator.date_format')],
            ['connection', 'cn', InputOption::VALUE_OPTIONAL, 'Connection property', config('model-generator.connection')],
            ['has-backup', 'b', InputOption::VALUE_OPTIONAL, 'Backup existing model', config('model-generator.has_backup', false)],
            ['has-create', 'hc', InputOption::VALUE_OPTIONAL, 'has create Method', false],
        ];
    }
}
