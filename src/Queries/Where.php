<?php

namespace Tribal2\DbHandler\Queries;

use PDO;
use Tribal2\DbHandler\Enums\SqlValueTypeEnum;
use Tribal2\DbHandler\PDOBindBuilder;

class Where {


  private function __construct(
    private string $key,
    private $value,
    private string $operator = '=',
    private ?int $pdoType = NULL,
  ) {}


  public function getSql(PDOBindBuilder $bindBuilder): string {
    if (is_array($this->value))
      return $this->getSqlForArrayOfValues($bindBuilder);

    $column = Common::quoteWrap($this->key);

    $valuePlaceholder = $bindBuilder->addValueWithPrefix(
      $this->value,
      $this->key,
      $this->pdoType ?? PDO::PARAM_STR,
    );

    return "{$column} {$this->operator} {$valuePlaceholder}";
  }


  private function getSqlForArrayOfValues(PDOBindBuilder $bindBuilder): string {
    if ($this->key === '' && isset($this->value['whereClauses']))
      return $this->getSqlForArrayOfWhereClauses($bindBuilder);

    $column = Common::quoteWrap($this->key);

    $valuePlaceholders = [];
    foreach ($this->value as $value) {
      $valuePlaceholders[] = $bindBuilder->addValueWithPrefix(
        $value,
        $this->key,
        $this->pdoType ?? PDO::PARAM_STR,
      );
    }

    switch ($this->operator) {
      case 'IN':
      case 'NOT IN':
        return "{$column} {$this->operator} (" . implode(', ', $valuePlaceholders) . ')';

      case 'BETWEEN':
      case 'NOT BETWEEN':
        return "{$column} {$this->operator} {$valuePlaceholders[0]} AND {$valuePlaceholders[1]}";
    }
  }


  private function getSqlForArrayOfWhereClauses(PDOBindBuilder $bindBuilder): string {
    $whereClauses = $this->value['whereClauses'];

    $sqlArr = [];
    foreach ($whereClauses as $whereClause) {
      $subClauseSql = $whereClause->getSql($bindBuilder);
      $sqlArr[] = "{$subClauseSql}";
    }

    $sql = implode(" {$this->operator} ", $sqlArr);

    return "({$sql})";
  }


  public static function or(Where ...$whereClauses): Where {
    return new Where(
      '',
      [
        'whereClauses' => $whereClauses,
      ],
      'OR',
    );
  }


  public static function and(Where ...$whereClauses): Where {
    return new Where(
      '',
      [
        'whereClauses' => $whereClauses,
      ],
      'AND',
    );
  }


  public static function equals(string $key, $value): Where {
    if (is_array($value)) return self::in($key, $value);

    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
      SqlValueTypeEnum::BOOLEAN,
    ]);
    return new Where($key, $value, '=', $pdoType);
  }


  public static function notEquals(string $key, $value): Where {
    if (is_array($value)) return self::notIn($key, $value);

    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
      SqlValueTypeEnum::BOOLEAN,
    ]);
    return new Where($key, $value, '<>', $pdoType);
  }


  public static function greaterThan(string $key, $value): Where {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new Where($key, $value, '>', $pdoType);
  }


  public static function greaterThanOrEquals(string $key, $value): Where {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new Where($key, $value, '>=', $pdoType);
  }


  public static function lessThan(string $key, $value): Where {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new Where($key, $value, '<', $pdoType);
  }


  public static function lessThanOrEquals(string $key, $value): Where {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new Where($key, $value, '<=', $pdoType);
  }


  public static function like(string $key, $value): Where {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
    ]);
    return new Where($key, $value, 'LIKE', $pdoType);
  }


  public static function notLike(string $key, $value): Where {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
    ]);
    return new Where($key, $value, 'NOT LIKE', $pdoType);
  }


  public static function in(string $key, array $values): Where {
    foreach ($values as $value) {
      Common::checkValue($value, $key, [
        SqlValueTypeEnum::STRING,
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
        SqlValueTypeEnum::BOOLEAN,
      ]);
    }
    return new Where($key, $values, 'IN');
  }


  public static function notIn(string $key, array $values): Where {
    foreach ($values as $value) {
      Common::checkValue($value, $key, [
        SqlValueTypeEnum::STRING,
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
        SqlValueTypeEnum::BOOLEAN,
      ]);
    }
    return new Where($key, $values, 'NOT IN');
  }


  public static function between(string $key, $value1, $value2): Where {
    foreach ([ $value1, $value2 ] as $value) {
      Common::checkValue($value, $key, [
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
      ]);
    }
    return new Where($key, [ $value1, $value2 ], 'BETWEEN');
  }


  public static function notBetween(string $key, $value1, $value2): Where {
    foreach ([ $value1, $value2 ] as $value) {
      Common::checkValue($value, $key, [
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
      ]);
    }
    return new Where($key, [ $value1, $value2 ], 'NOT BETWEEN');
  }


  public static function isNull(string $key): Where {
    return new Where($key, 'NULL', 'IS', PDO::PARAM_NULL);
  }


  public static function isNotNull(string $key): Where {
    return new Where($key, 'NULL', 'IS NOT', PDO::PARAM_NULL);
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


  /**
   * Generate a complex SQL 'where' for a single column
   *
   * @param PDOBindBuilder $bindBuilder Instance of PDOBindBuilder
   * @param string         $key         Name of the column
   * @param array          $valueArr    Array of conditions
   *
   * @return string SQL 'where' clause
   */
  private static function generateComplex(
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


}
