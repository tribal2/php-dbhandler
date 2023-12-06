<?php

use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Queries\Schema;

describe('Class', function () {

  test('constructor', function () {
    $schema = new Schema(
      Mockery::mock(PDOWrapperInterface::class, [ 'getDbName' => 'test_db' ]),
      Mockery::mock(CommonInterface::class),
    );
    expect($schema)->toBeInstanceOf(Schema::class);
  });

});


describe('Methods', function () {

  beforeEach(function () {
    $this->myPdo = Mockery::mock(PDOWrapperInterface::class, [
      'getDbName' => 'test_db',
    ]);
  });

  test("checkIfTableExists('known_table') - should return TRUE", function () {
    $this->myPdo->shouldReceive('execute')->once()->andReturn([ 1 ]);
    $schema = new Schema($this->myPdo);

    expect($schema->checkIfTableExists('users'))
      ->toBeBool()
      ->toBe(TRUE);
  });

  test("checkIfTableExists('unknown_table') should return FALSE", function () {
    $this->myPdo->shouldReceive('execute')->once()->andReturn([]);
    $schema = new Schema($this->myPdo);

    expect($schema->checkIfTableExists('unknown_table'))
      ->toBeBool()
      ->toBe(FALSE);
  });

});
