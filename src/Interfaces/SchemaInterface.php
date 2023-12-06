<?php

namespace Tribal2\DbHandler\Interfaces;

use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;

interface SchemaInterface {


  public function __construct(
    PDOWrapperInterface $pdo,
    ?CommonInterface $common = NULL,
  );


  public function getSql(?PDOBindBuilderInterface $_ = NULL): string;


  public function getDatabase(): string;


  public function checkIfTableExists(string $table): bool;


  public function getStoredProcedureArguments(string $procedure): array;


}
