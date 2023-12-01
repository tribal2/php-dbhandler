<?php

use Tribal2\DbHandler\Queries\Schema;

require_once __DIR__ . '/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});


describe('Schema', function () {

  test('checkIfTableExists() - should return TRUE', function () {
    expect(Schema::checkIfTableExists('test_table'))->toBe(TRUE);
  });

  test('checkIfTableExists() - should return FALSE', function () {
    expect(Schema::checkIfTableExists('test_tablex'))->toBe(FALSE);
  });

});
