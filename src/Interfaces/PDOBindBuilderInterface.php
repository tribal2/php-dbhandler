<?php

namespace Tribal2\DbHandler\Interfaces;

use PDO, PDOStatement;


interface PDOBindBuilderInterface {


  public function addValue(
    mixed $value,
    int $type = PDO::PARAM_STR
  ): string;


  public function addValueWithPrefix(
    mixed $value,
    string $prefix,
    int $type = PDO::PARAM_STR,
  ): string;


  public function getValues(): array;


  public function bindToStatement(PDOStatement $stmt): void;


  public function debugQuery(string $query): string;


}
