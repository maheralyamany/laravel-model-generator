<?php

namespace ModelGenerator;



use ModelGenerator\Illuminate\MDbManager;

class TypeRegistry
{
    private int $isValidConnection=-1;
    protected array $types = [
        'array' => 'array',
        'simple_array' => 'array',
        'json_array' => 'string',
        'bigint' => 'integer',
        'boolean' => 'boolean',
        'datetime' => 'string',
        'datetimetz' => 'string',
        'date' => 'string',
        'time' => 'string',
        'decimal' => 'float',
        'integer' => 'integer',
        'object' => 'object',
        'smallint' => 'integer',
        'string' => 'string',
        'text' => 'string',
        'binary' => 'string',
        'blob' => 'string',
        'float' => 'float',
        'guid' => 'string',
        'enum' => 'string',
    ];

    public function __construct(private MDbManager $mDbManager)
    {

    }

    public function registerAllTypes(): void
    {

       foreach ($this->types as $sqlType => $phpType) {
            $this->registerDoctrineTypeMapping($sqlType, $phpType);
        }
    }

    public function registerType(string $sqlType, string $phpType, string $connection = null): void
    {
        $this->types[$sqlType] = $phpType;

        $this->registerDoctrineTypeMapping($sqlType, $phpType, $connection);
    }

    public function resolveType(string $type): string
    {
        return array_key_exists($type, $this->types) ? $this->types[$type] : 'mixed';
    }

    private function registerDoctrineTypeMapping(string $sqlType, string $phpType, string $connection = null): void
    {

        $manager = $this->mDbManager->connection($connection)->getDoctrineSchemaManager();

        $manager->getDatabasePlatform()->registerDoctrineTypeMapping($sqlType, $phpType);
    }
}
