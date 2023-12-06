<?php

namespace Tribal2\DbHandler\Abstracts;

use PDO;
use Tribal2\DbHandler\Factories\ColumnsFactory;
use Tribal2\DbHandler\Interfaces\ColumnsFactoryInterface;
use Tribal2\DbHandler\Interfaces\ColumnsInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;

abstract class QueryModAbstract extends QueryAbstract {

  // Dependencies
  protected ?ColumnsFactoryInterface $_columnsFactory = NULL;

  // Properties
  protected ColumnsInterface $dbColumns;


  public function __construct(
    PDOWrapperInterface $pdo,
    ?CommonInterface $common = NULL,
    ?ColumnsFactoryInterface $columnsFactory = NULL,
  ) {
    parent::__construct($pdo, $common);

    $this->_columnsFactory = $columnsFactory ?? new ColumnsFactory($pdo);
  }


}
