<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;

class Delete extends QueryAbstract {

  private ?WhereInterface $whereClause = NULL;


  public static function from(string $table): self {
    return new self($table);
  }


  public function where(WhereInterface $where): self {
    $this->whereClause = $where;
    return $this;
  }


  public function getSql(?PDOBindBuilderInterface $bindBuilder = NULL): string {
    if ($this->whereClause === NULL) {
      throw new Exception('A WHERE clause is required for DELETE operations', 400);
    }

    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $quotedTable = $this->_common->quoteWrap($this->table);
    $whereSql = $this->whereClause->getSql($bindBuilder);

    $query = "DELETE FROM {$quotedTable} WHERE {$whereSql};";

    return $query;
  }


  public function execute(
    ?PDO $pdo = NULL,
    ?PDOBindBuilderInterface $bindBuilder = NULL,
  ): int {
    $_pdo = $pdo ?? PDOSingleton::get();
    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $query = $this->getSql($bindBuilder);

    $pdoStatement = $_pdo->prepare($query);
    $bindBuilder->bindToStatement($pdoStatement);

    $pdoStatement->execute();

    return $pdoStatement->rowCount();
  }


}
