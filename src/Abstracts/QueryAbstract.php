<?php

namespace Tribal2\DbHandler\Abstracts;

use Exception;
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
    ?PDO $pdo = NULL,
    ?CommonInterface $common = NULL,
  ) {
    $this->_pdo = $pdo ?? PDOSingleton::get();
    $this->_common = $common ?? new Common();
  }


  protected function beforeExecute() {
    if (!isset($this->table)) {
      throw new Exception('Table name is not set');
    }
  }


}
