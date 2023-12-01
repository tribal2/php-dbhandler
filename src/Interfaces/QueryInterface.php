<?php

namespace Tribal2\DbHandler\Interfaces;

use PDO;

interface QueryInterface {


  public function execute(
    ?PDO $pdo = NULL,
    ?PDOBindBuilderInterface $bindBuilder = NULL
  );


  public function getSql(?PDOBindBuilderInterface $bindBuilder = NULL): string;


}
