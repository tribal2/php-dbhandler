<?php

namespace Tribal2\DbHandler\Interfaces;

use Tribal2\DbHandler\Interfaces\ColumnsInterface;

interface ColumnsFactoryInterface {


  public function make(string $table): ColumnsInterface;


}
