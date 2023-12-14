<?php

use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\FetchPaginatedResultInterface;
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

    expect($results->data)
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

    expect($results->data)
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

    expect($results2->data)
      ->toBeArray()
      ->toHaveLength(2);
  });
});


describe('Pagination', function () {

  beforeEach(function () {
    $mockCommon = Mockery::mock(CommonInterface::class, [
      'checkValue' => PDO::PARAM_STR,
      'quoteWrap' => '<WRAPPED_VALUE>',
      'parseColumns' => '<COLUMNS>',
    ]);

    $mockPdoStatement = Mockery::mock(PDOStatement::class);
    $mockPdoStatement->shouldReceive('fetchAll')->andReturn(
      [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    );
    $mockPdoStatement->shouldReceive('fetchAll')->andReturn([10]);

    $this->select = Select::_from(
      'my_table',
      Mockery::mock(PDOWrapperInterface::class, [
        'execute' => $mockPdoStatement,
      ]),
      $mockCommon,
    );

    // Access private properties using reflection
    $reflection = new ReflectionClass($this->select);

    $limit = $reflection->getProperty('limit');
    $limit->setAccessible(TRUE);
    $this->limit = $limit;

    $offset = $reflection->getProperty('offset');
    $offset->setAccessible(TRUE);
    $this->offset = $offset;
  });

  test('fetchPage throws exception if paginate not called before', function () {
    $this->select->fetchPage();
  })->throws(
    Exception::class,
    'You must call paginate() before fetchPage().'
  );

  test('paginate sets limit and offset', function () {
    $this->select->paginate(10);

    expect($this->limit->getValue($this->select))->toBe(10);
    expect($this->offset->getValue($this->select))->toBe(0);
  });

  test('fetchPage sets correct offset for specified page', function () {
    $this->select->paginate(10);
    $result = $this->select->fetchPage(20);

    expect($result)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($this->offset->getValue($this->select))->toBe(190);
  });

  test('fetchNextPage increments offset', function () {
    $this->select->paginate(10)->fetchPage(5);
    expect($this->offset->getValue($this->select))->toBe(40);

    // Fetch next page
    $result = $this->select->fetchNextPage();
    expect($result)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($this->offset->getValue($this->select))->toBe(50);
  });

  test('fetchPreviousPage decrements offset', function () {
    $this->select->paginate(10)->fetchPage(5);
    expect($this->offset->getValue($this->select))->toBe(40);

    // Fetch next page
    $result = $this->select->fetchPreviousPage();
    expect($result)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($this->offset->getValue($this->select))->toBe(30);
  });

  test('fetchPreviousPage throws exception if already on first page', function () {
    $this->select->paginate(10)->fetchFirstPage();
    $this->select->fetchPreviousPage();
  })->throws(
    Exception::class,
    'There is no previous page.',
  );

  test('fetchFirstPage sets offset to 0', function () {
    $this->select->paginate(10)->fetchPage(3);
    $result = $this->select->fetchFirstPage();

    expect($result)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($this->offset->getValue($this->select))->toBe(0);
  });

  // @todo 1 - Fix this test
  // test('fetchLastPage sets offset to last page', function () {
  //   $this->select->paginate(3);

  //   /**
  //    * @var Select&MockInterface $mockSelect
  //    */
  //   $mockSelect = Mockery::mock($this->select)->makePartial();
  //   $mockSelect->shouldReceive('fetchCount')->once()->andReturn(10);

  //   $result = $mockSelect->fetchLastPage();
  //   expect($result)->toBeInstanceOf(FetchPaginatedResultInterface::class);

  //   $totalPages = (int)ceil(10 / 3);
  //   expect($result->page)->toBe($totalPages);
  //   expect($result->perPage)->toBe(3);
  // });
});
