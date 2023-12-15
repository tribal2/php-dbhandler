<?php

namespace Tribal2\DbHandler\Traits;

use Exception;

trait QueryBeforeExecuteCheckTableTrait {

  public string $table;


  protected function checkTable(): void {
    if (!isset($this->table)) {
      throw new Exception('Table name is not set');
    }
  }


}
