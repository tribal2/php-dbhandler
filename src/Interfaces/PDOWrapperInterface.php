<?php

namespace Tribal2\DbHandler\Interfaces;

use PDO;
use Psr\Log\LoggerInterface;
use Tribal2\DbHandler\Interfaces\DbConfigInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;

interface PDOWrapperInterface {


  public static function fromPdo(
    PDO $pdo,
    ?LoggerInterface $logger = NULL,
  ): PDOWrapperInterface;


  public function __construct(
    ?DbConfigInterface $config = NULL,
    ?LoggerInterface $logger = NULL,
  );


  public function execute(
    string $query,
    PDOBindBuilderInterface $bindBuilder,
    ?int $fetchMode = PDO::FETCH_OBJ,
  ): array|int;


  public function getDbName(): string;


  public function beginTransaction(): bool;


  public function commit(): bool;


  public function rollBack(): bool;


  public function inTransaction(): bool;


}
