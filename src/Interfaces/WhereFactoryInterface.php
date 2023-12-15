<?php

namespace Tribal2\DbHandler\Interfaces;

use PDO;

interface WhereFactoryInterface {


  public function make(
    string $key,
    mixed $value,
    string $operator = '=',
    int $pdoType = PDO::PARAM_STR,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


}
