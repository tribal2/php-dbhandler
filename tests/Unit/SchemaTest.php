<?php

use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Queries\Schema;

describe('Class', function () {

  test('constructor', function () {
    $schema = new Schema(
      Mockery::mock(PDO::class),
      Mockery::mock(PDOBindBuilderInterface::class),
    );
    expect($schema)->toBeInstanceOf(Schema::class);
  });

});


describe('Methods', function () {

  test('checkIfTableExists() should return TRUE', function () {
    $schema = new Schema(
      Mockery::mock(PDO::class, [
        'prepare' => Mockery::mock(PDOStatement::class, [
          'execute' => TRUE,
          'rowCount' => 1,
        ]),
      ]),
      Mockery::mock(PDOBindBuilderInterface::class, [
        'addValue' => '',
        'bindToStatement' => '',
      ]),
    );

    $result = $schema->_checkIfTableExists('users');

    expect($result)->toBeBool();
    expect($result)->toBe(TRUE);
  });

  test('checkIfTableExists() should return FALSE', function () {
    $schema = new Schema(
      Mockery::mock(PDO::class, [
        'prepare' => Mockery::mock(PDOStatement::class, [
          'execute' => TRUE,
          'rowCount' => 0,
        ]),
      ]),
      Mockery::mock(PDOBindBuilderInterface::class, [
        'addValue' => '',
        'bindToStatement' => '',
      ]),
    );

    $result = $schema->_checkIfTableExists('users');

    expect($result)->toBeBool();
    expect($result)->toBe(FALSE);
  });

});
