<?php

namespace Tribal2\DbHandler\Traits;

use PDO;
use PDOStatement;

trait QueryFetchResultsTrait {


  protected function fetchResults(PDOStatement $statement): array {
    $fetchMode = $this->fetchMethod ?? PDO::FETCH_OBJ;

    return $statement->fetchAll($fetchMode);
  }


}
