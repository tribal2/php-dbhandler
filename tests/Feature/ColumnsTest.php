<?php

use Tribal2\DbHandler\Table\Columns;

require_once __DIR__ . '/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});


describe('Columns', function () {

  test('getTableColumns()', function () {
    $columns = Columns::for('test_table');
    expect($columns)->toBeObject();
    expect((array)$columns)->toHaveKeys([
      'table',
      'columns',
      'key',
      'nonKey',
      'autoincrement',
    ]);

    $keyColArr = $columns->key;
    expect($keyColArr)->toBeArray();
    expect($keyColArr)->toHaveCount(1);
    expect($keyColArr[0])->toBe('test_table_id');
  });

  test('has() returns true when column exist', function () {
    $columns = Columns::for('test_table');

    $columnsInTable = [
    'test_table_id',
    'key',
    'value',
    'created_at',
    'updated_at',
    ];

    foreach ($columnsInTable as $col) {
      expect($columns->has($col))->toBeTrue();
    }
  });

  test('has() returns false when column does not exist', function () {
    $columns = Columns::for('test_table');

    $columnsNotInTable = [
    'test_table_id_',
    'key_',
    'value_',
    'created_at_',
    'updated_at_',
    ];

    foreach ($columnsNotInTable as $col) {
      expect($columns->has($col))->toBeFalse();
    }
  });

});
