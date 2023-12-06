<?php

use Tribal2\DbHandler\Enums\OrderByDirectionEnum;
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
    'Only one column can be selected',
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
    'Only one column can be selected',
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
