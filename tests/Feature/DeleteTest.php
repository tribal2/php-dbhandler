<?php

use Tribal2\DbHandler\Queries\Delete;
use Tribal2\DbHandler\Queries\Where;

require_once __DIR__ . '/../Feature/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});

describe('Delete', function () {

  test('delete records with Where', function () {
    $deleteCount = Delete::_from('test_table')
      ->where(Where::equals('test_table_id', 1))
      ->execute();

    expect($deleteCount)->toBeInt();
    expect($deleteCount)->toBe(1);
  });

  test('delete multiple records', function () {
    $deleteCount = Delete::_from('test_table_no_auto_increment')
      ->where(Where::like('key', 'test%'))
      ->execute();

    expect($deleteCount)->toBeInt();
    expect($deleteCount)->toBe(2);
  });

});
