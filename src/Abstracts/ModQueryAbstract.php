<?php

namespace Tribal2\DbHandler\Abstracts;

use Tribal2\DbHandler\Interfaces\ColumnsInterface;
use Tribal2\DbHandler\Table\Columns;

abstract class ModQueryAbstract extends QueryAbstract {

  protected ColumnsInterface $dbColumns;


  public function __construct(
    string $table,
    array $dependencies = [],
  ) {
    parent::__construct($table, $dependencies);

    $this->dbColumns = $dependencies['_columns'] ?? Columns::for($table);
  }


}
