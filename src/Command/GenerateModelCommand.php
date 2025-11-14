<?php

namespace MaherAlyamany\ModelGenerator\Command;

use MaherAlyamany\ModelGenerator\Generator;
use MaherAlyamany\ModelGenerator\Helper\EmgHelper;
use MaherAlyamany\ModelGenerator\Helper\Prefix;
use Illuminate\Console\Command;
use MaherAlyamany\ModelGenerator\Schema\MDbManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
class GenerateModelCommand extends Command
{
    use GenerateCommandTrait;

    protected $name = 'maheralyamany:generate:model';

    public function __construct(protected Generator $generator, protected  MDbManager $mDbManager)
    {

        parent::__construct();
    }

    public function handle()
    {
        $config = $this->createConfig();
        $config->setClassName($this->argument('class-name'));
        $hasCreateMethod = $this->option('has-create');
        Prefix::setPrefix($this->mDbManager->connection($config->getConnection())->getTablePrefix());

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
