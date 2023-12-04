<?php

use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\Queries\Select;

describe('SELECT builder', function () {

  test('static factory', function () {
    $select = new Select(
      'my_table',
      Mockery::mock(PDO::class),
      Mockery::mock(CommonInterface::class),
    );
    expect($select)->toBeInstanceOf(Select::class);
  });

});


describe('SQL', function () {

  beforeEach(function () {
    $mockCommon = Mockery::mock(CommonInterface::class, [
      'checkValue' => PDO::PARAM_STR,
      'quoteWrap' => '<WRAPPED_VALUE>',
      'parseColumns' => '<COLUMNS>',
    ]);

    $this->select = new Select(
      'my_table',
      Mockery::mock(PDO::class),
      $mockCommon,
    );

    $this->mockBindBuilder = Mockery::mock(PDOBindBuilderInterface::class, [
      'addValueWithPrefix' => '<BINDED_VALUE>',
    ]);

    $this->mockWhere = Mockery::mock(WhereInterface::class, ['getSql' => '<WHERE>']);
  });

  test('all columns from a table', function () {
    $sql = $this->select->getSql();

    expect($sql)->toBe('SELECT * FROM <WRAPPED_VALUE>;');
  });

  test('throws on invalid column name type', function () {
    $this->select
      ->columns(['column1', 1234, TRUE]);
  })->throws(\Exception::class);

  test('first 5 records of table with column1 and column2', function () {
    $sql = $this->select
      ->columns(['column1', 'column2'])
      ->limit(5)
      ->getSql($this->mockBindBuilder);

    $expected = 'SELECT <COLUMNS> FROM <WRAPPED_VALUE> LIMIT <BINDED_VALUE>;';
    expect($sql)->toBe($expected);
  });

  test('with where', function () {
    $sql = $this->select
      ->columns(['column1', 'column2'])
      ->where($this->mockWhere)
      ->getSql($this->mockBindBuilder);

    $expected = 'SELECT <COLUMNS> FROM <WRAPPED_VALUE> WHERE <WHERE>;';
    expect($sql)->toBe($expected);
  });

  test('with grouping', function () {
    $sql = $this->select
      ->column('column1')
      ->column('sum(column2)')
      ->groupBy('column1')
      ->having($this->mockWhere)
      ->getSql($this->mockBindBuilder);

    $expected = [
      'SELECT <COLUMNS>',
      'FROM <WRAPPED_VALUE>',
      'GROUP BY <COLUMNS>',
      'HAVING <WHERE>;',
    ];
    expect($sql)->toBe(implode(' ', $expected));
  });

  test('with all options', function () {
    $sql = $this->select
      ->column('column1')
      ->column('sum(column2)')
      ->where($this->mockWhere)
      ->groupBy('column1')
      ->having($this->mockWhere)
      ->limit(5)
      ->getSql($this->mockBindBuilder);

    $expected = [
      'SELECT <COLUMNS>',
      'FROM <WRAPPED_VALUE>',
      'WHERE <WHERE>',
      'GROUP BY <COLUMNS>',
      'HAVING <WHERE>',
      'LIMIT <BINDED_VALUE>;',
    ];
    expect($sql)->toBe(implode(' ', $expected));
  });
});
