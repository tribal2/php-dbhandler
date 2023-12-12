<?php

namespace Tribal2\DbHandler\Traits;

use PDOStatement;

trait QueryFetchCountTrait {


  protected function fetchResults(PDOStatement $statement): int {
    return $statement->rowCount();
  }


}
