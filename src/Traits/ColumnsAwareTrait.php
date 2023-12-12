<?php

namespace Tribal2\DbHandler\Traits;

use Tribal2\DbHandler\Factories\ColumnsFactory;
use Tribal2\DbHandler\Interfaces\ColumnsFactoryInterface;
use Tribal2\DbHandler\Interfaces\ColumnsInterface;

trait ColumnsAwareTrait {

  // Dependencies
  protected ?ColumnsFactoryInterface $_columnsFactory = NULL;

  // Properties
  protected ColumnsInterface $dbColumns;


  public function setColumnsFactory(
    ?ColumnsFactoryInterface $columnsFactory = NULL,
  ): void {
    $this->_columnsFactory = $columnsFactory ?? new ColumnsFactory($this->_pdo);
  }


}
