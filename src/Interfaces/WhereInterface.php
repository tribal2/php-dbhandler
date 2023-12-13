<?php

namespace Tribal2\DbHandler\Interfaces;

use PDO;

interface WhereInterface {


  public function __construct(
    string $key,
    mixed $value,
    string $operator = '=',
    int $pdoType = PDO::PARAM_STR,
    ?CommonInterface $common = NULL,
  );


  public function getSql(PDOBindBuilderInterface $bindBuilder): string;


  public static function or(WhereInterface ...$whereClauses): WhereInterface;


  public static function and(WhereInterface ...$whereClauses): WhereInterface;


  public static function equals(
    string $key,
    mixed $value,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function notEquals(
    string $key,
    mixed $value,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function greaterThan(
    string $key,
    mixed $value,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function greaterThanOrEquals(
    string $key,
    int|float $value,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function lessThan(
    string $key,
    int|float $value,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function lessThanOrEquals(
    string $key,
    int|float $value,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function like(
    string $key,
    string $value,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function notLike(
    string $key,
    string $value,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function in(
    string $key,
    array $values,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function notIn(
    string $key,
    array $values,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function between(
    string $key,
    int|float $value1,
    int|float $value2,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function notBetween(
    string $key,
    int|float $value1,
    int|float $value2,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function isNull(
    string $key,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function isNotNull(
    string $key,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function generate(
    PDOBindBuilderInterface $bindBuilder,
    array $where,
    ?CommonInterface $common = NULL,
  ): string;


}
