<?php

namespace App\Abstracts;

use Illuminate\Support\Facades\Config as BaseConfig;
use Illuminate\Support\Facades\DB;

class MySqlDbPlatform
{
    protected $doctrineTypeMapping;
    protected $castingTypeMapping;
    protected $stringTypeMapping;
    protected $dateTypeMapping;
    protected $numberTypeMapping;
    /**
     * The MySqlDbPlatform instance in use.
     */
    private static ?MySqlDbPlatform $instance = null;
    public function __construct()
    {
        $this->initializeDoctrineTypeMappings();
    }
    public function getDoctrineTypeMapping(): array
    {
        return $this->doctrineTypeMapping;
    }
    public function getCastingTypeMapping(): array
    {
        return $this->castingTypeMapping;
    }
    public function getCustomCastingTypeMapping($dataType): string|null
    {

        return   null;
    }
    public function getStringTypeMapping(): array
    {
        return $this->stringTypeMapping;
    }
    public function getDateTypeMapping(): array
    {
        return $this->dateTypeMapping;
    }
    public function getNumberTypeMapping(): array
    {
        return $this->numberTypeMapping;
    }
    /**
     * Returns the MySqlDbPlatform instance to use.
     *
     */
    final public static function get(): MySqlDbPlatform
    {
        if (self::$instance === null) {
            self::$instance = new MySqlDbPlatform();
        }
        return self::$instance;
    }
    private function getCurrentDatabaseExpression(): string
    {
        return 'DATABASE()';
    }
    private function getDatabaseName($database = null): string
    {
        if ($database == null) {
            $connection = BaseConfig::get('database.default', 'mysql');
            $db = BaseConfig::get('database.connections.' .  $connection);

            return  $db['database'] ?? env('DB_DATABASE', 'forge');
        }
        return $database;
    }
    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
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
            $result = DB::select("SELECT t.TABLE_NAME from INFORMATION_SCHEMA.TABLES t WhERE t.TABLE_SCHEMA = '" . $database . "' AND t.TABLE_TYPE = 'BASE TABLE';");
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
        return DB::select($query);
    }
    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
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
        $database = $this->getDatabaseName($database);
        // The schema name is passed multiple times as a literal in the WHERE clause instead of using a JOIN condition
        // in order to avoid performance issues on MySQL older than 8.0 and the corresponding MariaDB versions
        // caused by https://bugs.mysql.com/bug.php?id=81347
        return 'SELECT k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, ' .
            'k.REFERENCED_COLUMN_NAME /*!50116 , c.UPDATE_RULE, c.DELETE_RULE */ ' .
            'FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k /*!50116 ' .
            'INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS c ON ' .
            'c.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND ' .
            'c.TABLE_NAME = k.TABLE_NAME */ ' .
            'WHERE k.TABLE_NAME = ' . $this->quoteStringLiteral($table) . ' ' .
            'AND k.TABLE_SCHEMA = ' . $this->getDatabaseNameSQL($database) . ' /*!50116 ' .
            'AND c.CONSTRAINT_SCHEMA = ' . $this->getDatabaseNameSQL($database) . ' */' .
            'ORDER BY k.ORDINAL_POSITION';
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
        return DB::select($query);
    }
    public function getListTableForeignKeys($table, $database = null)
    {
        $query = $this->getListTableForeignKeysSQL($table, $database);
        return DB::select($query);
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
        $database = $this->getDatabaseName($database);
        return 'SELECT COLUMN_NAME AS Field,DATA_TYPE AS DataType, COLUMN_TYPE AS ColumnType, IS_NULLABLE AS `Null`, ' .
            'COLUMN_KEY AS `Key`, COLUMN_DEFAULT AS `Default`, EXTRA AS Extra, COLUMN_COMMENT AS Comment, ' .
            'CHARACTER_SET_NAME AS CharacterSet, COLLATION_NAME AS Collation ' .
            'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ' . $this->getDatabaseNameSQL($database) .
            ' AND TABLE_NAME = ' . $this->quoteStringLiteral($table) .
            ' ORDER BY ORDINAL_POSITION ASC';
    }
    public function getListTableColumns($table, $database = null)
    {
        $query = $this->getListTableColumnsSQL($table, $database);
        return DB::select($query);
    }
    public function getGroupedListTableColumns($table, $database = null)
    {
        $query = $this->getListTableColumnsSQL($table, $database);
        $columns = collect(DB::select($query));
        $grouped = $columns->mapToGroups(function ($col) {
            return [$col->Field => $col];
        })->mapWithKeys(fn ($x, $tableName) => [
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
        return collect(DB::select($query))->first();
    }

    public function getModelFields(array $columns): array
    {
        $fillable = [];
        $forcedNullStrings = [];
        $forcedNullNumbers = [];
        $casts = [];
        $dates = [];
        foreach ($columns as $col) {
            // $colName=sprintf("'%s'",$col->Field);
            $colName = $col->Field;
            if ($col->Extra != 'auto_increment') {
                $fillable[] = $colName;
            }
            if ($col->Null == 'YES') {
                if (in_array($col->DataType, $this->getStringTypeMapping()) || in_array($col->DataType, $this->getDateTypeMapping())) {
                    $forcedNullStrings[] = $colName;
                }
                if (in_array($col->DataType, $this->getNumberTypeMapping())) {
                    $forcedNullNumbers[] = $colName;
                }
            }
            if (in_array($col->DataType, $this->getDateTypeMapping())) {
                $dates[] = $colName;
            }
            if (key_exists($col->DataType, $this->getCastingTypeMapping())) {
                $casts[] = sprintf("'%s' => '%s'", $colName, $this->getCastingTypeMapping()[$col->DataType]);
            }
        }
        $result = ['fillable' => $fillable, 'forcedNullStrings' => $forcedNullStrings, 'forcedNullNumbers' => $forcedNullNumbers, 'casts' => $casts, 'dates' => $dates];
        return $result;
    }
    public function resolveType(string $type, bool $isNullable): string
    {
        return ($isNullable ? '?' : '') . (array_key_exists($type, $this->doctrineTypeMapping) ? $this->doctrineTypeMapping[$type] : 'mixed');
    }
    /**
     * {@inheritDoc}
     */
    private function initializeDoctrineTypeMappings()
    {
        $this->castingTypeMapping = [
            'char' => 'string',

            'decimal' => 'float',
            'double' => 'float',
            'float' => 'float',
            'int' => 'int',
            'integer' => 'integer',
            'longblob' => 'string',
            'mediumblob' => 'string',
            'mediumint' => 'integer',
            'numeric' => 'float',
            'real' => 'float',
            'set' => 'simple_array',
            'smallint' => 'int',
            'time' => 'time',
            'timestamp' => 'datetime',
            'tinyblob' => 'blob',
            'tinyint' => 'boolean',
            'enum' => 'boolean',

            'bool' => 'boolean',
            'tinytext' => 'string',
            'varbinary' => 'binary',
        ];
        $this->stringTypeMapping = [
            'char',
            'longtext',
            'mediumtext',
            'string',
            'text',
            'tinytext',
            'varchar',
        ];
        $this->dateTypeMapping = [
            'timestamp',
            'datetime',
            'date',

        ];
        $this->numberTypeMapping = [
            'bigint',
            'double',
            'decimal',
            'float',
            'int',
            'integer',
            'numeric',
            'real',
            'smallint',

        ];
        $this->doctrineTypeMapping = [
            'char' => 'string',
            'date' => 'string',
            'datetime' => 'string',
            'decimal' => 'float',
            'double' => 'float',
            'float' => 'float',
            'int' => 'integer',
            'integer' => 'integer',
            'longblob' => 'string',
            'mediumblob' => 'string',
            'mediumint' => 'integer',
            'numeric' => 'float',
            'real' => 'float',
            'set' => 'simple_array',
            'smallint' => 'integer',
            'time' => 'time',
            'timestamp' => 'string',
            'tinyblob' => 'string',
            'tinyint' => 'boolean',
            'enum' => 'boolean',
            'bool' => 'boolean',
            'tinytext' => 'string',
            'varbinary' => 'string',
            'bigint' => 'integer',
            'binary' => 'string',
            'blob' => 'string',
            'longtext' => 'string',
            'mediumtext' => 'string',
            'string' => 'string',
            'text' => 'string',
            'varchar' => 'string',
            'year' => 'string',
            'array' => 'array',
            'simple_array' => 'array',
            'json_array' => 'string',
            'boolean' => 'boolean',
            'datetimetz' => 'string',
            'object' => 'object',
            'guid' => 'string',
        ];
    }
    /**
     * {@inheritDoc}
     */
    private function quoteStringLiteral($str)
    {
        $str = str_replace('\\', '\\\\', $str); // MySQL requires backslashes to be escaped
        $c = $this->getStringLiteralQuoteCharacter();
        return $c . str_replace($c, $c . $c, $str) . $c;
    }
    private function supportsColumnLengthIndexes(): bool
    {
        return true;
    }
    private function getDatabaseNameSQL(?string $databaseName): string
    {
        if ($databaseName !== null) {
            return $this->quoteStringLiteral($databaseName);
        }
        return $this->getCurrentDatabaseExpression();
    }
    /**
     * Quotes a single identifier (no dot chain separation).
     *
     * @param string $str The identifier name to be quoted.
     *
     * @return string The quoted identifier string.
     */
    private function quoteSingleIdentifier($str)
    {
        $c = $this->getIdentifierQuoteCharacter();
        return $c . str_replace($c, $c . $c, $str) . $c;
    }
    /**
     * Gets the character used for identifier quoting.
     *
     *
     * @return string
     */
    private function getIdentifierQuoteCharacter()
    {
        return '"';
    }
    private function getStringLiteralQuoteCharacter()
    {
        return "'";
    }
}
