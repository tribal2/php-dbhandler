<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Traits\QueryBeforeExecuteCheckIfReadOnlyTrait;
use Tribal2\DbHandler\Traits\QueryBeforeExecuteCheckTableTrait;
use Tribal2\DbHandler\Traits\QueryFetchCountTrait;

class Delete extends QueryAbstract {
  use QueryBeforeExecuteCheckIfReadOnlyTrait;
  use QueryBeforeExecuteCheckTableTrait;
  use QueryFetchCountTrait;

  private ?WhereInterface $whereClause = NULL;


  protected function beforeExecute(): void {
    $this->checkTable();
    $this->checkIfReadOnly();
  }


  public static function _from(
    string $table,
    PDOWrapperInterface $pdo,
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


}
