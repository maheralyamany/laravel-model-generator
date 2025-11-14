<?php

declare(strict_types=1);

namespace MaherAlyamany\ModelGenerator\CodeGenerator\Model;

class TableColumn
{
  public string $Field;
  public string $DataType;
  public string $ColumnType;
  public string $Null;
  public string $Key;
  public string $Extra;
  public string $Comment;
  public ?string $Default;
  public ?string $CharacterSet;
  public ?string $Collation;
  public function __construct(string $Field, string $DataType, string $ColumnType, ?string $Null, ?string $Key, ?string $Default, ?string $Extra, ?string $Comment, ?string $CharacterSet, ?string $Collation,)
  {
    $this->Field = $Field;
    $this->DataType = $DataType;
    $this->ColumnType = $ColumnType;
    $this->Null = $Null ?? 'YES';
    $this->Key = $Key ?? '';
    $this->Default = $Default;
    $this->Extra = $Extra ?? '';
    $this->Comment = $Comment ?? '';
    $this->CharacterSet = $CharacterSet;
    $this->Collation = $Collation;
  }
  public static function new(object $obj): TableColumn
  {
    $col = new TableColumn(
      $obj->Field ?? '',
      $obj->DataType ?? '',
      $obj->ColumnType ?? '',
      $obj->Null ?? 'YES',
      $obj->Key ?? '',
      $obj->Default ?? null,
      $obj->Extra ?? '',
      $obj->Comment ?? '',
      $obj->CharacterSet ?? null,
      $obj->Collation ?? null
    );
    return $col;
  }
}
