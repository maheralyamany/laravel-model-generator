<?php

declare(strict_types=1);

namespace ModelGenerator\Illuminate\MySql;

use ModelGenerator\Illuminate\AbstractMSchemaManager;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DbSchema;
use ModelGenerator\CodeGenerator\Model\TableColumn;
use ModelGenerator\Illuminate\BaseSchema;

class Schema extends AbstractMSchemaManager implements BaseSchema
{
    /**
     * @var \Illuminate\Database\MySqlConnection
     */
    protected $connection;
    public function __construct(?\Illuminate\Database\ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? DB::connection();
        parent::__construct();
    }
   
    /**
     * @return \Illuminate\Database\MySqlConnection
     */
    public function connection()
    {
        return $this->connection;
    }

     /**
     * The  instance in use.
     */
    private static ?self $instance = null;
    /**
     * Returns the off sself instance to use.
     *
     */
    public static function get(): self
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }
    public function getCurrentDatabaseExpression(): string
    {
        return 'DATABASE()';
    }
    public  function checkDataBaseExists($database): bool
    {
        //$instance = static::get();
        try {
            $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME =  ?";
            $db = $this->connection()->select($query, [$database]);
            if (empty($db)) {
                return false;
            } else {
                return true;
            }
        } catch (\Exception $e) {

            return false;
        }
    }

    public  function isValidConnection(string|null  $connectionName = null): bool
    {
        $db = DbSchema::connection($connectionName)->getConnection()->getDatabaseName();
        return $this->checkDataBaseExists($db);
    }


    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractMSchemaManager} class hierarchy.
     */
    public function getListDatabasesSQL()
    {
        return 'SHOW DATABASES';
    }

    /**
     *
     *
     * {@inheritDoc}
     */
    public function getListTableConstraintsSQL($table)
    {
        return 'SHOW INDEX FROM ' . $table;
    }
    public function getTableNames($database = null)
    {
        $database = $this->getDatabaseName($database);
        $tableNames = [];
        try {
            $result = $this->connection()->select("SELECT t.TABLE_NAME from INFORMATION_SCHEMA.TABLES t WhERE t.TABLE_SCHEMA = '" . $database . "' AND t.TABLE_TYPE = 'BASE TABLE';");
            foreach ($result as $v) {
                if (!is_null($v->TABLE_NAME)) {
                    if (!in_array($v->TABLE_NAME, $tableNames)) {
                        $tableNames[] = $v->TABLE_NAME;
                    }
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
        return $tableNames;
    }
    /**
     * The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     *
     * Two approaches to listing the table indexes. The information_schema is
     * preferred, because it doesn't cause problems with SQL keywords such as "order" or "table".
     */
    public function getListTableIndexesSQL($table, $database = null)
    {
        $database = $this->getDatabaseName($database);
        if ($database !== null) {
            return 'SELECT NON_UNIQUE AS Non_Unique, INDEX_NAME AS Key_name, COLUMN_NAME AS Column_Name,' .
                ' SUB_PART AS Sub_Part, INDEX_TYPE AS Index_Type' .
                ' FROM information_schema.STATISTICS WHERE TABLE_NAME = ' . $this->quoteStringLiteral($table) .
                ' AND TABLE_SCHEMA = ' . $this->quoteStringLiteral($database) .
                ' ORDER BY SEQ_IN_INDEX ASC';
        }
        return 'SHOW INDEX FROM ' . $table;
    }
    public function getListTableIndexes($table, $database = null)
    {
        $query = $this->getListTableIndexesSQL($table, $database);
        return $this->connection()->select($query);
    }
    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractMSchemaManager} class hierarchy.
     */
    public function getListViewsSQL($database)
    {
        $database = $this->getDatabaseName($database);
        return 'SELECT * FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ' . $this->quoteStringLiteral($database);
    }
    /**
     * The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * @param string      $table
     * @param string|null $database
     *
     * @return string
     */
    public function getListTableForeignKeysSQL($table, $database = null)
    {

        $tbl = $this->quoteStringLiteral($table);
        $db = $this->getDatabaseNameSQL($this->getDatabaseName($database));


        // The schema name is passed multiple times as a literal in the WHERE clause instead of using a JOIN condition
        // in order to avoid performance issues on MySQL older than 8.0 and the corresponding MariaDB versions
        // caused by https://bugs.mysql.com/bug.php?id=81347
        return "SELECT k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, 
        k.REFERENCED_COLUMN_NAME /*!50116 , c.UPDATE_RULE, c.DELETE_RULE */ 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k /*!50116 
        INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS c ON 
        c.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND 
        c.TABLE_NAME = k.TABLE_NAME */ 
        WHERE k.TABLE_NAME = {$tbl} 
        AND k.TABLE_SCHEMA = {$db} /*!50116 
        AND c.CONSTRAINT_SCHEMA = {$db}  */
        ORDER BY k.ORDINAL_POSITION
        ";
    }
    public function getListTableRelatedKeysSQL($table, $database = null)
    {
        $database = $this->getDatabaseName($database);
        // The schema name is passed multiple times as a literal in the WHERE clause instead of using a JOIN condition
        // in order to avoid performance issues on MySQL older than 8.0 and the corresponding MariaDB versions
        // caused by https://bugs.mysql.com/bug.php?id=81347
        return 'SELECT k.CONSTRAINT_NAME, k.COLUMN_NAME,k.TABLE_NAME, ' .
            'k.REFERENCED_COLUMN_NAME /*!50116 , c.UPDATE_RULE, c.DELETE_RULE */ ' .
            'FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k /*!50116 ' .
            'INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS c ON ' .
            'c.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND ' .
            'c.TABLE_NAME = k.TABLE_NAME */ ' .
            'WHERE k.REFERENCED_TABLE_NAME = ' . $this->quoteStringLiteral($table) . ' ' .
            'AND k.TABLE_SCHEMA = ' . $this->getDatabaseNameSQL($database) . ' /*!50116 ' .
            'AND c.CONSTRAINT_SCHEMA = ' . $this->getDatabaseNameSQL($database) . ' */' .
            'ORDER BY k.ORDINAL_POSITION';
    }
    public function getListTableRelatedKeys($table, $database = null)
    {
        $query = $this->getListTableRelatedKeysSQL($table, $database);
        return $this->connection()->select($query);
    }
    public function getListTableForeignKeys($table, $database = null)
    {
        $query = $this->getListTableForeignKeysSQL($table, $database);
        return $this->connection()->select($query);
    }
    /**
     * chaek table has Column
     *
     * @param string $table
     * @param string $columnName
     * @param string|null $database
     * @return bool
     */
    public  function hasColumn(string $table, string $columnName, string $database = null): bool
    {

        $database =  $this->getDatabaseName($database);
        $query = 'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA =' . $this->getDatabaseNameSQL($database) . '  AND TABLE_NAME =' . $this->quoteStringLiteral($table) . ' AND COLUMN_NAME=' . $this->quoteStringLiteral($columnName);
        $data = collect($this->connection()->select($query));
        return $data->count() > 0;
    }
    public  function hasTable(string $table, $database = null): bool
    {

        $database = $this->getDatabaseName($database);
        try {
            $query = "SELECT t.TABLE_NAME FROM INFORMATION_SCHEMA.TABLES t WhERE t.TABLE_SCHEMA =" . $this->getDatabaseNameSQL($database) . "  AND t.TABLE_TYPE = 'BASE TABLE' AND t.TABLE_NAME=" . $this->quoteStringLiteral($table);
            $data = collect($this->connection()->select($query));
            return $data->count() > 0;
        } catch (\Throwable $th) {
        }
        return false;
    }
    /**
     * The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTablesSQL()
    {
        return "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'";
    }
    /**
     * The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        $db = $this->getDatabaseNameSQL($this->getDatabaseName($database));
        $tbl = $this->quoteStringLiteral($table);
        return "SELECT COLUMN_NAME AS Field,DATA_TYPE AS DataType,
			 COLUMN_TYPE AS ColumnType, IS_NULLABLE AS `Null`, 
			COLUMN_KEY AS `Key`, COLUMN_DEFAULT AS `Default`, EXTRA AS Extra, COLUMN_COMMENT AS Comment, CHARACTER_SET_NAME AS CharacterSet, COLLATION_NAME AS Collation 
			FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = {$db}
			AND TABLE_NAME = {$tbl} ORDER BY ORDINAL_POSITION ASC";
    }
    /**
     * Summary of getListTableColumns
     * @param string $table
     * @param string|null $database
     * @return TableColumn[]
     */
    public function getListTableColumns($table, $database = null)
    {
        $query = $this->getListTableColumnsSQL($table, $database);
        return collect($this->connection()->select($query))->mapWithKeys(fn($obj, $k) => [$k => TableColumn::new($obj)])->all();
    }
    public function getGroupedListTableColumns($table, $database = null)
    {
        $query = $this->getListTableColumnsSQL($table, $database);
        $columns = collect($this->connection()->select($query));
        $grouped = $columns->mapToGroups(function ($col) {
            return [$col->Field => $col];
        })->mapWithKeys(fn($x, $tableName) => [
            $tableName => $x[0],
        ]);
        /* return $columns
->groupBy('Field')
->mapWithKeys(fn($x, $tableName) => [
$tableName => [
'name' => $tableName,
'columns' => $x->map(fn($y) => [
'field' => $y->field,
'type' => $y->type,
'isNullable' => $y->isNullable,
]),
'primary' => ($x[0]->isPrimary) ? $x[0]->field : null,
'file' => $this->getPath($tableName),
],
]); */
        return $grouped->toArray();
    }
    /** The SQL used for schema introspection is an implementation detail and should not be relied upon. */
    public function getListTableMetadataSQL(string $table, ?string $database = null): string
    {
        $database = $this->getDatabaseName($database);
        return sprintf(
            <<<'SQL'
SELECT t.ENGINE,
       t.AUTO_INCREMENT,
       t.TABLE_COMMENT,
       t.CREATE_OPTIONS,
       t.TABLE_COLLATION,
       ccsa.CHARACTER_SET_NAME
FROM information_schema.TABLES t
    INNER JOIN information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` ccsa
        ON ccsa.COLLATION_NAME = t.TABLE_COLLATION
WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = %s AND TABLE_NAME = %s
SQL,
            $this->getDatabaseNameSQL($database),
            $this->quoteStringLiteral($table),
        );
    }
    public function getListTableMetadata(string $table, ?string $database = null)
    {
        $query = $this->getListTableMetadataSQL($table, $database);
        return collect($this->connection()->select($query))->first();
    }
}
