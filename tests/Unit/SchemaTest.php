<?php

use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Queries\Schema;

describe('Class', function () {

  test('constructor', function () {
    $schema = new Schema(
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(CommonInterface::class),
    );
    expect($schema)->toBeInstanceOf(Schema::class);
  });

});


describe('Methods', function () {

  test('checkIfTableExists() should return TRUE', function () {
    $result = Schema::_checkIfTableExists(
      'users',
      Mockery::mock(PDOWrapperInterface::class, [ 'execute' => [ 1 ] ]),
    );

    expect($result)->toBeBool();
    expect($result)->toBe(TRUE);
  });

  test('checkIfTableExists() should return FALSE', function () {
    $result = Schema::_checkIfTableExists(
      'users',
      Mockery::mock(PDOWrapperInterface::class, [ 'execute' => [] ]),
    );

    expect($result)->toBeBool();
    expect($result)->toBe(FALSE);
  });

});
