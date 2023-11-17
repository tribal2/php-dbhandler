<?php

namespace Tribal2\DbHandler\Queries;

class Common {


  /**
   * Quote wrap a column name
   * @param string $column
   *
   * @return string
   */
  public static function quoteWrap(string $column): string {
    // If column is * or a function, don't quote it
    if (preg_match('/\w+\(.*\)|\*/', $column)) {
      return $column;
    }

    return "`{$column}`";
  }


  /**
   * Parse columns to be used in a query
   * @param string|string[] $cols
   *
   * @return string
   */
  public static function parseColumns($cols): string {
    $_cols = is_string($cols)
      ? explode(',', $cols)
      : $cols;

    $colsArr = [];
    foreach($_cols as $col) {
      $colsArr[] = Common::quoteWrap(trim($col));
    }

    return implode(', ', $colsArr);
  }


}
