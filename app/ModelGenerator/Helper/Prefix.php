<?php

namespace App\ModelGenerator\Helper;

class Prefix
{
    private static ?string $prefix = null;
    public static $shortMethodNames = [
        'facility_classifications' => 'classifications',
        'standard_infrastructures' => 'infrastructures',
        'infrast_standards' => 'standards',
        'evaluation_inputs' => 'eval_inputs',
    ];
    public static function setPrefix(?string $prefix): void
    {
        self::$prefix = (string) $prefix;
    }

    public static function add(string $tableName): string
    {
        return self::$prefix . $tableName;
    }

    public static function remove(string $tableName): string
    {
        $prefix = preg_quote(self::$prefix, '/');
        return preg_replace("/^$prefix/", '', $tableName);
    }
    public static function getRelationMethodName(string $tableName, string $prefix): string
    {
        if (key_exists($tableName, Prefix::$shortMethodNames)) {
            return Prefix::$shortMethodNames[$tableName];
        }
        return Prefix::removeRelation($tableName, $prefix);

    }
    public static function removeRelation(string $tableName, string $prefix): string
    {
        $prefix = preg_quote($prefix, '/');
        return preg_replace("/^$prefix/", '', $tableName);
    }
}
