<?php

declare(strict_types=1);

namespace ModelGenerator\Illuminate;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\DatabaseManager;
use ModelGenerator\Illuminate\MySql\Schema as MySqlSchema;
class MDbManager
{



  private DatabaseManager $databaseManager;
  /**
   * The AbstractMSchemaManager instance in use.
   */
  private static ?AbstractMSchemaManager $instance = null;
  public function __construct(?DatabaseManager $databaseManager = null)
  {
    $this->databaseManager = $databaseManager ?? app('db');
  }
  /**
   * Returns the MySqlDbPlatform instance to use.
   *
   */
  public  function get(): AbstractMSchemaManager
  {
    if (self::$instance === null) {
      self::$instance = new MySqlSchema($this->connection());
    }
    return self::$instance;
  }
  public static function getSchema(): AbstractMSchemaManager
  {
   
    return self::getManager()->get();
  }
  public static function getManager(): self
  {
    return app('db.mmanager');
  }

  /**
   * Get the value of databaseManager
   */
  public function getDatabaseManager()
  {
    return $this->databaseManager;
  }

  /**
   * Get a database connection instance.
   *
   * @param  string|null  $name
   * @return \Illuminate\Database\Connection
   */
  public function connection($name = null)
  {


    return $this->getDatabaseManager()->connection($name);
  }
}
