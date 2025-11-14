<?php

declare(strict_types=1);

namespace MaherAlyamany\ModelGenerator\Schema;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config as BaseConfig;
use Illuminate\Support\Facades\DB;
abstract class AbstractMSchemaManager
{
  protected $doctrineTypeMapping;
  protected $castingTypeMapping;
  protected $stringTypeMapping;
  protected $dateTypeMapping;
  protected $numberTypeMapping;
  protected $default_database;
   /**
     * Holds instance of the Doctrine connection for this schema manager.
     *
     * @var Connection
     */
    protected $_conn;
  public function __construct(?Connection $connection=null)
  {
      $this->_conn     = $connection?? DB::connection();
    $this->initializeDoctrineTypeMappings();
  }
  abstract public static function get(): self;
  abstract public  function checkDataBaseExists($database): bool;
  abstract public  function isValidConnection(string|null  $connectionName = null): string;
  abstract  public function getCurrentDatabaseExpression(): string;
  abstract public function getListDatabasesSQL();
  abstract public function getListTableConstraintsSQL($table);
  abstract public function getTableNames($database = null);
  abstract public function getListTableIndexesSQL($table, $database = null);
  abstract public function getListTableIndexes($table, $database = null);
  abstract public function getListViewsSQL($database);
  abstract public function getListTableForeignKeysSQL($table, $database = null);
  abstract public function getListTableRelatedKeysSQL($table, $database = null);
  abstract public function getListTableRelatedKeys($table, $database = null);
  abstract public function getListTableForeignKeys($table, $database = null);
  abstract public function getListTablesSQL();
  abstract public function getListTableColumnsSQL($table, $database = null);
  abstract public function getListTableColumns($table, $database = null);
  abstract public function getGroupedListTableColumns($table, $database = null);
  abstract public function getListTableMetadataSQL(string $table, ?string $database = null): string;
  abstract public function getListTableMetadata(string $table, ?string $database = null);
  /**
   * chaek table has Column
   *
   * @param string $table
   * @param string $columnName
   * @param string|null $database
   * @return bool
   */
  abstract  public  function hasColumn(string $table, string $columnName, string $database = null): bool;
  abstract public  function hasTable(string $table, $database = null): bool;
  public function getDoctrineTypeMapping(): array
  {
    return $this->doctrineTypeMapping;
  }
  public function getCastingTypeMapping(): array
  {
    return $this->castingTypeMapping;
  }
  public function getCustomCastingTypeMapping($dataType): array|null
  {
    $casts = [

      'longtext' => [
        'get' => 'fn(mixed $value) =>json_decode($value)',
        'set' => 'fn(mixed $value) => $value===null?[]:json_encode($value, true)'
      ],
    ];
    return  $casts[$dataType] ?? null;
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
  protected function getDatabaseName($database = null): string
  {
    if ($database == null) {
      if (is_null($this->default_database)) {
        $connection = BaseConfig::get('database.default', 'mysql');
        $db = BaseConfig::get('database.connections.' .  $connection);
        $this->default_database = $db['database'] ?? env('DB_DATABASE', 'forge');
      }
      return  $this->default_database;
    }
    return $database;
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
  protected function initializeDoctrineTypeMappings()
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
  protected function quoteStringLiteral($str)
  {
    $str = str_replace('\\', '\\\\', $str); // MySQL requires backslashes to be escaped
    $c = $this->getStringLiteralQuoteCharacter();
    return $c . str_replace($c, $c . $c, $str) . $c;
  }
  protected function supportsColumnLengthIndexes(): bool
  {
    return true;
  }
  protected function getDatabaseNameSQL(?string $databaseName): string
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
  protected function quoteSingleIdentifier($str)
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
  protected function getIdentifierQuoteCharacter()
  {
    return '"';
  }
  protected function getStringLiteralQuoteCharacter()
  {
    return "'";
  }
}
