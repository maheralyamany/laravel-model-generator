<?php

namespace App\ModelGenerator\Command;

use App\ModelGenerator\Config\MConfig;
use App\ModelGenerator\Generator;
use App\ModelGenerator\Helper\EmgHelper;
use App\ModelGenerator\Helper\Prefix;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Symfony\Component\Console\Input\InputOption;

class GenerateModelsCommand extends Command
{
    use GenerateCommandTrait;

    protected $name = 'maheralyamany:generate:models';

     public function __construct(protected Generator $generator,protected  DatabaseManager $databaseManager)
    {

        parent::__construct();
    }


    public function handle()
    {
        $this->emptyPath();
        $config = $this->createConfig();

        Prefix::setPrefix($this->databaseManager->connection($config->getConnection())->getTablePrefix());

        $allowTables = $this->getAllowTables();
        $skipTables = $this->option('skip-table');
        $hasCreateMethod = $this->option('has-create');
        // dd($hasCreateMethod,$this->option('has-create'));
        $skipTables[] = 'migrations';
        $skipTables[] = 'personal_access_tokens';

        if (sizeof($allowTables) > 0) {
            foreach ($allowTables as $table) {
                $tableName = Prefix::remove($table);
                if (in_array($tableName, $skipTables)) {
                    continue;
                }
                $this->generateModel($config, $tableName, $hasCreateMethod);
            }
        } else {
            //$schemaManager = $this->databaseManager->connection($config->getConnection())->getDoctrineSchemaManager();
            $tables =  \App\Abstracts\MySqlDbPlatform::get()->getTableNames($config->getDatabaseName());
            foreach ($tables as $tableName) {
                if (in_array($tableName, $skipTables)) {
                    continue;
                }
                $this->generateModel($config, $tableName, $hasCreateMethod);
            }
        }
    }


    protected function getOptions()
    {
        return array_merge(
            $this->getCommonOptions(),
            [
                ['allow-tables', 'at', InputOption::VALUE_OPTIONAL, 'Tables to  generate models', null],
                ['has-create', 'hc', InputOption::VALUE_OPTIONAL, 'has create Method', true],
                ['skip-table', 'sk', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Tables to skip generating models for', null],
            ],
        );
    }
}
