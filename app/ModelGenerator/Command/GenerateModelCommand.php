<?php

namespace App\ModelGenerator\Command;

use App\ModelGenerator\Generator;
use App\ModelGenerator\Helper\EmgHelper;
use App\ModelGenerator\Helper\Prefix;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
class GenerateModelCommand extends Command
{
    use GenerateCommandTrait;

    protected $name = 'maheralyamany:generate:model';

    public function __construct(protected Generator $generator, protected  DatabaseManager $databaseManager)
    {

        parent::__construct();
    }

    public function handle()
    {
        $config = $this->createConfig();
        $config->setClassName($this->argument('class-name'));
        $hasCreateMethod = $this->option('has-create');
        Prefix::setPrefix($this->databaseManager->connection($config->getConnection())->getTablePrefix());

        $tableName = $config->getTableName() ?? EmgHelper::getTableNameByClassName($config->getClassName());
        $this->generateModel($config, $tableName, $hasCreateMethod);
    }

    protected function getArguments()
    {
        return [
            ['class-name', InputArgument::REQUIRED, 'Model class name'],
        ];
    }
 protected function getOptions()
    {
        return array_merge(
            $this->getCommonOptions(),
            [

                ['has-create', 'hc', InputOption::VALUE_OPTIONAL, 'has create Method', false],

            ],
        );
    }

}
