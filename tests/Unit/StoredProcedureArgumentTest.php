<?php

use Tribal2\DbHandler\Helpers\StoredProcedureArgument;
use Tribal2\DbHandler\Queries\Schema;

describe('Instance', function () {

  it('should generate an instance of StoredProcedureArgument', function () {
    $instance = new StoredProcedureArgument(
      position: 2,
      name: 'test',
      type: 'string',
      maxCharLength: 255,
    );

    expect($instance)->toBeInstanceOf(StoredProcedureArgument::class);
  });

});


describe('Exceptions', function () {

  it('should throw an exception for invalid value type', function () {
      $arg = new StoredProcedureArgument(1, 'test', 'int');
      $arg->addValue('invalid');
  })->throws(
    Exception::class,
    "Invalid type for argument 'test'. Expected type: int.",
    500,
  );

  it('should throw an exception for invalid string length', function () {
    $arg = new StoredProcedureArgument(1, 'test', 'varchar', 5);
    $arg->addValue('invalid');
  })->throws(
    Exception::class,
    "Invalid length for argument 'test'. Expected: 5.",
    500,
  );

});


describe('StoredProcedureArgument', function () {

  it('should add a valid value', function () {
      $arg = new StoredProcedureArgument(1, 'test', 'int');
      $arg->addValue(123);

      expect($arg->value)->toEqual(123);

      expect($arg->hasValue())->toBeTrue();
  });

});


describe('Static methods', function () {
  it('should fetch stored procedure arguments', function () {
    $mockSchema = Mockery::mock(Schema::class, [
      'getStoredProcedureArguments' => [
        (object)[
          'ORDINAL_POSITION' => 1,
          'PARAMETER_NAME' => 'param1',
          'DATA_TYPE' => 'int',
          'CHARACTER_MAXIMUM_LENGTH' => NULL
        ],
        (object)[
          'ORDINAL_POSITION' => 2,
          'PARAMETER_NAME' => 'param2',
          'DATA_TYPE' => 'varchar',
          'CHARACTER_MAXIMUM_LENGTH' => 255
        ],
      ],
    ]);

    $arguments = StoredProcedureArgument::getAllFor(
      'procedure_name',
      $mockSchema,
    );

    expect($arguments)
      ->toBeArray()
      ->toHaveLength(2);

    expect($arguments['param1'])
      ->toBeInstanceOf(StoredProcedureArgument::class)
      ->toHaveProperty('position', 1)
      ->toHaveProperty('name', 'param1')
      ->toHaveProperty('type', 'int')
      ->toHaveProperty('maxCharLength', NULL);

    expect($arguments['param2'])
      ->toBeInstanceOf(StoredProcedureArgument::class)
      ->toHaveProperty('position', 2)
      ->toHaveProperty('name', 'param2')
      ->toHaveProperty('type', 'varchar')
      ->toHaveProperty('maxCharLength', 255);
  });

  it('should throw if there are no arguments', function () {
    $mockSchema = Mockery::mock(Schema::class, [
      'getStoredProcedureArguments' => [],
    ]);

    StoredProcedureArgument::getAllFor('procedure_name', $mockSchema);
  })->throws(
    Exception::class,
    "No arguments found for stored procedure 'procedure_name'.",
    500,
  );
});
