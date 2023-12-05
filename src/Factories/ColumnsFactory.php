<?php

namespace Tribal2\DbHandler\Factories;

use Tribal2\DbHandler\Interfaces\ColumnsFactoryInterface;
use Tribal2\DbHandler\Interfaces\ColumnsInterface;
use Tribal2\DbHandler\Table\Columns;

class ColumnsFactory implements ColumnsFactoryInterface {


  public function make(string $table): ColumnsInterface {
      return new Columns($table);
  }


}
