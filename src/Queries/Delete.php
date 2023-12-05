<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\QueryInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;

class Delete extends QueryAbstract implements QueryInterface {

  private ?WhereInterface $whereClause = NULL;


  public static function _from(
    string $table,
    ?PDO $pdo = NULL,
    ?CommonInterface $common = NULL,
  ): self {
    $select = new self($pdo, $common);
    $select->from($table);

    return $select;
  }


  public function from(string $table): self {
    $this->table = $table;

    return $this;
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
    ?PDOBindBuilderInterface $bindBuilder = NULL,
    ?PDO $pdo = NULL,
  ): int {
    $executedPdoStatement = parent::_execute($bindBuilder, $pdo);

    // Return the number of affected rows
    return $executedPdoStatement->rowCount();
  }


}
