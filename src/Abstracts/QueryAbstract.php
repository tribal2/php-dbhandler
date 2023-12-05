<?php

namespace Tribal2\DbHandler\Abstracts;

use Exception;
use PDO;
use PDOStatement;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;
use Tribal2\DbHandler\Queries\Common;

abstract class QueryAbstract {

  // Properties
  public string $table;

  // Dependencies
  protected PDO $_pdo;
  protected CommonInterface $_common;


  // Abstract methods
  /**
   * Get the SQL query string
   *
   * @param PDOBindBuilderInterface|null $bindBuilder
   *
   * @return string
   */
  abstract public function getSql(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
  ): string;


  public function __construct(
    ?PDO $pdo = NULL,
    ?CommonInterface $common = NULL,
  ) {
    $this->_pdo = $pdo ?? PDOSingleton::get();
    $this->_common = $common ?? new Common();
  }


  protected function beforeExecute() {
    if (!isset($this->table)) {
      throw new Exception('Table name is not set');
    }
  }


  protected function _execute(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
    ?PDO $pdo = NULL,
  ): PDOStatement {
    $_pdo = $pdo ?? $this->_pdo;

    // Verify that the table name is set
    $this->beforeExecute();

    // Bind values
    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    // Generate query
    $query = $this->getSql($bindBuilder);

    // Prepare query and bind values
    $pdoStatement = $_pdo->prepare($query);
    $bindBuilder->bindToStatement($pdoStatement);

    // Execute query
    $pdoStatement->execute();

    return $pdoStatement;
  }


}
