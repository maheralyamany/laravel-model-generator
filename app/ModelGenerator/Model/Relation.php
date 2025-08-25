<?php

namespace App\ModelGenerator\Model;

use App\ModelGenerator\Helper\EmgHelper;
use App\ModelGenerator\Helper\Prefix;
use Illuminate\Support\Str;

abstract class Relation
{
    protected string $prefix = '';
    protected string $relationMethodName = '';
    protected string $tableName;
    protected string $foreignColumnName;
    protected string $localColumnName;
    protected string $relatedClass;
    protected bool $isCascade = false;
    protected $columns = [];

    public function __construct(string $tableName, string $joinColumnName, string $localColumnName)
    {
        // Prefix::removeRelation($relation->getTableName(),$prefix)
        $this->setTableName($tableName);
        $this->setForeignColumnName($joinColumnName);
        $this->setLocalColumnName($localColumnName);

        /*  if (($joinColumnName == 'doc_mst_id') || ($localColumnName == 'doc_mst_id')) {
            dd($this);
        } */
        $relatedClass = EmgHelper::getClassNameByTableName($tableName);
        $this->setRelatedClass($relatedClass);
    }

    public function initMethodName(): string
    {
        if (Str::endsWith($this->foreignColumnName,'_mst_id')) {

            if (Str::endsWith($this->tableName, '_mstrs')) {
                $this->relationMethodName = "docMstr";
                return $this->relationMethodName;
            } elseif (Str::endsWith($this->tableName, '_dtls')) {
                $this->relationMethodName = "detail";
                return $this->relationMethodName;
            }
        }
         elseif ($this->tableName === 'system_levels') {
            $this->relationMethodName = "levels";
            return $this->relationMethodName;
        }
         elseif ($this->tableName === 'minstry_programs') {
            $this->relationMethodName = "programs";
            return $this->relationMethodName;
        }

        $this->relationMethodName = Prefix::getRelationMethodName($this->tableName, $this->prefix);
        return $this->relationMethodName;
    }
    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->relationMethodName;
    }

    /**
     * @param string $relationMethodName
     * @return self
     */
    public function setMethodName(string $methodName): self
    {
        $this->relationMethodName = $methodName;
        return $this;
    }
    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function getForeignColumnName(): string
    {
        return $this->foreignColumnName;
    }

    public function setForeignColumnName(string $foreignColumnName): self
    {
        $this->foreignColumnName = $foreignColumnName;

        return $this;
    }

    public function getLocalColumnName(): string
    {
        return $this->localColumnName;
    }


    public function setLocalColumnName(string $localColumnName): self
    {
        $this->localColumnName = $localColumnName;

        return $this;
    }

    /**
     * @return
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param  $prefix
     * @return self
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getRelatedClass(): string
    {
        return $this->relatedClass;
    }

    /**
     * @param string $relatedClass
     * @return self
     */
    public function setRelatedClass(string $relatedClass): self
    {
        $this->relatedClass = $relatedClass;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param mixed $columns
     * @return self
     */
    public function setColumns($columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsCascade(): bool
    {
        return $this->isCascade;
    }

    /**
     * @param bool $isCascade
     * @return self
     */
    public function setIsCascade(bool $isCascade): self
    {
        $this->isCascade = $isCascade;
        return $this;
    }
}
