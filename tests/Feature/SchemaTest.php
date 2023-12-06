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

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
  });

  test('checkIfTableExists() - should return TRUE', function () {
    expect(Schema::_checkIfTableExists('test_table', $this->myPdo))
      ->toBe(TRUE);
  });

  test('checkIfTableExists() - should return FALSE', function () {
    expect(Schema::_checkIfTableExists('test_tablex', $this->myPdo))
      ->toBe(FALSE);
  });

});
