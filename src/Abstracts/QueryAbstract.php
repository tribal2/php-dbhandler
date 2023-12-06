<?php

namespace Tribal2\DbHandler\Abstracts;

use Exception;
use PDO;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Common;

abstract class QueryAbstract {


  abstract public function getSql(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
  ): string;


  abstract protected function beforeExecute(): void;


  // Dependencies
  protected PDOWrapperInterface $_pdo;
  protected CommonInterface $_common;


  public function __construct(
    PDOWrapperInterface $pdo,
    ?CommonInterface $common = NULL,
  ) {
    $this->_pdo = $pdo;
    $this->_common = $common ?? new Common();
  }


  protected function _execute(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
    ?PDOWrapperInterface $pdo = NULL,
    ?int $fetchMode = PDO::FETCH_OBJ,
  ): array|int {
    $_pdo = $pdo ?? $this->_pdo;

    // Before execute hook
    $this->beforeExecute();

    // Generate query
    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();
    $query = $this->getSql($bindBuilder);

    // Execute query
    return $_pdo->execute($query, $bindBuilder, $fetchMode);
  }


}
