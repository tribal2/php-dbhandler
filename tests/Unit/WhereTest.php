<?php

use Tribal2\DbHandler\Queries\Where;
use Tribal2\DbHandler\PDOBindBuilder;

describe('Utils', function () {
  test('validateOperator() - with valid values', function() {
    $validOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
    $reflection = new ReflectionClass(Where::class);
    $method = $reflection->getMethod('validateOperator');
    $method->setAccessible(TRUE);

    foreach ($validOperators as $operator) {
        $result = $method->invoke($this->db, $operator);
        expect($result)->toBe($operator);
    }
  });

  test('validateOperator() - with invalid value', function() {
    $reflection = new ReflectionClass(Where::class);
    $method = $reflection->getMethod('validateOperator');
    $method->setAccessible(TRUE);

    $invalidOperator = 'INVALID_OPERATOR';
    $method->invoke($this->db, $invalidOperator);
  })->throws(Exception::class, "El operador 'INVALID_OPERATOR' no es válido.");

});

describe('Where::generateComplex()', function () {

  test('generates simple OR clause', function() {
    $bindBuilder = new PDOBindBuilder();
    $key = 'column_name';
    $valueArr = ['value1', 'value2'];

    $clause = Where::generateComplex($bindBuilder, $key, $valueArr);

    expect($clause)->toBe("(`column_name` LIKE :placeholder___1 OR `column_name` LIKE :placeholder___2)");

    $binds = $bindBuilder->getValues();
    expect($binds[':placeholder___1']['value'])->toBe('value1');
    expect($binds[':placeholder___2']['value'])->toBe('value2');
  });

  test('generates OR clause with NULL', function() {
      $bindBuilder = new PDOBindBuilder();
      $key = 'column_name';
      $valueArr = [NULL, 'value2'];

      $clause = Where::generateComplex($bindBuilder, $key, $valueArr);

      expect($clause)->toBe("(`column_name` IS NULL OR `column_name` LIKE :placeholder___1)");

      $binds = $bindBuilder->getValues();
      expect($binds[':placeholder___1']['value'])->toBe('value2');
  });

  test('generates complex AND and OR clauses', function() {
      $bindBuilder = new PDOBindBuilder();
      $key = 'column_name';
      $valueArr = [
          ['operator' => '>', 'value' => 1, 'and' => TRUE],
          ['operator' => '<', 'value' => 2, 'and' => TRUE],
          ['operator' => '=', 'value' => 3],
          ['operator' => '=', 'value' => 4],
      ];

      $clause = Where::generateComplex($bindBuilder, $key, $valueArr);

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

  test('throws an exception for invalid operator', function() {
      $bindBuilder = new PDOBindBuilder();
      $key = 'column_name';
      $valueArr = [
          ['operator' => 'INVALID_OPERATOR', 'value' => 5]
      ];

      Where::generateComplex($bindBuilder, $key, $valueArr);
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
