<?php

namespace ModelGenerator\Command;

use ModelGenerator\Generator;
use ModelGenerator\Helper\MgHelper;
use ModelGenerator\Helper\MgPrefix;
use Illuminate\Console\Command;
use ModelGenerator\Illuminate\MDbManager;
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
       
        MgPrefix::setPrefix($this->mDbManager->connection($config->getConnection())->getTablePrefix());

        $tableName = $config->getTableName() ?? MgHelper::getTableNameByClassName($config->getClassName());
        $this->generateModel($config, $tableName);
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
            ],
        );
    }

}
