<?php

namespace Tribal2\DbHandler\Queries;

use PDO;
use Tribal2\DbHandler\Enums\SqlValueTypeEnum;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\PDOBindBuilder;

class Where implements WhereInterface {

  // Instance variables
  private string $key;
  private mixed $value;
  private string $operator = '=';
  private int $pdoType = PDO::PARAM_STR;

  // Dependencies
  private CommonInterface $common;


  public function __construct(
    string $key,
    mixed $value,
    string $operator = '=',
    int $pdoType = PDO::PARAM_STR,
    ?CommonInterface $common = NULL,
  ) {
    $this->key = $key;
    $this->value = $value;
    $this->operator = $operator;
    $this->pdoType = $pdoType;
    $this->common = $common ?? new Common();
  }


  public function getSql(PDOBindBuilderInterface $bindBuilder): string {
    if (is_array($this->value))
      return $this->getSqlForArrayOfValues($bindBuilder);

    $column = $this->common->quoteWrap($this->key);

    $valuePlaceholder = $bindBuilder->addValueWithPrefix(
      $this->value,
      $this->key,
      $this->pdoType,
    );

    return "{$column} {$this->operator} {$valuePlaceholder}";
  }


  private function getSqlForArrayOfValues(PDOBindBuilder $bindBuilder): string {
    // For OR and AND operators
    if ($this->key === '' && isset($this->value['whereClauses']))
      return $this->getSqlForArrayOfWhereClauses($bindBuilder);

    // For IN, NOT IN, BETWEEN, NOT BETWEEN operators
    $column = $this->common->quoteWrap($this->key);

    $valuePlaceholders = [];
    foreach ($this->value as $value) {
      $valuePlaceholders[] = $bindBuilder->addValueWithPrefix(
        $value,
        $this->key,
        $this->pdoType,
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
    /**
     * @var Where[] $whereClauses
     */
    $whereClauses = $this->value['whereClauses'];

    $sqlArr = [];
    foreach ($whereClauses as $whereClause) {
      $subClauseSql = $whereClause->getSql($bindBuilder);
      $sqlArr[] = "{$subClauseSql}";
    }

    $sql = implode(" {$this->operator} ", $sqlArr);

    return "({$sql})";
  }


  public static function or(WhereInterface ...$whereClauses): Where {
    return new Where(
      '',
      [
        'whereClauses' => $whereClauses,
      ],
      'OR',
    );
  }


  public static function and(WhereInterface ...$whereClauses): Where {
    return new Where(
      '',
      [
        'whereClauses' => $whereClauses,
      ],
      'AND',
    );
  }


  public static function equals(
    string $key,
    mixed $value,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();

    if (is_array($value)) return self::in($key, $value, $common);

    $pdoType = $common->checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
      SqlValueTypeEnum::BOOLEAN,
    ]);
    return new Where($key, $value, '=', $pdoType, $common);
  }


  public static function notEquals(
    string $key,
    mixed $value,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();

    if (is_array($value)) return self::notIn($key, $value, $common);

    $pdoType = $common->checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
      SqlValueTypeEnum::BOOLEAN,
    ]);
    return new Where($key, $value, '<>', $pdoType, $common);
  }


  public static function greaterThan(
    string $key,
    mixed $value,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();
    $pdoType = $common->checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new Where($key, $value, '>', $pdoType, $common);
  }


  public static function greaterThanOrEquals(
    string $key,
    $value,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();
    $pdoType = $common->checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new Where($key, $value, '>=', $pdoType, $common);
  }


  public static function lessThan(
    string $key,
    $value,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();
    $pdoType = $common->checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new Where($key, $value, '<', $pdoType, $common);
  }


  public static function lessThanOrEquals(
    string $key,
    $value,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();
    $pdoType = $common->checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new Where($key, $value, '<=', $pdoType, $common);
  }


  public static function like(
    string $key,
    string $value,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();
    $pdoType = $common->checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
    ]);
    return new Where($key, $value, 'LIKE', $pdoType, $common);
  }


  public static function notLike(
    string $key,
    string $value,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();
    $pdoType = $common->checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
    ]);
    return new Where($key, $value, 'NOT LIKE', $pdoType, $common);
  }


  public static function in(
    string $key,
    array $values,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();

    foreach ($values as $value) {
      $common->checkValue($value, $key, [
        SqlValueTypeEnum::STRING,
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
        SqlValueTypeEnum::BOOLEAN,
      ]);
    }
    return new Where($key, $values, 'IN', PDO::PARAM_STR, $common);
  }


  public static function notIn(
    string $key,
    array $values,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();

    foreach ($values as $value) {
      $common->checkValue($value, $key, [
        SqlValueTypeEnum::STRING,
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
        SqlValueTypeEnum::BOOLEAN,
      ]);
    }
    return new Where($key, $values, 'NOT IN', PDO::PARAM_STR, $common);
  }


  public static function between(
    string $key,
    int|float $value1,
    int|float $value2,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();

    foreach ([ $value1, $value2 ] as $value) {
      $common->checkValue($value, $key, [
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
      ]);
    }
    return new Where(
      $key,
      [ $value1, $value2 ],
      'BETWEEN',
      PDO::PARAM_STR,
      $common,
    );
  }


  public static function notBetween(
    string $key,
    int|float $value1,
    int|float $value2,
    ?CommonInterface $common = NULL,
  ): Where {
    $common = $common ?? new Common();

    foreach ([ $value1, $value2 ] as $value) {
      $common->checkValue($value, $key, [
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
      ]);
    }
    return new Where(
      $key,
      [ $value1, $value2 ],
      'NOT BETWEEN',
      PDO::PARAM_STR,
      $common,
    );
  }


  public static function isNull(
    string $key,
    ?CommonInterface $common = NULL,
  ): Where {
    return new Where(
      $key,
      'NULL',
      'IS',
      PDO::PARAM_NULL,
      $common ?? new Common(),
    );
  }


  public static function isNotNull(
    string $key,
    ?CommonInterface $common = NULL,
  ): Where {
    return new Where(
      $key,
      'NULL',
      'IS NOT',
      PDO::PARAM_NULL,
      $common ?? new Common(),
    );
  }


  /**
   * Generate a SQL 'where' clause from an array of conditions
   * @param PDOBindBuilder       $bindBuilder Instance of PDOBindBuilder
   * @param array                $where       Array of conditions
   * @param CommonInterface|null $common      Instance of CommonInterface
   *
   * @return string SQL 'where' clause
   */
  public static function generate(
    PDOBindBuilderInterface $bindBuilder,
    array $where,
    ?CommonInterface $common = NULL,
  ): string {
    $common = $common ?? new Common();

    $whereArr = [];
    foreach($where as $key => $val) {
      $_key = $common->quoteWrap($key);

      // Varias opciones para un sólo campo ==> OR
      if (is_array($val) && !array_key_exists('operator', $val)) {
        $whereArr[] = self::generateComplex($bindBuilder, $key, $val, $common);
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
   * @param PDOBindBuilder       $bindBuilder Instance of PDOBindBuilder
   * @param string               $key         Name of the column
   * @param array                $valueArr    Array of conditions
   * @param CommonInterface|null $common      Instance of CommonInterface
   *
   * @return string SQL 'where' clause
   */
  private static function generateComplex(
    PDOBindBuilder $bindBuilder,
    string $key,
    array $valueArr,
    ?CommonInterface $common = NULL,
  ): string {
    $common = $common ?? new Common();

    $_key = $common->quoteWrap($key);

    $whereArr = [];

    $orClause = [];
    $andClause = [];

    foreach($valueArr as $valValue) {
      // Si el elemento no es otro array..
      if (!is_array($valValue)) {
        $orClause[] = is_null($valValue)
          ? "{$_key} IS " . $bindBuilder->addValue($valValue, PDO::PARAM_NULL)
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
