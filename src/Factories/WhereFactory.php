<?php

namespace Tribal2\DbHandler\Factories;

use PDO;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\WhereFactoryInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\Queries\Where;

class WhereFactory implements WhereFactoryInterface {


  public function make(
    string $key,
    mixed $value,
    string $operator = '=',
    int $pdoType = PDO::PARAM_STR,
    ?CommonInterface $common = NULL,
  ): WhereInterface {
      return new Where($key, $value, $operator, $pdoType, $common);
  }


}
