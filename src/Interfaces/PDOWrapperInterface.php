<?php

namespace Tribal2\DbHandler\Interfaces;

use PDO;
use Tribal2\DbHandler\Interfaces\DbConfigInterface;
use Tribal2\DbHandler\Interfaces\LoggerInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;

interface PDOWrapperInterface {


  public function __construct(
    DbConfigInterface $config,
    LoggerInterface $logger,
  );


  public function execute(
    string $query,
    PDOBindBuilderInterface $bindBuilder,
    ?int $fetchMode = PDO::FETCH_OBJ,
  ): array|int;


  public function getDbName(): string;


}
