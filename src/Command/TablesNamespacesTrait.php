<?php

namespace MaherAlyamany\ModelGenerator\Command;

use MaherAlyamany\ModelGenerator\Config\MConfig;
use MaherAlyamany\ModelGenerator\Helper\EmgHelper;
use Illuminate\Support\Str;

trait TablesNamespacesTrait
{

    public static function getTableNamespace($tableName,MConfig $config): string
    {

        if ($tableName===null || empty($tableName)) {
            return '';
        }
        $tables = $config->getTablesNamespace();
        if (key_exists($tableName, $tables)) {
            return $tables[$tableName];
        }

        return '';
    }
    protected function resolveNamespace($tableName,MConfig $config): string
    {
        $namespace = $this->option('namespace');
        if ($namespace == null) {
            $namespace = 'App\Models';
        }
        $tableNamspace = static::getTableNamespace($tableName,$config);
        if (!empty($tableNamspace)) {
            $namespace = $tableNamspace;
        }
        return $namespace;
    }
    protected function getUseNamespace($tableName,MConfig $config): string
    {
        $namespace =$config->getNamespace();
        if (is_null($tableName) || empty($tableName)) {
            return '';
        }
        $useNamespace = 'App\Models';
        try {
            $tableNamspace = static::getTableNamespace($tableName,$config);
            if (!empty($tableNamspace)) {
               return $tableNamspace;
            }
            if ($useNamespace != $namespace) {
                $tableName = EmgHelper::getClassNameByTableName($tableName);
                $useNamespace .= '\\' . $tableName;
            } else {
                $useNamespace = '';
            }
        } catch (\Throwable $th) {
            dd($tableName);
            throw $th;
        }
        return $useNamespace;
    }
}
