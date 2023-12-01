<?php

use Tribal2\DbHandler\Helpers\StoredProcedureArgument;
use Tribal2\DbHandler\PDOSingleton;

require_once __DIR__ . '/../Feature/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});

describe('Static methods', function () {

  it('should fetch stored procedure arguments', function () {
    $arguments = StoredProcedureArgument::getAllFor(
      PDOSingleton::getDbName(),
      'get_test_rows',
    );

    expect($arguments)
      ->toBeArray()
      ->toHaveLength(2);
  });

});

describe('addValue() Exceptions', function () {

  beforeEach(function () {
    $this->arguments = StoredProcedureArgument::getAllFor(
      PDOSingleton::getDbName(),
      'get_test_rows',
    );
  });

  it('should throw on non matching value type', function () {
    $this->arguments['keyInput']->addValue(123);
  })->throws(
    Exception::class,
    'Invalid type for argument keyInput. Expected varchar.',
    500,
  );

  it('should throw on char length over limit', function () {
    $this->arguments['valueInput']->addValue('123456');
  })->throws(
    Exception::class,
    'Invalid length for argument valueInput. Expected 5.',
    500,
  );

});

describe('addValue()', function () {

  beforeEach(function () {
    $this->arguments = StoredProcedureArgument::getAllFor(
      PDOSingleton::getDbName(),
      'get_test_rows',
    );
  });

  it('should set value property', function () {
    $this->arguments['keyInput']->addValue('123');
    $this->arguments['valueInput']->addValue('12345');

    expect($this->arguments['keyInput']->value)->toBe('123');
    expect($this->arguments['valueInput']->value)->toBe('12345');
  });

});
