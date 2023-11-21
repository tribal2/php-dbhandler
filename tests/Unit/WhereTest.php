<?php

use Tribal2\DbHandler\Queries\Where;
use Tribal2\DbHandler\PDOBindBuilder;

describe('Where::getSql()', function () {

  beforeEach(function() {
    $this->bindBuilder = new PDOBindBuilder();
  });

  test('equals creates a correct WhereClause', function () {
    $clause = Where::equals('column', 'value');
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` = :column___1");
  });

  test('notEquals creates a correct WhereClause', function () {
    $clause = Where::notEquals('column', 'value');
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` <> :column___1");
  });

  test('isNull creates a correct WhereClause', function () {
    $clause = Where::isNull('column');
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` IS :column___1");
  });

  test('isNotNull creates a correct WhereClause', function () {
    $clause = Where::isNotNull('column');
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` IS NOT :column___1");
  });

});


describe('Where::getSqlForArrayOfValues()', function () {

  beforeEach(function() {
    $this->bindBuilder = new PDOBindBuilder();
  });

  test('in creates a correct WhereClause for an array of values', function () {
    $clause = Where::in('column', ['value1', 'value2']);
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` IN (:column___1, :column___2)");

    $clause2 = Where::notIn('column', ['value1', 'value2']);
    expect($clause2->getSql($this->bindBuilder))->toEqual("`column` NOT IN (:column___3, :column___4)");
  });

  test('between creates a correct WhereClause for range values', function () {
    $clause = Where::between('column', 10, 20);
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` BETWEEN :column___1 AND :column___2");

    $clause2 = Where::notBetween('column', 10, 20);
    expect($clause2->getSql($this->bindBuilder))->toEqual("`column` NOT BETWEEN :column___3 AND :column___4");
  });

});


describe('Where::getSqlForArrayOfWhereClauses()', function () {

  beforeEach(function() {
    $this->bindBuilder = new PDOBindBuilder();
  });

  test('AND and getSql', function () {
    $clauseAnd = Where::and(
      Where::equals('column1', 'value1'),
      Where::greaterThan('column2', 1),
      Where::lessThanOrEquals('column3', 2),
    );

    $expectedSql = ""
      . "("
      .   "`column1` = :column1___1"
      .   " AND "
      .   "`column2` > :column2___1"
      .   " AND "
      .   "`column3` <= :column3___1"
      . ")";

    expect($clauseAnd->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

  test('OR and getSqlForArrayOfValues', function () {
    $clauseOr = Where::or(
      Where::in('column', ['value1', 'value2']),
      Where::notIn('column', ['value3', 'value4']),
    );

    $expectedSql = ""
      . "("
      .   "`column` IN (:column___1, :column___2)"
      .   " OR "
      .   "`column` NOT IN (:column___3, :column___4)"
      . ")";
    expect($clauseOr->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

  test('mix of AND / OR', function () {
    $clauseOr = Where::or(
      Where::and(
        Where::greaterThan('column1', 1),
        Where::lessThanOrEquals('column1', 5),
      ),
      Where::and(
        Where::greaterThan('column2', 1),
        Where::lessThanOrEquals('column2', 5),
      ),
    );

    $expectedSql = ""
      . "("
      .   "("
      .     "`column1` > :column1___1"
      .     " AND "
      .     "`column1` <= :column1___2"
      .   ")"
      .   " OR "
      .   "("
      .     "`column2` > :column2___1"
      .     " AND "
      .     "`column2` <= :column2___2"
      .   ")"
      . ")";
    expect($clauseOr->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

  test('nested ANDs', function () {
    $clauseAnd = Where::and(
      Where::equals('column1', 'value1'),
      Where::and(
        Where::greaterThan('column2', 1),
        Where::and(
          Where::lessThanOrEquals('column3', 2),
          Where::equals('column4', 'value4'),
        ),
      ),
    );

    $expectedSql = ""
      . "("
      .   "`column1` = :column1___1"
      .   " AND "
      .   "("
      .     "`column2` > :column2___1"
      .     " AND "
      .     "("
      .       "`column3` <= :column3___1"
      .       " AND "
      .       "`column4` = :column4___1"
      .     ")"
      .   ")"
      . ")";

    expect($clauseAnd->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

});


describe('Where::verify sql after binding values', function () {

  beforeEach(function() {
    $this->bindBuilder = new PDOBindBuilder();
  });

  test('numeric values', function () {
    $clause = Where::in('column', [100, 200]);
    $sql = $clause->getSql($this->bindBuilder);

    expect($sql)->toEqual("`column` IN (:column___1, :column___2)");
    expect($this->bindBuilder->debugQuery($sql))->toEqual("`column` IN (100, 200)");
  });

  test('string values', function () {
    $clause = Where::in('column', ['first', 'second']);
    $sql = $clause->getSql($this->bindBuilder);

    expect($sql)->toEqual("`column` IN (:column___1, :column___2)");
    expect($this->bindBuilder->debugQuery($sql))->toEqual("`column` IN ('first', 'second')");
  });

});


describe('Where::validateOperator()', function () {

  $validateOperator = function ($operator) {
    $reflection = new ReflectionClass(Where::class);
    $method = $reflection->getMethod('validateOperator');
    $method->setAccessible(TRUE);

    return $method->invoke(NULL, $operator);
  };

  test('validateOperator() - with valid values', function() use ($validateOperator) {
    $validOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];

    foreach ($validOperators as $operator) {
        $result = $validateOperator($operator);
        expect($result)->toBe($operator);
    }
  });

  test('validateOperator() - with invalid value', function() use ($validateOperator) {
    $validateOperator('INVALID_OPERATOR');
  })->throws(Exception::class, "El operador 'INVALID_OPERATOR' no es válido.");

});

describe('Where::generateComplex()', function () {

  $generateComplex = function ($bindBuilder, $key, $valueArr) {
    $reflection = new ReflectionClass(Where::class);
    $method = $reflection->getMethod('generateComplex');
    $method->setAccessible(TRUE);

    return $method->invoke(NULL, $bindBuilder, $key, $valueArr, $method);
  };

  test('generates simple OR clause', function() use ($generateComplex) {
    $bindBuilder = new PDOBindBuilder();
    $key = 'column_name';
    $valueArr = ['value1', 'value2'];

    $clause = $generateComplex($bindBuilder, $key, $valueArr);

    expect($clause)->toBe("(`column_name` LIKE :placeholder___1 OR `column_name` LIKE :placeholder___2)");

    $binds = $bindBuilder->getValues();
    expect($binds[':placeholder___1']['value'])->toBe('value1');
    expect($binds[':placeholder___2']['value'])->toBe('value2');
  });

  test('generates OR clause with NULL', function() use ($generateComplex) {
      $bindBuilder = new PDOBindBuilder();
      $key = 'column_name';
      $valueArr = [NULL, 'value2'];

      $clause = $generateComplex($bindBuilder, $key, $valueArr);

      expect($clause)->toBe("(`column_name` IS NULL OR `column_name` LIKE :placeholder___1)");

      $binds = $bindBuilder->getValues();
      expect($binds[':placeholder___1']['value'])->toBe('value2');
  });

  test('generates complex AND and OR clauses', function() use ($generateComplex) {
      $bindBuilder = new PDOBindBuilder();
      $key = 'column_name';
      $valueArr = [
          ['operator' => '>', 'value' => 1, 'and' => TRUE],
          ['operator' => '<', 'value' => 2, 'and' => TRUE],
          ['operator' => '=', 'value' => 3],
          ['operator' => '=', 'value' => 4],
      ];

      $clause = $generateComplex($bindBuilder, $key, $valueArr);

      $expected = ""
        . "(`column_name` = :placeholder___3 OR `column_name` = :placeholder___4) "
        . "AND (`column_name` > :placeholder___1 AND `column_name` < :placeholder___2)";

      expect($clause)->toBe($expected);

      $binds = $bindBuilder->getValues();
      expect($binds[':placeholder___1']['value'])->toBe(1);
      expect($binds[':placeholder___2']['value'])->toBe(2);
      expect($binds[':placeholder___3']['value'])->toBe(3);
      expect($binds[':placeholder___4']['value'])->toBe(4);
  });

  test('throws an exception for invalid operator', function() use ($generateComplex) {
      $bindBuilder = new PDOBindBuilder();
      $key = 'column_name';
      $valueArr = [
          ['operator' => 'INVALID_OPERATOR', 'value' => 5]
      ];

      $generateComplex($bindBuilder, $key, $valueArr);
  })->throws(Exception::class);

});

describe('Where::generate()', function () {

  test('generates simple WHERE clause for single condition', function() {
    $bindBuilder = new PDOBindBuilder();
    $where = ['column_name' => 'value1'];

    $clause = Where::generate($bindBuilder, $where);

    expect($clause)->toBe("`column_name` LIKE :placeholder___1");
    $binds = $bindBuilder->getValues();
    expect($binds[':placeholder___1']['value'])->toBe('value1');
  });

  test('generates WHERE clause for multiple conditions', function() {
      $bindBuilder = new PDOBindBuilder();
      $where = [
          'column_name1' => 'value1',
          'column_name2' => 'value2'
      ];

      $clause = Where::generate($bindBuilder, $where);

      $expect = "`column_name1` LIKE :placeholder___1 AND "
       . "`column_name2` LIKE :placeholder___2";

      expect($clause)->toBe($expect);
      $binds = $bindBuilder->getValues();
      expect($binds[':placeholder___1']['value'])->toBe('value1');
      expect($binds[':placeholder___2']['value'])->toBe('value2');
  });

  test('generates WHERE clause for NULL value', function() {
      $bindBuilder = new PDOBindBuilder();
      $where = ['column_name' => NULL];

      $clause = Where::generate($bindBuilder, $where);

      expect($clause)->toBe("`column_name` IS NULL");
  });

  test('generates WHERE clause with custom operator', function() {
      $bindBuilder = new PDOBindBuilder();
      $where = [
          'column_name' => [
              'operator' => '>',
              'value' => 5
          ]
      ];

      $clause = Where::generate($bindBuilder, $where);

      expect($clause)->toBe("`column_name` > :placeholder___1");
      $binds = $bindBuilder->getValues();
      expect($binds[':placeholder___1']['value'])->toBe(5);
  });

  test('generates complex WHERE clause with multiple conditions', function() {
      $bindBuilder = new PDOBindBuilder();
      $where = [
          'column_name1' => ['value1', 'value2'],
          'column_name2' => [
              ['operator' => '>', 'value' => 5],
              ['operator' => '<', 'value' => 10]
          ]
      ];

      $clause = Where::generate($bindBuilder, $where);

      $expect = ''
        . "("
        .   "`column_name1` LIKE :placeholder___1 "
        .   "OR `column_name1` LIKE :placeholder___2"
        . ") AND ("
        .   "`column_name2` > :placeholder___3 "
        .   "OR `column_name2` < :placeholder___4"
        . ")";

      expect($clause)->toBe($expect);
      $binds = $bindBuilder->getValues();
      expect($binds[':placeholder___1']['value'])->toBe('value1');
      expect($binds[':placeholder___2']['value'])->toBe('value2');
      expect($binds[':placeholder___3']['value'])->toBe(5);
      expect($binds[':placeholder___4']['value'])->toBe(10);
  });

  test('throws an exception for invalid operator', function() {
      $bindBuilder = new PDOBindBuilder();
      $where = ['column_name' => ['operator' => 'INVALID_OPERATOR', 'value' => 5]];

      Where::generate($bindBuilder, $where);

  })->throws(Exception::class);

});
