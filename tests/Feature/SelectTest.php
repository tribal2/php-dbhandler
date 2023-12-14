<?php

use Tribal2\DbHandler\Enums\OrderByDirectionEnum;
use Tribal2\DbHandler\Helpers\Cache;
use Tribal2\DbHandler\Interfaces\FetchPaginatedResultInterface;
use Tribal2\DbHandler\Queries\Insert;
use Tribal2\DbHandler\Queries\Select;
use Tribal2\DbHandler\Queries\Where;

require_once __DIR__ . '/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});


describe('fetchAll()', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
  });

  test('return all', function () {
    $result = Select::_from('test_table', $this->myPdo)
      ->fetchAll();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
  });

  test('return all reversed', function () {
    $result = Select::_from('test_table', $this->myPdo)
      ->orderBy('test_table_id', OrderByDirectionEnum::DESC)
      ->fetchAll();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0]->test_table_id)->toBe(2);
    expect($result[0]->value)->toBe('Test value 2');
  });

});


describe('fetchFirst()', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
  });

  test('return first', function () {
    $result = Select::_from('test_table', $this->myPdo)
      ->fetchFirst();

    expect($result)->toBeObject();
    expect($result->value)->toBe('Test value 1');
  });

  test('return last', function () {
    $result = Select::_from('test_table', $this->myPdo)
      ->orderBy('test_table_id', OrderByDirectionEnum::DESC)
      ->fetchFirst();

      expect($result->value)->toBe('Test value 2');
  });

});


describe('fetchColumn()', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
  });

  test('throw when multiple columns are selected', function () {
    Select::_from('test_table', $this->myPdo)
      ->columns(['test_table_id', 'value'])
      ->fetchColumn();
  })->throws(
    Exception::class,
    'There are more than one column to select. Provide a column name to this method.',
  );

  test('using column() to set the column', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->column('value')
      ->fetchColumn();

    expect($res)->toBeArray();
    expect($res)->toHaveCount(2);
    expect($res[0])->toBe('Test value 1');
  });

  test('passing a column name', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->fetchColumn('value');

    expect($res)->toBeArray();
    expect($res)->toHaveCount(2);
    expect($res[0])->toBe('Test value 1');
  });

  test('passing a column name when other columns are already set', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->columns(['test_table_id', 'value'])
      ->fetchColumn('value');

    expect($res)->toBeArray();
    expect($res)->toHaveCount(2);
    expect($res[0])->toBe('Test value 1');
  });

});


describe('fetchValue()', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
  });

  test('throw when multiple columns are selected', function () {
    Select::_from('test_table', $this->myPdo)
      ->columns(['test_table_id', 'value'])
      ->fetchValue();
  })->throws(
    Exception::class,
    'There are more than one column to select. Provide a column name to this method.',
  );

  test('using column() to set the column', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->column('value')
      ->fetchValue();

    expect($res)->toBe('Test value 1');
  });

  test('passing a column name', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->fetchValue('value');

    expect($res)->toBe('Test value 1');
  });

  test('passing a column name when other columns are already set', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->columns(['test_table_id', 'value'])
      ->fetchValue('value');

    expect($res)->toBe('Test value 1');
  });

  test('return NULL when there is no value', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->where(Where::equals('test_table_id', 3))
      ->fetchValue('value');

    expect($res)->toBeNull();
  });

});


describe('functions', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
  });

  test('DISTINCT()', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->fetchColumn('DISTINCT(`key`)');

    expect($res)->toBeArray();
    expect($res)->toHaveCount(2);
    expect($res[0])->toBe('test1');
    expect($res[1])->toBe('test2');
  });

  test('DISTINCT() using method', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->fetchDistincts('key');

    expect($res)->toBeArray();
    expect($res)->toHaveCount(2);
    expect($res[0])->toBe('test1');
    expect($res[1])->toBe('test2');
  });

  test('COUNT()', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->fetchValue('COUNT(*)');

    expect($res)->toBe('2');
  });

  test('COUNT() using method', function () {
    $res = Select::_from('test_table', $this->myPdo)
      ->fetchCount();

    expect($res)->toBe(2);
  });

});


describe('Caching', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
  });

  test('it should throw when cache is not set', function () {
    Select::_from('test_table', $this->myPdo)->withCache();
  })->throws(Exception::class);

  test('set cache when is not set', function () {
    $select = Select::_from('test_table', $this->myPdo);

    $cache = new Cache();
    $select->setCache($cache);

    // Select and cache results
    $results = $select
      ->withCache()
      ->fetchAll();

    expect($results)
      ->toBeArray()
      ->toHaveLength(2);

    // Add new row
    Insert::_into('test_table', $this->myPdo)
      ->value('key', 'cache_test')
      ->value('value', 'Test value 3')
      ->execute();

    // Select again without cache to verify new row
    $resultsAfterInsert = Select::_from('test_table', $this->myPdo)
      ->fetchAll();

    expect($resultsAfterInsert)
      ->toBeArray()
      ->toHaveLength(3);

    // Select again with cache to verify new row is not returned
    $cachedResults = $select
      ->withCache()
      ->fetchAll();

    expect($cachedResults)
      ->toBeArray()
      ->toHaveLength(2);
  });
});


describe('Pagination', function () {

  beforeEach(function () {
    $this->select = Select::_from(
      'test_table',
      DbTestSchema::getPdoWrapper(),
    );

    $reflection = new ReflectionClass($this->select);
    $this->limit = $reflection->getProperty('limit');
    $this->limit->setAccessible(TRUE);
    $this->offset = $reflection->getProperty('offset');
    $this->offset->setAccessible(TRUE);
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
    // Fetch first page
    $firstPageResult = $this->select->paginate(1)->fetchPage(1);

    expect($firstPageResult)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($firstPageResult->data)
      ->toBeArray()
      ->toHaveLength(1);
    expect($firstPageResult->data[0]->test_table_id)->toBe(1);

    // Fetch next page
    $secondPageResult = $this->select->fetchNextPage();
    expect($secondPageResult)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($secondPageResult->data)
      ->toBeArray()
      ->toHaveLength(1);
    expect($firstPageResult->data[0]->test_table_id)->toBe(1);
  });

  test('fetchPreviousPage decrements offset', function () {
    $secondPageResult = $this->select->paginate(1)->fetchPage(2);
    expect($secondPageResult)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($secondPageResult->data)
      ->toBeArray()
      ->toHaveLength(1);
    expect($secondPageResult->data[0]->test_table_id)->toBe(2);

    // Fetch previous page
    $firstPageResult = $this->select->fetchPreviousPage();
    expect($firstPageResult)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($firstPageResult->data)
      ->toBeArray()
      ->toHaveLength(1);
    expect($firstPageResult->data[0]->test_table_id)->toBe(1);
  });

  test('fetchFirstPage', function () {
    $secondPageResult = $this->select->paginate(1)->fetchPage(2);
    expect($secondPageResult)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($secondPageResult->data)
      ->toBeArray()
      ->toHaveLength(1);
    expect($secondPageResult->data[0]->test_table_id)->toBe(2);

    // Fetch previous page
    $firstPageResult = $this->select->fetchFirstPage();
    expect($firstPageResult)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($firstPageResult->data)
      ->toBeArray()
      ->toHaveLength(1);
    expect($firstPageResult->data[0]->test_table_id)->toBe(1);
  });

  test('fetchPreviousPage throws exception if already on first page', function () {
    $this->select->paginate(1)->fetchFirstPage();
    $this->select->fetchPreviousPage();
  })->throws(
    Exception::class,
    'There is no previous page.',
  );

  test('fetchLastPage', function () {
    // Fetch first page
    $firstPageResult = $this->select->paginate(2)->fetchPage();
    expect($firstPageResult)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($firstPageResult->data)
      ->toBeArray()
      ->toHaveLength(2);
    expect($firstPageResult->data[0]->test_table_id)->toBe(1);
    expect($firstPageResult->data[1]->test_table_id)->toBe(2);

    // Fetch last page
    $lastPageResult = $this->select->fetchLastPage();
    expect($lastPageResult)->toBeInstanceOf(FetchPaginatedResultInterface::class);
    expect($lastPageResult->data)
      ->toBeArray()
      ->toHaveLength(1);
    expect($lastPageResult->data[0]->test_table_id)->toBe(3);
  });
});
