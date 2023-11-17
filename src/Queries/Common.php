<?php

namespace Tribal2\DbHandler\Queries;

class Common {


  public static function quoteWrap(string $column): string {
    // If column is * or a function, don't quote it
    if (preg_match('/\w+\(.*\)|\*/', $column)) {
      return $column;
    }

    return "`{$column}`";
  }


}
