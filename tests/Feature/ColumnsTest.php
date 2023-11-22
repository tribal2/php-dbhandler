<?php

use Tribal2\DbHandler\Table\Columns;

require_once __DIR__ . '/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});


describe('Schema', function () {

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

});
