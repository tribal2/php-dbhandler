<?php

use Tribal2\DbHandler\Queries\Select;
use Tribal2\DbHandler\Queries\Update;
use Tribal2\DbHandler\Queries\Where;

require_once __DIR__ . '/../Feature/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});

describe('Update', function () {

  test('update records with Where', function () {
    $where = Where::equals('test_table_id', 1);

    $updateResult = Update::table('test_table')
      ->set('value', 'updated value')
      ->where($where)
      ->execute();

    expect($updateResult)->toBeTrue();

    $updatedRow = Select::from('test_table')
      ->where($where)
      ->fetchFirst();

    // check if the record was updated
    expect($updatedRow->value)->toBe('updated value');
  });

  test('update all records', function () {
    $updateResult = Update::table('test_table')
      ->set('value', 'updated value')
      ->execute();

    expect($updateResult)->toBeTrue();

    $updatedRows = Select::from('test_table')
      ->where(Where::equals('value', 'updated value'))
      ->fetchAll();

    // check if all records were updated
    expect(count($updatedRows))->toBe(2);
    expect($updatedRows[0]->value)->toBe('updated value');
    expect($updatedRows[1]->value)->toBe('updated value');
  });

});
