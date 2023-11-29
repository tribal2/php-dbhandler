<?php

namespace Tribal2\DbHandler\Abstracts;

use PDO;
use Tribal2\DbHandler\PDOSingleton;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Queries\Common;

abstract class QueryAbstract {

  public string $table;

  // Class dependencies
  protected CommonInterface $_common;
  protected PDO $_pdo;
  protected string $_where;


  public function __construct(
    string $table,
    array $dependencies = [],
  ) {
    $this->table = $table;

    $this->_common = $dependencies['_common'] ?? new Common();
    $this->_pdo = $dependencies['_pdo'] ?? PDOSingleton::get();

    $this->_where = $dependencies['_where'] ?? Where::class;
  }


}
