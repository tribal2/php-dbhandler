<?php

use Psr\SimpleCache\CacheInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\Queries\Select;

describe('SELECT builder', function () {

  test('static factory', function () {
    $select = new Select(
      Mockery::mock(PDOWrapperInterface::class),
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

    $this->select = Select::_from(
      'my_table',
      Mockery::mock(PDOWrapperInterface::class),
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


describe('Caching', function () {

  beforeEach(function () {
    $mockCommon = Mockery::mock(CommonInterface::class, [
      'checkValue' => PDO::PARAM_STR,
      'quoteWrap' => '<WRAPPED_VALUE>',
      'parseColumns' => '<COLUMNS>',
    ]);

    $this->queryResult = [
      (object)['column1' => 'value1'],
      (object)['column1' => 'value2'],
    ];

    $mockPDOStatement = Mockery::mock(PDOStatement::class, [
      'fetchAll' => $this->queryResult,
    ]);

    $mockPDOWrapper = Mockery::mock(PDOWrapperInterface::class, [
      'execute' => $mockPDOStatement,
    ]);

    $this->select = Select::_from(
      'my_table',
      $mockPDOWrapper,
      $mockCommon,
    );

    // Set cache
    $this->mockCache = Mockery::mock(CacheInterface::class);
  });

  test('it should throw when cache is not set', function () {
    $this->select->withCache();
  })->throws(Exception::class);

  test('use cache when set', function () {
    $this->select->setCache($this->mockCache);
    $this->mockCache->shouldReceive('has')->once()->andReturn(TRUE);
    $this->mockCache->shouldReceive('get')->once()->andReturn([]);

    $results = $this->select
      ->withCache()
      ->fetchAll();

    expect($results)
      ->toBeArray()
      ->toHaveLength(0);
  });

  test('set cache when is not set', function () {
    $this->select->setCache($this->mockCache);

    /**
     * Cache is not set
     */
    $this->mockCache->shouldReceive('has')->once()->andReturn(FALSE);
    // Cache is set
    $this->mockCache->shouldReceive('set')->once()->andReturn(TRUE);

    $results = $this->select
      ->withCache()
      ->fetchAll();

    expect($results)
      ->toBeArray()
      ->toHaveLength(2);

    /**
     * Cache is set
     */
    $this->mockCache->shouldReceive('has')->once()->andReturn(TRUE);
    $this->mockCache->shouldReceive('get')->once()->andReturn($this->queryResult);

    $results2 = $this->select
      ->withCache()
      ->fetchAll();

    expect($results2)
      ->toBeArray()
      ->toHaveLength(2);
  });
});
