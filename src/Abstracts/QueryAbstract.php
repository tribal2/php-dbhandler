<?php

namespace Tribal2\DbHandler\Abstracts;

use PDO;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\PDOSingleton;
use Tribal2\DbHandler\Queries\Common;

abstract class QueryAbstract {

  // Properties
  public string $table;

  // Dependencies
  protected PDO $_pdo;
  protected CommonInterface $_common;


  public function __construct(
    string $table,
    ?PDO $pdo = NULL,
    ?CommonInterface $common = NULL,
  ) {
    $this->table = $table;
    $this->_pdo = $pdo ?? PDOSingleton::get();
    $this->_common = $common ?? new Common();
  }


}
