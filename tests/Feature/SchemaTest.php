<?php

use Tribal2\DbHandler\Schema;

require_once __DIR__ . '/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});


describe('Schema', function () {

  test('checkIfTableExists()', function () {
    expect(Schema::checkIfTableExists('test_table'))->toBe(TRUE);
    expect(Schema::checkIfTableExists('test_tablex'))->toBe(FALSE);
  });

});
