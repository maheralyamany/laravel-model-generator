<?php

namespace MaherAlyamany\ModelGenerator\Config;



use Illuminate\Support\Facades\Config as BaseConfig;
use Illuminate\Support\Facades\DB;

class MConfig
{
    private ?string $prefix = '';
    private ?string $className = null;
    private ?string $tableName = null;
    private ?string $namespace = null;
    private ?string $baseClassName = null;
    private ?bool $noTimestamps = null;
    private ?string $dateFormat = null;
    private ?string $connection = null;
    private ?string $databaseName = null;
    private bool $hasCreateMethod = true;
    private array $tablesNamespace = [];

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->tablesNamespace = config('models_namespaces', []);
    }
    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(?string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function setTableName(?string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function setNamespace(?string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getBaseClassName(): ?string
    {
        return $this->baseClassName;
    }

    public function setBaseClassName(?string $baseClassName): self
    {
        $this->baseClassName = $baseClassName;

        return $this;
    }

    public function getNoTimestamps(): ?bool
    {
        return $this->noTimestamps;
    }

    public function setNoTimestamps(?bool $noTimestamps): self
    {
        $this->noTimestamps = $noTimestamps;

        return $this;
    }

    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(?string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    public function getConnection(): ?string
    {
        if ($this->connection == null) {
            //$this->connection = config('database.default', 'mysql');
            $this->connection = BaseConfig::get('database.default', 'mysql');
        }
        return $this->connection;
    }
    public function getDatabaseName(): ?string
    {
        if ($this->databaseName == null) {
            $db = BaseConfig::get('database.connections.' . $this->getConnection());
            $this->databaseName = $db['database'];
        }
        return $this->databaseName;
    }

    public function setConnection(?string $connection): self
    {

        $defaultConnection = BaseConfig::get('database.default', 'mysql');
        if (is_null($connection)) {
            $connection = $defaultConnection;
        }


        $this->connection = $connection;
        $db = BaseConfig::get('database.connections.' . $connection);
        $this->databaseName = $db['database'];
        if ($this->connection != $defaultConnection) {

            BaseConfig::set("database.connections.{$defaultConnection}", $db);
            BaseConfig::set("database.default", $connection);
            // dd($connection,$defaultConnection, $this->databaseName, BaseConfig::get("database.connections.{$defaultConnection}.database"));


            DB::purge($connection);
            DB::reconnect($connection);
        }
        return $this;
    }

    /**
     * @return
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @param  $prefix
     * @return self
     */
    public function setPrefix(?string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Get the value of hasCreateMethod
     */
    public function getHasCreateMethod()
    {
        return $this->hasCreateMethod;
    }

    /**
     * Set the value of hasCreateMethod
     *
     * @return  self
     */
    public function setHasCreateMethod($hasCreateMethod)
    {
        if (is_string($hasCreateMethod))
            $hasCreateMethod = $hasCreateMethod == 'true';
        $this->hasCreateMethod = $hasCreateMethod;

        return $this;
    }

    /**
     * Get the value of tablesNamespace
     */
    public function getTablesNamespace()
    {
        return $this->tablesNamespace;
    }

    /**
     * Set the value of tablesNamespace
     *
     * @return  self
     */
    public function setTablesNamespace($tablesNamespace)
    {
        $this->tablesNamespace = $tablesNamespace;

        return $this;
    }
}
