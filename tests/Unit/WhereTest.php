<?php

use Tribal2\DbHandler\Queries\Where;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Common;

describe('Methods', function () {

  beforeEach(function() {
    $mockedCommon = Mockery::mock(Common::class);
    $mockedCommon->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR);
    $mockedCommon->shouldReceive('quoteWrap')->andReturn('`column`');
    $this->mockedCommon = $mockedCommon;
  });

  test('setKey() should change the column value at any time', function () {
    $clause = Where::equals('column', 'value', $this->mockedCommon);

    $reflection = new ReflectionClass($clause);
    $property = $reflection->getProperty('key');
    $property->setAccessible(TRUE);

    expect($property->getValue($clause))->toEqual('column');

    $clause->setKey('column2');
    expect($property->getValue($clause))->toEqual('column2');
  });

  test('setValue() should change the value at any time', function () {
    $clause = Where::equals('column', 'value', $this->mockedCommon);

    $reflection = new ReflectionClass($clause);
    $property = $reflection->getProperty('value');
    $property->setAccessible(TRUE);

    expect($property->getValue($clause))->toEqual('value');

    $clause->setValue('value2');
    expect($property->getValue($clause))->toEqual('value2');
  });

  test('setPdoType() should change the pdoType at any time', function () {
    $clause = Where::equals('column', 'value', $this->mockedCommon);

    $reflection = new ReflectionClass($clause);
    $property = $reflection->getProperty('pdoType');
    $property->setAccessible(TRUE);

    expect($property->getValue($clause))->toEqual(PDO::PARAM_STR);

    $clause->setPdoType(PDO::PARAM_INT);
    expect($property->getValue($clause))->toEqual(PDO::PARAM_INT);
  });

});


describe('Where::getSql()', function () {

  beforeEach(function() {
    $mockedCommon = Mockery::mock(Common::class);
    $mockedCommon->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR);
    $mockedCommon->shouldReceive('quoteWrap')->andReturn('`column`');
    $this->mockedCommon = $mockedCommon;

    $mockedBindBuilder = Mockery::mock(PDOBindBuilder::class);
    $mockedBindBuilder->shouldReceive('addValueWithPrefix')->andReturn('<BINDED_PLACEHOLDER>');
    $this->bindBuilder = $mockedBindBuilder;
  });

  test('equals creates a correct WhereClause', function () {
    $clause = Where::equals('column', 'value', $this->mockedCommon);
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` = <BINDED_PLACEHOLDER>");
  });

  test('notEquals creates a correct WhereClause', function () {
    $clause = Where::notEquals('column', 'value', $this->mockedCommon);
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` <> <BINDED_PLACEHOLDER>");
  });

  test('equals/notEquals with an array of values', function () {
    $values = [
      'value',
      123,
      TRUE,
    ];

    // EQUALS
    $clauseEqStr = Where::equals('column', $values, $this->mockedCommon)
      ->getSql($this->bindBuilder);

    $expected = "(<BINDED_PLACEHOLDER>, <BINDED_PLACEHOLDER>, <BINDED_PLACEHOLDER>)";
    expect($clauseEqStr)->toEqual("`column` IN {$expected}");

    // NOT EQUALS
    $clauseNotEqStr = Where::notEquals('column', $values, $this->mockedCommon)
      ->getSql($this->bindBuilder);

    expect($clauseNotEqStr)->toEqual("`column` NOT IN {$expected}");
  });

  test('isNull creates a correct WhereClause', function () {
    $clause = Where::isNull('column', $this->mockedCommon);
    expect($clause->getSql($this->bindBuilder))
      ->toEqual("`column` IS <BINDED_PLACEHOLDER>");
  });

  test('isNotNull creates a correct WhereClause', function () {
    $clause = Where::isNotNull('column', $this->mockedCommon);
    expect($clause->getSql($this->bindBuilder))
      ->toEqual("`column` IS NOT <BINDED_PLACEHOLDER>");
  });

  test('like/notLike creates a correct WhereClause', function () {
    $clause = Where::like('column', 'value', $this->mockedCommon);
    expect($clause->getSql($this->bindBuilder))
      ->toEqual("`column` LIKE <BINDED_PLACEHOLDER>");

    $clause2 = Where::notLike('column', 'value', $this->mockedCommon);
    expect($clause2->getSql($this->bindBuilder))
      ->toEqual("`column` NOT LIKE <BINDED_PLACEHOLDER>");
  });

  test('comparison operators', function () {
    $clause = Where::greaterThan('column', 10, $this->mockedCommon);
    expect($clause->getSql($this->bindBuilder))
      ->toEqual("`column` > <BINDED_PLACEHOLDER>");

    $clause2 = Where::greaterThanOrEquals('column', 10, $this->mockedCommon);
    expect($clause2->getSql($this->bindBuilder))
      ->toEqual("`column` >= <BINDED_PLACEHOLDER>");

    $clause3 = Where::lessThan('column', 10, $this->mockedCommon);
    expect($clause3->getSql($this->bindBuilder))
      ->toEqual("`column` < <BINDED_PLACEHOLDER>");

    $clause4 = Where::lessThanOrEquals('column', 10, $this->mockedCommon);
    expect($clause4->getSql($this->bindBuilder))
      ->toEqual("`column` <= <BINDED_PLACEHOLDER>");
  });

  test('private static method numericComparison()', function() {
    $reflection = new ReflectionClass(Where::class);
    $method = $reflection->getMethod('numericComparison');
    $method->setAccessible(TRUE);

    $clause = $method->invokeArgs(NULL, ['column', 10, '>', $this->mockedCommon]);
    expect($clause->getSql($this->bindBuilder))
      ->toEqual("`column` > <BINDED_PLACEHOLDER>");

    $clause2 = $method->invokeArgs(NULL, ['column', 10, '>=', $this->mockedCommon]);
    expect($clause2->getSql($this->bindBuilder))
      ->toEqual("`column` >= <BINDED_PLACEHOLDER>");

    $clause3 = $method->invokeArgs(NULL, ['column', 10, '<', $this->mockedCommon]);
    expect($clause3->getSql($this->bindBuilder))
      ->toEqual("`column` < <BINDED_PLACEHOLDER>");

    $clause4 = $method->invokeArgs(NULL, ['column', 10, '<=', $this->mockedCommon]);
    expect($clause4->getSql($this->bindBuilder))
      ->toEqual("`column` <= <BINDED_PLACEHOLDER>");
  });

});


describe('Where::getSqlForArrayOfValues()', function () {

  beforeEach(function() {
    $mockedCommon = Mockery::mock(Common::class);
    $mockedCommon->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR);
    $mockedCommon->shouldReceive('quoteWrap')->andReturn('`column`');
    $this->mockedCommon = $mockedCommon;

    $mockedBindBuilder = Mockery::mock(PDOBindBuilder::class);
    $mockedBindBuilder->shouldReceive('addValueWithPrefix')->andReturn('<BINDED_PLACEHOLDER>');
    $this->bindBuilder = $mockedBindBuilder;
  });

  test('in creates a correct WhereClause for an array of values', function () {
    $clause = Where::in('column', ['value1', 'value2'], $this->mockedCommon);
    expect($clause->getSql($this->bindBuilder))
      ->toEqual("`column` IN (<BINDED_PLACEHOLDER>, <BINDED_PLACEHOLDER>)");

    $clause2 = Where::notIn('column', ['value1', 'value2'], $this->mockedCommon);
    expect($clause2->getSql($this->bindBuilder))
      ->toEqual("`column` NOT IN (<BINDED_PLACEHOLDER>, <BINDED_PLACEHOLDER>)");
  });

  test('between creates a correct WhereClause for range values', function () {
    $clause = Where::between('column', 10, 20, $this->mockedCommon);
    expect($clause->getSql($this->bindBuilder))
      ->toEqual("`column` BETWEEN <BINDED_PLACEHOLDER> AND <BINDED_PLACEHOLDER>");

    $clause2 = Where::notBetween('column', 10, 20, $this->mockedCommon);
    expect($clause2->getSql($this->bindBuilder))
      ->toEqual("`column` NOT BETWEEN <BINDED_PLACEHOLDER> AND <BINDED_PLACEHOLDER>");
  });

});


describe('Where::getSqlForArrayOfWhereClauses()', function () {

  beforeEach(function() {
    $mockedCommon = Mockery::mock(Common::class);
    $mockedCommon->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR);
    $this->mockedCommon = $mockedCommon;

    $mockedBindBuilder = Mockery::mock(PDOBindBuilder::class);
    $mockedBindBuilder->shouldReceive('addValueWithPrefix')->andReturn('<BINDED_PLACEHOLDER>');
    $this->bindBuilder = $mockedBindBuilder;
  });

  test('AND and getSql', function () {
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column1')->andReturn('`column1`');
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column2')->andReturn('`column2`');
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column3')->andReturn('`column3`');

    $clauseAnd = Where::and(
      Where::equals('column1', 'value1', $this->mockedCommon),
      Where::greaterThan('column2', 1, $this->mockedCommon),
      Where::lessThanOrEquals('column3', 2, $this->mockedCommon),
    );

    $expectedSql = ""
      . "("
      .   "`column1` = <BINDED_PLACEHOLDER>"
      .   " AND "
      .   "`column2` > <BINDED_PLACEHOLDER>"
      .   " AND "
      .   "`column3` <= <BINDED_PLACEHOLDER>"
      . ")";

    expect($clauseAnd->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

  test('OR and getSqlForArrayOfValues', function () {
    $this->mockedCommon->shouldReceive('quoteWrap')->andReturn('`column`');

    $clauseOr = Where::or(
      Where::in('column', ['value1', 'value2'], $this->mockedCommon),
      Where::notIn('column', ['value3', 'value4'], $this->mockedCommon),
    );

    $expectedSql = ""
      . "("
      .   "`column` IN (<BINDED_PLACEHOLDER>, <BINDED_PLACEHOLDER>)"
      .   " OR "
      .   "`column` NOT IN (<BINDED_PLACEHOLDER>, <BINDED_PLACEHOLDER>)"
      . ")";
    expect($clauseOr->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

  test('mix of AND / OR', function () {
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column1')->andReturn('`column1`');
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column2')->andReturn('`column2`');

    $clauseOr = Where::or(
      Where::and(
        Where::greaterThan('column1', 1, $this->mockedCommon),
        Where::lessThanOrEquals('column1', 5, $this->mockedCommon),
      ),
      Where::and(
        Where::greaterThan('column2', 1, $this->mockedCommon),
        Where::lessThanOrEquals('column2', 5, $this->mockedCommon),
      ),
    );

    $expectedSql = ""
      . "("
      .   "("
      .     "`column1` > <BINDED_PLACEHOLDER>"
      .     " AND "
      .     "`column1` <= <BINDED_PLACEHOLDER>"
      .   ")"
      .   " OR "
      .   "("
      .     "`column2` > <BINDED_PLACEHOLDER>"
      .     " AND "
      .     "`column2` <= <BINDED_PLACEHOLDER>"
      .   ")"
      . ")";
    expect($clauseOr->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

  test('nested ANDs', function () {
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column1')->andReturn('`column1`');
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column2')->andReturn('`column2`');
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column3')->andReturn('`column3`');
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column4')->andReturn('`column4`');

    $clauseAnd = Where::and(
      Where::equals('column1', 'value1', $this->mockedCommon),
      Where::and(
        Where::greaterThan('column2', 1, $this->mockedCommon),
        Where::and(
          Where::lessThanOrEquals('column3', 2, $this->mockedCommon),
          Where::equals('column4', 'value4', $this->mockedCommon),
        ),
      ),
    );

    $expectedSql = ""
      . "("
      .   "`column1` = <BINDED_PLACEHOLDER>"
      .   " AND "
      .   "("
      .     "`column2` > <BINDED_PLACEHOLDER>"
      .     " AND "
      .     "("
      .       "`column3` <= <BINDED_PLACEHOLDER>"
      .       " AND "
      .       "`column4` = <BINDED_PLACEHOLDER>"
      .     ")"
      .   ")"
      . ")";

    expect($clauseAnd->getSql($this->bindBuilder))->toEqual($expectedSql);
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

  $generateComplex = function ($key, $valueArr) {
    $mockedCommon = Mockery::mock(Common::class);
    $mockedCommon->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR);
    $mockedCommon->shouldReceive('quoteWrap')->andReturn('`column`');

    $mockedBindBuilder = Mockery::mock(PDOBindBuilder::class);
    $mockedBindBuilder->shouldReceive('addValueWithPrefix')->andReturn('<BINDED_PLACEHOLDER>');
    $mockedBindBuilder->shouldReceive('addValue')->andReturn('<BINDED_PLACEHOLDER>');

    $reflection = new ReflectionClass(Where::class);
    $method = $reflection->getMethod('generateComplex');
    $method->setAccessible(TRUE);

    return $method->invoke(NULL, $mockedBindBuilder, $key, $valueArr, $mockedCommon);
  };

  test('generates simple OR clause', function() use ($generateComplex) {
    $key = 'column';
    $valueArr = ['value1', 'value2'];

    $clause = $generateComplex($key, $valueArr);

    expect($clause)->toBe("(`column` LIKE <BINDED_PLACEHOLDER> OR `column` LIKE <BINDED_PLACEHOLDER>)");
  });

  test('generates OR clause with NULL', function() use ($generateComplex) {
      $key = 'column';
      $valueArr = [NULL, 'value2'];

      $clause = $generateComplex($key, $valueArr);

      expect($clause)->toBe("(`column` IS <BINDED_PLACEHOLDER> OR `column` LIKE <BINDED_PLACEHOLDER>)");
  });

  test('generates complex AND and OR clauses', function() use ($generateComplex) {
      $key = 'column';
      $valueArr = [
          ['operator' => '>', 'value' => 1, 'and' => TRUE],
          ['operator' => '<', 'value' => 2, 'and' => TRUE],
          ['operator' => '=', 'value' => 3],
          ['operator' => '=', 'value' => 4],
      ];

      $clause = $generateComplex($key, $valueArr);

      $expected = ""
        . "(`column` = <BINDED_PLACEHOLDER> OR `column` = <BINDED_PLACEHOLDER>) "
        . "AND (`column` > <BINDED_PLACEHOLDER> AND `column` < <BINDED_PLACEHOLDER>)";

      expect($clause)->toBe($expected);
  });

  test('throws an exception for invalid operator', function() use ($generateComplex) {
      $key = 'column';
      $valueArr = [
          ['operator' => 'INVALID_OPERATOR', 'value' => 5]
      ];

      $generateComplex($key, $valueArr);
  })->throws(Exception::class);

});

describe('Where::generate()', function () {

  beforeEach(function() {
    $mockedCommon = Mockery::mock(Common::class);
    $mockedCommon->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR);
    $this->mockedCommon = $mockedCommon;

    $mockedBindBuilder = Mockery::mock(PDOBindBuilder::class);
    $mockedBindBuilder->shouldReceive('addValueWithPrefix')->andReturn('<BINDED_PLACEHOLDER>');
    $mockedBindBuilder->shouldReceive('addValue')->andReturn('<BINDED_PLACEHOLDER>');
    $this->bindBuilder = $mockedBindBuilder;
  });

  test('generates simple WHERE clause for single condition', function() {
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column')->andReturn('`column`');

    $where = ['column' => 'value1'];

    $clause = Where::generate($this->bindBuilder, $where, $this->mockedCommon);

    expect($clause)->toBe("`column` LIKE <BINDED_PLACEHOLDER>");
  });

  test('generates WHERE clause for multiple conditions', function() {
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column1')->andReturn('`column1`');
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column2')->andReturn('`column2`');

    $where = [
      'column1' => 'value1',
      'column2' => 'value2'
    ];

    $clause = Where::generate($this->bindBuilder, $where, $this->mockedCommon);

    $expect = "`column1` LIKE <BINDED_PLACEHOLDER> AND "
      . "`column2` LIKE <BINDED_PLACEHOLDER>";

    expect($clause)->toBe($expect);
  });

  test('generates WHERE clause for NULL value', function() {
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column')->andReturn('`column`');

    $where = ['column' => NULL];
    $clause = Where::generate($this->bindBuilder, $where, $this->mockedCommon);

    expect($clause)->toBe("`column` IS NULL");
  });

  test('generates WHERE clause with custom operator', function() {
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column')->andReturn('`column`');

    $where = [
      'column' => [
        'operator' => '>',
        'value' => 5
      ]
    ];

    $clause = Where::generate($this->bindBuilder, $where, $this->mockedCommon);

    expect($clause)->toBe("`column` > <BINDED_PLACEHOLDER>");
  });

  test('generates complex WHERE clause with multiple conditions', function() {
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column1')->andReturn('`column1`');
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column2')->andReturn('`column2`');

    $where = [
      'column1' => ['value1', 'value2'],
      'column2' => [
        ['operator' => '>', 'value' => 5],
        ['operator' => '<', 'value' => 10]
      ]
    ];

    $clause = Where::generate($this->bindBuilder, $where, $this->mockedCommon);

    $expect = ''
      . "("
      .   "`column1` LIKE <BINDED_PLACEHOLDER> "
      .   "OR `column1` LIKE <BINDED_PLACEHOLDER>"
      . ") AND ("
      .   "`column2` > <BINDED_PLACEHOLDER> "
      .   "OR `column2` < <BINDED_PLACEHOLDER>"
      . ")";

    expect($clause)->toBe($expect);
  });

  test('throws an exception for invalid operator', function() {
    $this->mockedCommon->shouldReceive('quoteWrap')->with('column')->andReturn('`column`');

    $where = ['column' => ['operator' => 'INVALID_OPERATOR', 'value' => 5]];

    Where::generate($this->bindBuilder, $where, $this->mockedCommon);
  })->throws(Exception::class);

});
