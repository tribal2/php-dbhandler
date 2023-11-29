<?php

namespace Tribal2\DbHandler\Queries;

use PDO;
use Tribal2\DbHandler\Enums\SqlValueTypeEnum;
use Tribal2\DbHandler\Interfaces\CommonInterface;

class Common implements CommonInterface {


  /**
   * Quote wrap a column name
   * @param string $column
   *
   * @return string
   */
  public function quoteWrap(string $column): string {
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
  public function parseColumns($cols): string {
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
   * @return ?int
   */
  public function checkValue(
    $value,
    ?string $column = NULL,
    array $expectedType = [],
  ): ?int {

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
      if (
        $type === SqlValueTypeEnum::INTEGER
        || $type === SqlValueTypeEnum::FLOAT
      ) {
        if (is_numeric($value))
          return is_int($value + 0)
            ? PDO::PARAM_INT
            : NULL;

        if (array_search('number', $eTypeStr) !== FALSE) continue;
        $eTypeStr[] = 'number';
        continue;
      }

      if ($type === SqlValueTypeEnum::NULL) {
        if (is_null($value)) return PDO::PARAM_NULL;

        $eTypeStr[] = 'NULL';
        continue;
      }

      if ($type === SqlValueTypeEnum::BOOLEAN) {
        if (is_bool($value)) return PDO::PARAM_BOOL;

        $eTypeStr[] = 'boolean';
        continue;
      }

      if ($type === SqlValueTypeEnum::STRING) {
        if (is_string($value)) return PDO::PARAM_STR;

        $eTypeStr[] = 'string';
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
