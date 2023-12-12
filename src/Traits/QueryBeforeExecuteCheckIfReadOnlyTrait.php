<?php

namespace Tribal2\DbHandler\Traits;

use Exception;

trait QueryBeforeExecuteCheckIfReadOnlyTrait {


  protected function checkIfReadOnly(): void {
    if ($this->_pdo->isReadOnly()) {
      throw new Exception(
        "Can't execute statement. Read only mode is enabled.",
        409,
      );
    }
  }


}
