<?php

namespace Tribal2\DbHandler\Interfaces;

interface StoredProcedureArgumentInterface {


  public function __construct(
    int $position,
    string $name,
    string $type,
    ?int $maxCharLength = NULL,
  );


  public function addValue(mixed $value): self;


  public function hasValue(): bool;


  public static function getAllFor(
    string $procedureName,
    SchemaInterface $schema,
  ): array;


}
