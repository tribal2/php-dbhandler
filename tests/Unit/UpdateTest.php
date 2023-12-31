<?php

use Tribal2\DbHandler\Interfaces\ColumnsFactoryInterface;
use Tribal2\DbHandler\Interfaces\ColumnsInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\Queries\Update;

describe('Update Builder', function () {

  test('constructor', function () {
    $update = new Update(
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(CommonInterface::class),
    );

    expect($update)->toBeInstanceOf(Update::class);
  });

  test('static factory', function () {
    $mockColumns = Mockery::mock(ColumnsInterface::class, [ 'has' => FALSE ]);

    $update = Update::_table(
      'test_table',
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(ColumnsFactoryInterface::class, [ 'make' => $mockColumns ]),
      Mockery::mock(CommonInterface::class),
    );

    expect($update)->toBeInstanceOf(Update::class);
  });

});


describe('set()', function () {

  test('should throw on invalid column name', function () {
    $mockColumns = Mockery::mock(ColumnsInterface::class, [ 'has' => FALSE ]);

    $update = Update::_table(
      'test_table',
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(ColumnsFactoryInterface::class, [ 'make' => $mockColumns ]),
      Mockery::mock(CommonInterface::class),
    );

    $update->set('invalid_key', 'updated_key');
  })->throws(
    Exception::class,
    // Column '{$column}' does not exist in table '{$this->table}'
    "Column 'invalid_key' does not exist in table 'test_table'",
    400,
  );

  test('should throw on invalid value type', function () {
    $mockCommon = Mockery::mock(CommonInterface::class);
    $mockCommon
      ->shouldReceive('checkValue')
      ->andThrows(Exception::class, '<ERROR_MESSAGE>', 500)
      ->getMock();

    $mockColumns = Mockery::mock(ColumnsInterface::class, [ 'has' => TRUE ]);

    $update = Update::_table(
      'test_table',
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(ColumnsFactoryInterface::class, [ 'make' => $mockColumns ]),
      $mockCommon,
    );

    $update->set('key', [ 'updated_key' ]);
  })->throws(
    Exception::class,
    '<ERROR_MESSAGE>',
    500,
  );

});


describe('SQL', function () {

  beforeEach(function () {
    $mockCommon = Mockery::mock(CommonInterface::class);
    $mockCommon
      ->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR)->getMock()
      ->shouldReceive('quoteWrap')->andReturn('<WRAPPED_VALUE>')->getMock()
      ->shouldReceive('parseColumns')->andReturn('<COLUMNS>')->getMock();

    $mockColumns = Mockery::mock(ColumnsInterface::class, [ 'has' => TRUE ]);

    $this->update = Update::_table(
      'test_table',
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(ColumnsFactoryInterface::class, [ 'make' => $mockColumns ]),
      $mockCommon,
    );

    $this->mockBindBuilder = Mockery::mock(PDOBindBuilderInterface::class)
      ->shouldReceive('addValueWithPrefix')->andReturn('<BINDED_VALUE>')
      ->getMock();

    $this->mockWhere = Mockery::mock(WhereInterface::class)
      ->shouldReceive('getSql')->andReturn('<WHERE>')
      ->getMock();
  });

  test('update a single value', function () {
    $updateSql = $this->update
      ->set('key', 'value1')
      ->getSql($this->mockBindBuilder);

      $expectedSql = ''
      . 'UPDATE <WRAPPED_VALUE> '
      . 'SET '
      .   '<WRAPPED_VALUE> = <BINDED_VALUE>;';

    expect($updateSql)->toBeString();
    expect($updateSql)->toBe($expectedSql);
  });

  test('update multiple values of different type', function () {
    $updateSql = $this->update
      ->set('key', 'updated_key')
      ->set('value', 123)
      ->getSql($this->mockBindBuilder);

    $expectedSql = ''
      . 'UPDATE <WRAPPED_VALUE> '
      . 'SET '
      .   '<WRAPPED_VALUE> = <BINDED_VALUE>, '
      .   '<WRAPPED_VALUE> = <BINDED_VALUE>;';

    expect($updateSql)->toBeString();
    expect($updateSql)->toBe($expectedSql);
  });

  test('single value with WHERE clause', function () {
    $updateSql = $this->update
      ->set('key', 'updated_key')
      ->set('value', 123)
      ->where($this->mockWhere)
      ->getSql($this->mockBindBuilder);

      $expectedSql = ''
      . 'UPDATE <WRAPPED_VALUE> '
      . 'SET '
      .   '<WRAPPED_VALUE> = <BINDED_VALUE>, '
      .   '<WRAPPED_VALUE> = <BINDED_VALUE> '
      . 'WHERE <WHERE>;';

    expect($updateSql)->toBeString();
    expect($updateSql)->toBe($expectedSql);
  });

  it('should throw when no value is set', function () {
    $this->update
      ->getSql($this->mockBindBuilder);
  })->throws(
    Exception::class,
    'No values provided for update',
    400,
  );

});


describe('Execution', function () {

  beforeEach(function () {
    $mockCommon = Mockery::mock(CommonInterface::class);
    $mockCommon
      ->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR)->getMock()
      ->shouldReceive('quoteWrap')->andReturn('<WRAPPED_VALUE>')->getMock()
      ->shouldReceive('parseColumns')->andReturn('<COLUMNS>')->getMock();

    $mockColumns = Mockery::mock(ColumnsInterface::class, [ 'has' => TRUE ]);

    $mockPdoWrapper = Mockery::mock(PDOWrapperInterface::class, [
      'isReadOnly' => FALSE,
      'execute' => Mockery::mock(PDOStatement::class, [
        'rowCount' => 1,
      ]),
    ]);

    $this->update = Update::_table(
      'test_table',
      $mockPdoWrapper,
      Mockery::mock(ColumnsFactoryInterface::class, [ 'make' => $mockColumns ]),
      $mockCommon,
    );
  });

  test('update a single value', function () {
    $updateResult = $this->update
      ->set('key', 'value1')
      ->execute();

    expect($updateResult)
      ->toBeInt()
      ->toBe(1);
  });

});
