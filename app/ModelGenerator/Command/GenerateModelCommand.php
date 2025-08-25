<?php

namespace App\ModelGenerator\Command;

use App\ModelGenerator\Generator;
use App\ModelGenerator\Helper\EmgHelper;
use App\ModelGenerator\Helper\Prefix;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Symfony\Component\Console\Input\InputArgument;

class GenerateModelCommand extends Command
{
    use GenerateCommandTrait;

    protected $name = 'maheralyamany:generate:model';

    public function __construct(private Generator $generator, private DatabaseManager $databaseManager)
    {
        parent::__construct();
    }

    public function handle()
    {
        $config = $this->createConfig();
        $config->setClassName($this->argument('class-name'));
        Prefix::setPrefix($this->databaseManager->connection($config->getConnection())->getTablePrefix());
        $tableName = EmgHelper::getTableNameByClassName($config->getClassName());
        $config->setNamespace($this->resolveNamespace($tableName,$config));
        $model = $this->generator->generateModel($config);

        $this->saveModel($model, $tableName, $config);

        $this->output->writeln(sprintf('Model %s generated', $model->getName()->getName()));
    }

    protected function getArguments()
    {
        return [
            ['class-name', InputArgument::REQUIRED, 'Model class name'],
        ];
    }

    protected function getOptions()
    {
        return $this->getCommonOptions();
    }
}
