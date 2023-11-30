<?php

namespace Tribal2\DbHandler\Interfaces;

use Tribal2\DbHandler\PDOBindBuilder;

interface WhereInterface {


  public function getSql(PDOBindBuilder $bindBuilder): string;


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
    $value,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function lessThan(
    string $key,
    $value,
    ?CommonInterface $common = NULL,
  ): WhereInterface;


  public static function lessThanOrEquals(
    string $key,
    $value,
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
    PDOBindBuilder $bindBuilder,
    array $where,
    ?CommonInterface $common = NULL,
  ): string;


}
