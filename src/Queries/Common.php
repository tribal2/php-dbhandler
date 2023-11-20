<?php

namespace Tribal2\DbHandler\Queries;

use Tribal2\DbHandler\Enums\SqlValueTypeEnum;

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
   * @param mixed              $value        The value to parse
   * @param ?string            $column       The column name
   * @param SqlValueTypeEnum[] $expectedType The expected type of the value. Default: []
   *
   * @throws \Exception
   *
   * @return void
   */
  public static function checkValue(
    $value,
    ?string $column = NULL,
    array $expectedType = [],
  ): void {

    if (empty($expectedType)) {
      $expectedType = [
        SqlValueTypeEnum::STRING,
        SqlValueTypeEnum::INTEGER,
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::NULL,
        SqlValueTypeEnum::BOOLEAN,
      ];
    }

    $eTypeStr = [];

    foreach ($expectedType as $type) {
      if ($type === SqlValueTypeEnum::STRING) {
        if (is_string($value)) return;
        $eTypeStr[] = 'string';
        continue;
      }

      if (
        $type === SqlValueTypeEnum::INTEGER
        || $type === SqlValueTypeEnum::FLOAT
      ) {
        if (is_numeric($value)) return;

        if (array_search('number', $eTypeStr) !== FALSE) continue;
        $eTypeStr[] = 'number';
        continue;
      }

      if ($type === SqlValueTypeEnum::NULL) {
        if (is_null($value)) return;
        $eTypeStr[] = 'NULL';
        continue;
      }

      if ($type === SqlValueTypeEnum::BOOLEAN) {
        if (is_bool($value)) return;
        $eTypeStr[] = 'boolean';
        continue;
      }
    }

    $valType = gettype($value);
    $eType = implode(' or ', $eTypeStr);
    $forColumn = isset($column) ? " for '{$column}'" : '';

    $e = "The value to write in the database must be {$eType}. The value "
      . "entered{$forColumn} is of type '{$valType}'.";

    throw new \Exception($e, 500);
  }


}
