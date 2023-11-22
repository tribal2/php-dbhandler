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

  test('return all', function () {
    $result = Select::from('test_table')
      ->fetchAll();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
  });

  test('return all reversed', function () {
    $result = Select::from('test_table')
      ->orderBy('test_table_id', OrderByDirectionEnum::DESC)
      ->fetchAll();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0]->test_table_id)->toBe(2);
    expect($result[0]->value)->toBe('Test value 2');
  });

});


describe('fetchFirst()', function () {

  test('return first', function () {
    $result = Select::from('test_table')
      ->fetchFirst();

    expect($result)->toBeObject();
    expect($result->value)->toBe('Test value 1');
  });

  test('return last', function () {
    $result = Select::from('test_table')
      ->orderBy('test_table_id', OrderByDirectionEnum::DESC)
      ->fetchFirst();

      expect($result->value)->toBe('Test value 2');
  });

});


describe('fetchColumn()', function () {

  test('throw when multiple columns are selected', function () {
    Select::from('test_table')
      ->columns(['test_table_id', 'value'])
      ->fetchColumn();
  })->throws(
    Exception::class,
    'Only one column can be selected',
  );

  test('using column() to set the column', function () {
    $res = Select::from('test_table')
      ->column('value')
      ->fetchColumn();

    expect($res)->toBeArray();
    expect($res)->toHaveCount(2);
    expect($res[0])->toBe('Test value 1');
  });

  test('passing a column name', function () {
    $res = Select::from('test_table')
      ->fetchColumn('value');

    expect($res)->toBeArray();
    expect($res)->toHaveCount(2);
    expect($res[0])->toBe('Test value 1');
  });

  test('passing a column name when other columns are already set', function () {
    $res = Select::from('test_table')
      ->columns(['test_table_id', 'value'])
      ->fetchColumn('value');

    expect($res)->toBeArray();
    expect($res)->toHaveCount(2);
    expect($res[0])->toBe('Test value 1');
  });

});


describe('fetchValue()', function () {

  test('throw when multiple columns are selected', function () {
    Select::from('test_table')
      ->columns(['test_table_id', 'value'])
      ->fetchValue();
  })->throws(
    Exception::class,
    'Only one column can be selected',
  );

  test('using column() to set the column', function () {
    $res = Select::from('test_table')
      ->column('value')
      ->fetchValue();

    expect($res)->toBe('Test value 1');
  });

  test('passing a column name', function () {
    $res = Select::from('test_table')
      ->fetchValue('value');

    expect($res)->toBe('Test value 1');
  });

  test('passing a column name when other columns are already set', function () {
    $res = Select::from('test_table')
      ->columns(['test_table_id', 'value'])
      ->fetchValue('value');

    expect($res)->toBe('Test value 1');
  });

  test('return NULL when there is no value', function () {
    $res = Select::from('test_table')
      ->where(Where::equals('test_table_id', 3))
      ->fetchValue('value');

    expect($res)->toBeNull();
  });

});
