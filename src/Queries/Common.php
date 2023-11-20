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


  /**
   * Parse values to be used in a query
   * @param mixed   $value
   * @param ?string $column
   *
   * @return void
   */
  public static function checkValue($value, ?string $column = NULL): void {
    if (
      is_string($value)
      || is_numeric($value)
      || is_null($value)
      || is_bool($value)
    ) {
      return;
    }

    $valType = gettype($value);
    $forColumn = isset($column) ? " for '{$column}'" : '';
    $e = "The value to write in the database must be string, number, NULL or "
      . "boolean. The value entered{$forColumn} is of type '{$valType}'.";

    throw new \Exception($e, 500);
  }


}
