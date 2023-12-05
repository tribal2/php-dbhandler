<?php

use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\StoredProcedureArgumentInterface;
use Tribal2\DbHandler\Queries\StoredProcedure;


describe('Instance', function () {

  it('should generate an instance of StoredProcedure', function () {
    $instance = new StoredProcedure(
      'get_test_rows',
      'dbhandler',
      [],
      Mockery::mock(PDO::class),
      Mockery::mock(CommonInterface::class),
    );

    expect($instance)->toBeInstanceOf(StoredProcedure::class);
  });

});


describe('Exceptions', function () {

  it('should throw with invalid argument name', function () {
    $sp = new StoredProcedure(
      'get_test_rows',
      'dbhandler',
      [ 'valid' => NULL ],
      Mockery::mock(PDO::class),
      Mockery::mock(CommonInterface::class),
    );

    $sp->with('invalid', '123');
  })->throws(
    Exception::class,
    "No parameter with name 'invalid' exists for stored procedure 'get_test_rows'",
    500,
  );

});


describe('with() and getArguments()', function () {

  it('should accept values for expected argument', function () {
    $mockArg = Mockery::mock(
      StoredProcedureArgumentInterface::class,
      [ 'hasValue' => TRUE ],
    );
    $mockArg
      ->shouldReceive('addValue')
      ->andSet('name', 'valid')
      ->andSet('value', '123');

    $sp = new StoredProcedure(
      'get_test_rows',
      'dbhandler',
      [ 'valid' => $mockArg ],
      Mockery::mock(PDO::class),
      Mockery::mock(CommonInterface::class),
    );
    $sp->with('valid', '123');

    expect($sp->getArguments())
      ->toBeArray()
      ->toHaveCount(1)
      ->toHaveKey('valid');
  });

  it('getArguments() should return an empty array if no value is added', function () {
    $mockArg = Mockery::mock(
      StoredProcedureArgumentInterface::class,
      [ 'hasValue' => FALSE ],
    );
    $sp = new StoredProcedure(
      'get_test_rows',
      'dbhandler',
      [ 'valid' => $mockArg ],
      Mockery::mock(PDO::class),
      Mockery::mock(CommonInterface::class),
    );

    expect($sp->getArguments())
      ->toBeArray()
      ->toHaveCount(0);
  });

});


describe('SQL', function () {

  it('should generate valid SQL statements', function () {
    $mockArg = Mockery::mock(StoredProcedureArgumentInterface::class);
    $mockArg
      ->shouldReceive('hasValue')->andReturn(TRUE)->getMock()
      ->shouldReceive('addValue')
      ->andSet('position', 1)
      ->andSet('name', 'valid')
      ->andSet('value', '123');

    $mockBindBuilder = Mockery::mock(PDOBindBuilderInterface::class);
    $mockBindBuilder
      ->shouldReceive('addValueWithPrefix')
      ->andReturn('<BINDED_VALUE>');

    $sp = new StoredProcedure(
      'get_test_rows',
      'dbhandler',
      [ 'valid' => $mockArg ],
      Mockery::mock(PDO::class),
      Mockery::mock(CommonInterface::class),
    );

    $sql = $sp
      ->with('valid', '123')
      ->getSql($mockBindBuilder);

    expect($sql)
      ->toBeString()
      ->toBe('CALL get_test_rows(<BINDED_VALUE>);');
  });

  it('should generate valid SQL statements with multiple values', function () {
    $mockArg1 = Mockery::mock(StoredProcedureArgumentInterface::class);
    $mockArg1
      ->shouldReceive('hasValue')->andReturn(TRUE)->getMock()
      ->shouldReceive('addValue')
      ->andSet('position', 1)
      ->andSet('name', 'input1')
      ->andSet('value', '123');

    $mockArg2 = Mockery::mock(StoredProcedureArgumentInterface::class);
    $mockArg2
      ->shouldReceive('hasValue')->andReturn(TRUE)->getMock()
      ->shouldReceive('addValue')
      ->andSet('position', 2)
      ->andSet('name', 'input2')
      ->andSet('value', '456');

    $mockBindBuilder = Mockery::mock(PDOBindBuilderInterface::class);
    $mockBindBuilder
      ->shouldReceive('addValueWithPrefix')
      ->andReturn('<BINDED_VALUE>');

    $sp = new StoredProcedure(
      'get_test_rows',
      'dbhandler',
      [
        'input1' => $mockArg1,
        'input2' => $mockArg2,
      ],
      Mockery::mock(PDO::class),
      Mockery::mock(CommonInterface::class),
    );

    $sql = $sp
      ->with('input1', '123')
      ->with('input2', '456')
      ->getSql($mockBindBuilder);

    expect($sql)
      ->toBeString()
      ->toBe('CALL get_test_rows(<BINDED_VALUE>, <BINDED_VALUE>);');
  });

});


describe('Execute', function () {

  it('should execute SQL', function () {
    $mockArg = Mockery::mock(StoredProcedureArgumentInterface::class);
    $mockArg
      ->shouldReceive('hasValue')->andReturn(TRUE)->getMock()
      ->shouldReceive('addValue')
      ->andSet('position', 1)
      ->andSet('name', 'valid')
      ->andSet('value', '123');

    $mockBindBuilder = Mockery::mock(PDOBindBuilderInterface::class);
    $mockBindBuilder
      ->shouldReceive('addValueWithPrefix')->andReturn('<BINDED_VALUE>')->getMock()
      ->shouldReceive('bindToStatement');

    $mockPdoStatement = Mockery::mock(PDOStatement::class, [
      'execute' => TRUE,
      'fetchAll' => [ (object)[ 'id' => 1, 'name' => 'test' ] ],
    ]);

    $mockPdo = Mockery::mock(PDO::class, [
      'prepare' => $mockPdoStatement,
    ]);

    $sp = new StoredProcedure(
      'get_test_rows',
      'dbhandler',
      [ 'valid' => $mockArg ],
      Mockery::mock(PDO::class),
      Mockery::mock(CommonInterface::class),
    );

    $result = $sp
      ->with('valid', '123')
      ->execute($mockBindBuilder, $mockPdo);

    expect($result)
      ->toBeArray()
      ->toHaveCount(1);

    expect($result[0])
      ->toBeObject()
      ->toHaveProperty('id', 1)
      ->toHaveProperty('name', 'test');
  });

});
