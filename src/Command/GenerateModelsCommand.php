<?php

namespace ModelGenerator\Command;


use ModelGenerator\Generator;

use ModelGenerator\Helper\MgPrefix;
use Illuminate\Console\Command;

use ModelGenerator\Illuminate\MDbManager;

use Symfony\Component\Console\Input\InputOption;

class GenerateModelsCommand extends Command
{
    use GenerateCommandTrait;

    protected $name = 'maheralyamany:generate:models';

    public function __construct(protected Generator $generator, protected  MDbManager $mDbManager)
    {

        parent::__construct();
    }


    public function handle()
    {
        $config = $this->createConfig();

        MgPrefix::setPrefix($this->mDbManager->connection($config->getConnection())->getTablePrefix());


        $allowTables = $this->getAllowTables();
        $skipTables = $this->option('skip-tables');

        // dd($hasCreateMethod,$this->option('has-create'));
      
        $this->emptyPath($config);
        if (sizeof($allowTables) > 0) {
            foreach ($allowTables as $table) {
                $tableName = MgPrefix::remove($table);
                if (in_array($tableName, $skipTables)) {
                    continue;
                }
                $this->generateModel($config, $tableName);
            }
        } else {
            //$schemaManager = $this->mDbManager->connection($config->getConnection())->getDoctrineSchemaManager();
            $tables =  $this->mDbManager->get()->getTableNames($config->getDatabaseName());
            foreach ($tables as $tableName) {
                if (in_array($tableName, $skipTables)) {
                    continue;
                }
                $this->generateModel($config, $tableName);
            }
        }
    }


    protected function getOptions()
    {
        return array_merge(
            $this->getCommonOptions(),
            [
                ['allow-tables', 'at', InputOption::VALUE_OPTIONAL, 'Tables to  generate models', null],

                ['skip-tables', 'sk', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Tables to skip generating models for', config('model-generator.except')??[]],
            ],
        );
    }
}
