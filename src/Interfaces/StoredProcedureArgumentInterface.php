<?php

namespace Tribal2\DbHandler\Interfaces;

use PDO;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;

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
    string $dbName,
    string $procedureName,
    ?PDO $pdo = NULL,
    ?PDOBindBuilderInterface $bindBuilder = NULL,
  ): array;


}
