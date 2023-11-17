<?php

namespace Tribal2\DbHandler\Queries;

use Tribal2\DbHandler\Helpers\Common;
use Tribal2\DbHandler\PDOBindBuilder;

class Where {


  /**
   * Validate an operator
   * @param string $operator Operator to validate
   *
   * @return string Valid operator
   * @throws \Exception If operator is not valid
   */
  private static function validateOperator($operator) {
    $validOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
    if (!in_array($operator, $validOperators)) {
      $msg = "El operador '{$operator}' no es válido.";
      throw new \Exception($msg, 400);
    }

    return $operator;
  }


  /**
   * Generate a SQL 'where' clause from an array of conditions
   * @param PDOBindBuilder $bindBuilder Instance of PDOBindBuilder
   * @param array          $where       Array of conditions
   *
   * @return string SQL 'where' clause
   */
  public static function generate(
    PDOBindBuilder $bindBuilder,
    array $where,
  ): string {
    $whereArr = [];
    foreach($where as $key => $val) {
      $_key = Common::quoteWrap($key);

      // Varias opciones para un sólo campo ==> OR
      if (is_array($val) && !array_key_exists('operator', $val)) {
        $whereArr[] = self::generateComplex($bindBuilder, $key, $val);
        continue;
      }

      // Única opción con operador
      if (is_array($val)) {
        $operator = self::validateOperator($val['operator']);
        $value = is_null($val['value'])
          ? 'NULL'
          : $bindBuilder->addValue($val['value']);
        $whereArr[] = "{$_key} {$operator} {$value}";
        continue;
      }

      // Única opción simple
      $whereArr[] = is_null($val)
        ? "{$_key} IS NULL"
        : "{$_key} LIKE " . $bindBuilder->addValue($val);
    }

    return implode(' AND ', $whereArr);
  }


  public static function generateComplex(
    PDOBindBuilder $bindBuilder,
    string $key,
    array $valueArr,
  ): string {
    $_key = Common::quoteWrap($key);

    $whereArr = [];

    $orClause = [];
    $andClause = [];

    foreach($valueArr as $valValue) {
      // Si el elemento no es otro array..
      if (!is_array($valValue)) {
        $orClause[] = is_null($valValue)
          ? "{$_key} IS NULL"
          : "{$_key} LIKE " . $bindBuilder->addValue($valValue);

        continue;
      }

      // ..si es otro array
      $operator = self::validateOperator($valValue['operator']);
      $value = $valValue['value'];

      if (!is_null($value)) {
        $placeholderValue = $bindBuilder->addValue($value);
        $clause = "{$_key} {$operator} {$placeholderValue}";
        // Si se provee la propiedad 'and', se hará un query así:
        // (valor > 3 AND valor < 10)
        if (isset($valValue['and']) && $valValue['and']) {
          $andClause[] = $clause;
        }
        // Si NO se provee la propiedad 'and':
        // (valor > 3 OR valor < 10)
        else {
          $orClause[] = $clause;
        }
      }
    }

    if (count($orClause) > 0) {
      $orClauseStr = implode(' OR ', $orClause);
      $whereArr[] = "({$orClauseStr})";
    }

    if (count($andClause) > 0) {
      $andClauseStr = implode(' AND ', $andClause);
      $whereArr[] = "({$andClauseStr})";
    }

    return implode(' AND ', $whereArr);
  }


}
