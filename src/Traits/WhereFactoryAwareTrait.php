<?php

namespace Tribal2\DbHandler\Traits;

use Tribal2\DbHandler\Factories\WhereFactory;
use Tribal2\DbHandler\Interfaces\WhereFactoryInterface;

trait WhereFactoryAwareTrait {

  // Dependencies
  protected WhereFactoryInterface $_whereFactory;


  public function setWhereFactory(
    ?WhereFactoryInterface $whereFactory = NULL,
  ): void {
    $this->_whereFactory = $whereFactory ?? new WhereFactory();
  }


}
