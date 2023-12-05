<?php

namespace Tribal2\DbHandler\Interfaces;

use PDO;

interface QueryInterface {


  public function execute(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
    ?PDO $pdo = NULL,
  );


  public function getSql(?PDOBindBuilderInterface $bindBuilder = NULL): string;


}
