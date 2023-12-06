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
    $this->schema = new Schema($this->myPdo);
  });

  test("checkIfTableExists('known_table') - should return TRUE", function () {
    expect($this->schema->checkIfTableExists('test_table'))
      ->toBe(TRUE);
  });

  test("checkIfTableExists('unknown_table') should return FALSE", function () {
    expect($this->schema->checkIfTableExists('test_tablex'))
      ->toBe(FALSE);
  });

  test('getStoredProcedureArguments()', function () {
    $results = $this->schema->getStoredProcedureArguments('get_test_rows');

    expect($results)
      ->toBeArray()
      ->toHaveCount(2);

    expect($results[0])
      ->toBeInstanceOf(stdClass::class)
      ->toHaveProperties(['ORDINAL_POSITION', 'PARAMETER_NAME', 'DATA_TYPE', 'CHARACTER_MAXIMUM_LENGTH']);
  });

  test('getStoredProcedureArguments() with unknown procedure should return empty array', function () {
    $results = $this->schema->getStoredProcedureArguments('unknown_procedure');

    expect($results)
      ->toBeArray()
      ->toHaveCount(0);
  });

});
