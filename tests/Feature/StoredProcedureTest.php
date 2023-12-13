<?php

use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\StoredProcedure;

require_once __DIR__ . '/../Feature/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});


describe('Exceptions', function () {

  beforeEach(function () {
    $this->storedProcedure = new StoredProcedure(DbTestSchema::getPdoWrapper());
  });

  it('should throw when read only mode is enabled', function () {
    StoredProcedure::_call(
      'get_test_rows',
      DbTestSchema::getReadOnlyPdoWrapper(),
    )->execute();
  })->throws(
    Exception::class,
    "Can't execute statement. Read only mode is enabled.",
    409,
  );

  it('should throw with invalid procedure name', function () {
    $this->storedProcedure->call('invalid_procedure_name');
  })->throws(Exception::class);

  it('should throw with invalid argument name', function () {
    $this->storedProcedure
      ->call('get_test_rows')
      ->with('invalid', '123');
  })->throws(Exception::class);

  it('should throw with invalid argument type', function () {
    $this->storedProcedure
      ->call('get_test_rows')
      ->with('keyInput', 123);
  })->throws(Exception::class);

});


describe('getArguments()', function () {

  beforeEach(function () {
    $this->storedProcedure = new StoredProcedure(DbTestSchema::getPdoWrapper());
  });

  it('should return an array of provided (and validated arguments)', function () {
    $sp = $this->storedProcedure->call('get_test_rows');

    $args1 = $sp->with('keyInput', '123')->getArguments();

    expect($args1)
      ->toBeArray()
      ->toHaveCount(1)
      ->toHaveKey('keyInput');

    expect($args1['keyInput'])
      ->toBeString()
      ->toBe('123');

    // Test with multiple arguments
    $args2 = $sp->with('valueInput', '456')->getArguments();

    expect($args2)
      ->toBeArray()
      ->toHaveCount(2)
      ->toHaveKeys(['keyInput', 'valueInput']);

    expect($args2['valueInput'])
      ->toBeString()
      ->toBe('456');
  });

});


describe('SQL', function () {

  beforeEach(function () {
    $this->storedProcedure = new StoredProcedure(DbTestSchema::getPdoWrapper());
  });

  it('should generate valid SQL statements', function () {
    $bindBuilder = new PDOBindBuilder();

    $sql = $this->storedProcedure
      ->call('get_test_rows')
      ->with('keyInput', '123')
      ->with('valueInput', '%')
      ->getSql($bindBuilder);

    expect($sql)
      ->toBeString()
      ->toBe('CALL get_test_rows(:keyInput___1, :valueInput___1);');

    $sqlWithValues = $bindBuilder->debugQuery($sql);

    expect($sqlWithValues)
      ->toBeString()
      ->toBe("CALL get_test_rows('123', '%');");
  });

});


describe('Results', function () {

  beforeEach(function () {
    $this->storedProcedure = new StoredProcedure(DbTestSchema::getPdoWrapper());
  });

  it('should return empty array if no value found', function () {
    $results = $this->storedProcedure
      ->call('get_test_rows')
      ->with('keyInput', '123')
      ->with('valueInput', 'fff')
      ->execute();

    expect($results)
      ->toBeArray()
      ->toBeEmpty();
  });

  it('should return array of objects', function () {
    $results = $this->storedProcedure
      ->call('get_test_rows')
      ->with('keyInput', 'test')
      ->execute();

    expect($results)
      ->toBeArray()
      ->toHaveCount(2);

    expect($results[0])
      ->toBeObject()
      ->toHaveProperty('test_table_id', 1)
      ->toHaveProperty('key', 'test1')
      ->toHaveProperty('value', 'Test value 1');

    expect($results[1])
      ->toBeObject()
      ->toHaveProperty('test_table_id', 2)
      ->toHaveProperty('key', 'test2')
      ->toHaveProperty('value', 'Test value 2');
  });

});
