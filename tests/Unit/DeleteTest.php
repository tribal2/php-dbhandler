<?php

use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\Queries\Delete;


describe('Delete Builder', function () {

  test('static factory', function () {
    $delete = new Delete(
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(CommonInterface::class),
    );
    expect($delete)->toBeInstanceOf(Delete::class);
  });

});


describe('SQL Generation', function () {

  test('getSql() generates correct SQL query', function () {
    $mockWhere = Mockery::mock(WhereInterface::class, [ 'getSql' => '<WHERE>' ]);
    $mockBindBuilder = Mockery::mock(PDOBindBuilderInterface::class, [
      'addValueWithPrefix' => '<BINDED_VALUE>',
    ]);
    $mockCommon = Mockery::mock(CommonInterface::class, [
      'checkValue' => PDO::PARAM_STR,
      'quoteWrap' => '<WRAPPED_VALUE>',
    ]);

    $delete = Delete::_from(
      'test_table',
      Mockery::mock(PDOWrapperInterface::class),
      $mockCommon,
    );

    $sql = $delete
      ->where($mockWhere)
      ->getSql($mockBindBuilder);
    $expectedSql = 'DELETE FROM <WRAPPED_VALUE> WHERE <WHERE>;';

    expect($sql)->toBeString();
    expect($sql)->toBe($expectedSql);
  });

});


describe('Execution', function () {

  beforeEach(function() {
    $mockCommon = Mockery::mock(CommonInterface::class, [
      'checkValue' => PDO::PARAM_STR,
      'quoteWrap' => '<WRAPPED_VALUE>',
    ]);

    $mockPdo = Mockery::mock(PDOWrapperInterface::class, [
      'isReadOnly' => FALSE,
      'execute' => Mockery::mock(PDOStatement::class, [
        'rowCount' => 1,
      ])
    ]);

    $this->delete = Delete::_from(
      'test_table',
      $mockPdo,
      $mockCommon,
    );
  });

  it('should throw if no where clause is provided', function () {
    $this->delete->execute();
  })->throws(
    Exception::class,
    'A WHERE clause is required for DELETE operations',
  );

  it('should execute the statement', function () {
    $mockWhere = Mockery::mock(WhereInterface::class, [ 'getSql' => '<WHERE>' ]);

    $result = $this->delete
      ->where($mockWhere)
      ->execute();

    expect($result)->toBe(1);
  });

});
