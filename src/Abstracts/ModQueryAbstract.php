<?php

namespace Tribal2\DbHandler\Abstracts;

use PDO;
use Tribal2\DbHandler\Interfaces\ColumnsInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\WhereFactoryInterface;
use Tribal2\DbHandler\Table\Columns;

abstract class ModQueryAbstract extends QueryAbstract {

  // Properties
  protected ColumnsInterface $dbColumns;


  public function __construct(
    public string $table,
    ?ColumnsInterface $columns = NULL,
    ?PDO $pdo = NULL,
    ?CommonInterface $common = NULL,
    ?WhereFactoryInterface $whereFactory = NULL
  ) {
    parent::__construct($table, $pdo, $common, $whereFactory);

    $this->dbColumns = $columns ?? Columns::for($table);
  }


}
