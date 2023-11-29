<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;
use Tribal2\DbHandler\Queries\Common;
use Tribal2\DbHandler\Queries\Where;

class Delete
{

  private string $table;
  private ?Where $whereClause = NULL;


  public static function from(string $table): self {
    return new self($table);
  }


  private function __construct(string $table) {
    $this->table = $table;
  }


  public function where(Where $where): self {
    $this->whereClause = $where;
    return $this;
  }


  public function getSql(?PDOBindBuilder $bindBuilder = NULL): string {
    if ($this->whereClause === NULL) {
      throw new Exception('A WHERE clause is required for DELETE operations', 400);
    }

    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $quotedTable = Common::quoteWrap($this->table);
    $whereSql = $this->whereClause->getSql($bindBuilder);

    $query = "DELETE FROM {$quotedTable} WHERE {$whereSql};";

    return $query;
  }


  public function execute(
    ?PDO $pdo = NULL,
    ?PDOBindBuilder $bindBuilder = NULL,
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
