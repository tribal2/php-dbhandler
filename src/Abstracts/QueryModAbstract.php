<?php

namespace Tribal2\DbHandler\Abstracts;

use PDO;
use Tribal2\DbHandler\Interfaces\ColumnsInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Table\Columns;

abstract class QueryModAbstract extends QueryAbstract {

  // Properties
  protected ColumnsInterface $dbColumns;


  public function __construct(
    public string $table,
    ?ColumnsInterface $columns = NULL,
    ?PDO $pdo = NULL,
    ?CommonInterface $common = NULL,
  ) {
    parent::__construct($table, $pdo, $common);

    $this->dbColumns = $columns ?? Columns::for($table);
  }


}
