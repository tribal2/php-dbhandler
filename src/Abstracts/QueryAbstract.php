<?php

namespace Tribal2\DbHandler\Abstracts;

use PDOStatement;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\QueryInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Common;

abstract class QueryAbstract implements QueryInterface {


  abstract public function getSql(?PDOBindBuilderInterface $bindBuilder = NULL): string;


  abstract protected function beforeExecute(): void;


  abstract protected function fetchResults(PDOStatement $statement): int|array;


  // Dependencies
  protected PDOWrapperInterface $_pdo;
  protected CommonInterface $_common;


  public function __construct(
    PDOWrapperInterface $pdo,
    ?CommonInterface $common = NULL,
  ) {
    $this->_pdo = $pdo;
    $this->_common = $common ?? new Common();

    $this->afterConstruct();
  }


  protected function afterConstruct(): void {}


  public function execute(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
  ): array|int {
    // Before execute hook
    $this->beforeExecute();

    // Generate query
    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();
    $query = $this->getSql($bindBuilder);

    // Execute query
    $stmt = $this->_pdo->execute($query, $bindBuilder);

    // Fetch results
    $results = $this->fetchResults($stmt);

    return $results;
  }


}
