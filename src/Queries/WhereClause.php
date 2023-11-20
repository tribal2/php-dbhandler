<?php

namespace Tribal2\DbHandler\Queries;

use PDO;
use Tribal2\DbHandler\Enums\SqlValueTypeEnum;
use Tribal2\DbHandler\PDOBindBuilder;

class WhereClause {


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


  public static function equals(string $key, $value): WhereClause {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
      SqlValueTypeEnum::BOOLEAN,
    ]);
    return new WhereClause($key, $value, '=', $pdoType);
  }


  public static function notEquals(string $key, $value): WhereClause {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
      SqlValueTypeEnum::BOOLEAN,
    ]);
    return new WhereClause($key, $value, '<>', $pdoType);
  }


  public static function greaterThan(string $key, $value): WhereClause {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new WhereClause($key, $value, '>', $pdoType);
  }


  public static function greaterThanOrEquals(string $key, $value): WhereClause {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new WhereClause($key, $value, '>=', $pdoType);
  }


  public static function lessThan(string $key, $value): WhereClause {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new WhereClause($key, $value, '<', $pdoType);
  }


  public static function lessThanOrEquals(string $key, $value): WhereClause {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::FLOAT,
      SqlValueTypeEnum::INTEGER,
    ]);
    return new WhereClause($key, $value, '<=', $pdoType);
  }


  public static function like(string $key, $value): WhereClause {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
    ]);
    return new WhereClause($key, $value, 'LIKE', $pdoType);
  }


  public static function notLike(string $key, $value): WhereClause {
    $pdoType = Common::checkValue($value, $key, [
      SqlValueTypeEnum::STRING,
    ]);
    return new WhereClause($key, $value, 'NOT LIKE', $pdoType);
  }


  public static function in(string $key, array $values): WhereClause {
    foreach ($values as $value) {
      Common::checkValue($value, $key, [
        SqlValueTypeEnum::STRING,
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
        SqlValueTypeEnum::BOOLEAN,
      ]);
    }
    return new WhereClause($key, $values, 'IN');
  }


  public static function notIn(string $key, array $values): WhereClause {
    foreach ($values as $value) {
      Common::checkValue($value, $key, [
        SqlValueTypeEnum::STRING,
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
        SqlValueTypeEnum::BOOLEAN,
      ]);
    }
    return new WhereClause($key, $values, 'NOT IN');
  }


  public static function between(string $key, $value1, $value2): WhereClause {
    foreach ([ $value1, $value2 ] as $value) {
      Common::checkValue($value, $key, [
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
      ]);
    }
    return new WhereClause($key, [ $value1, $value2 ], 'BETWEEN');
  }


  public static function notBetween(string $key, $value1, $value2): WhereClause {
    foreach ([ $value1, $value2 ] as $value) {
      Common::checkValue($value, $key, [
        SqlValueTypeEnum::FLOAT,
        SqlValueTypeEnum::INTEGER,
      ]);
    }
    return new WhereClause($key, [ $value1, $value2 ], 'NOT BETWEEN');
  }


  public static function isNull(string $key): WhereClause {
    return new WhereClause($key, 'NULL', 'IS', PDO::PARAM_NULL);
  }


  public static function isNotNull(string $key): WhereClause {
    return new WhereClause($key, 'NULL', 'IS NOT', PDO::PARAM_NULL);
  }


}
