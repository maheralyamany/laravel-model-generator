<?php

declare(strict_types=1);

namespace ModelGenerator\Illuminate;

interface BaseSchema
{
  /**
   *  Holds instance of the Doctrine connection for this schema manager.
   * @return \Illuminate\Database\ConnectionInterface
   */
  public function connection();
  public static function get(): self;

  public function getCurrentDatabaseExpression(): string;
  public function getListDatabasesSQL();
  public function getListTableConstraintsSQL($table);
  public function getTableNames($database = null);
  public function getListTableIndexesSQL($table, $database = null);
  public function getListTableIndexes($table, $database = null);
  public function getListViewsSQL($database);
  public function getListTableForeignKeysSQL($table, $database = null);
  public function getListTableRelatedKeysSQL($table, $database = null);
  public function getListTableRelatedKeys($table, $database = null);
  public function getListTableForeignKeys($table, $database = null);
  public function getListTablesSQL();
  public function getListTableColumnsSQL($table, $database = null);
  public function getListTableColumns($table, $database = null);
  public function getGroupedListTableColumns($table, $database = null);
  public function getListTableMetadataSQL(string $table, ?string $database = null): string;
  public function getListTableMetadata(string $table, ?string $database = null);
  /**
   * chaek table has Column
   *
   * @param string $table
   * @param string $columnName
   * @param string|null $database
   * @return bool
   */
  public  function hasColumn(string $table, string $columnName, string $database = null): bool;
  public  function hasTable(string $table, $database = null): bool;
}
