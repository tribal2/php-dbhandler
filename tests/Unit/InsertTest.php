<?php

use Tribal2\DbHandler\Interfaces\ColumnsFactoryInterface;
use Tribal2\DbHandler\Interfaces\ColumnsInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\WhereFactoryInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\Queries\Insert;


describe('Builder', function () {

  test('constructor', function () {
    $insert = new Insert(
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(CommonInterface::class),
    );

    expect($insert)->toBeInstanceOf(Insert::class);
  });

});


describe('Insert values', function () {

  beforeEach(function () {
    $mockColumns = Mockery::mock(ColumnsInterface::class, [ 'has' => TRUE ]);

    $this->insert = Insert::_into(
      'test_table',
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(ColumnsFactoryInterface::class, [ 'make' => $mockColumns ]),
      Mockery::mock(WhereFactoryInterface::class),
      Mockery::mock(CommonInterface::class, [ 'checkValue' => PDO::PARAM_STR ]),
    );
  });

  test('value() adds a column/value to the insert query', function () {
    $values = $this->insert
      ->value('value', 'value1')
      ->getValues();

    expect($values[0])->toBeArray();
    expect($values[0])->toHaveCount(1);
    expect($values[0]['value'])->toBe('value1');
  });

  test('values() adds multiple values correctly', function () {
    $values = $this->insert
      ->values([
        'key' => 'key1',
        'value' => 'value',
      ])
      ->getValues();

    expect($values[0])->toBeArray();
    expect($values[0])->toHaveCount(2);
    expect($values[0]['key'])->toBe('key1');
    expect($values[0]['value'])->toBe('value');
  });

  test('value() and values() chained', function () {
    $values = $this->insert
      ->value('key', 'key1')
      ->values([
        'value' => 25,
        'created_at' => '2020-01-01 00:00:00',
      ])
      ->getValues();

    expect($values[0])->toBeArray();
    expect($values[0])->toHaveCount(3);
    expect($values[0]['key'])->toBe('key1');
    expect($values[0]['value'])->toBe(25);
    expect($values[0]['created_at'])->toBe('2020-01-01 00:00:00');
  });

  test('value() ignores columns that are not on the table', function () {
    $mockColumns = Mockery::mock(ColumnsInterface::class, [ 'has' => FALSE ]);
    $values = Insert::_into(
      'test_table',
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(ColumnsFactoryInterface::class, [ 'make' => $mockColumns ]),
      Mockery::mock(WhereFactoryInterface::class),
      Mockery::mock(CommonInterface::class, [ 'checkValue' => PDO::PARAM_STR ]),
    )
      ->value('column1', 'value1')
      ->getValues();

    expect($values[0])->toBeArray();
    expect($values[0])->toHaveCount(0);
  });

  test('value() throws on invalid value', function () {
    $mockedCommon = Mockery::mock(CommonInterface::class);
    $mockedCommon->shouldReceive('checkValue')->andThrow(Exception::class);

    $mockColumns = Mockery::mock(ColumnsInterface::class, [ 'has' => TRUE ]);

    Insert::_into(
      'test_table',
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(ColumnsFactoryInterface::class, [ 'make' => $mockColumns ]),
      Mockery::mock(WhereFactoryInterface::class),
      $mockedCommon,
    )->value('value', [ 1, 2, 3 ]);
  })->throws(Exception::class);

  test('rows()', function () {
    $values = $this->insert
      ->rows([
        [ 'key' => 'key1', 'value' => 25 ],
        [ 'key' => 'key2' ],
      ])
      ->getValues();

    expect($values)->toBeArray();
    expect($values)->toHaveCount(2);
    expect($values[0])->toBeArray();
    expect($values[0])->toHaveCount(2);
    expect($values[1])->toBeArray();
    expect($values[1])->toHaveCount(2);
    expect($values[0]['key'])->toBe('key1');
    expect($values[0]['value'])->toBe(25);
    expect($values[1]['key'])->toBe('key2');
    expect($values[1]['value'])->toBeNull();
  });

});


describe('SQL', function () {

  beforeEach(function () {
    $mockCommon = Mockery::mock(CommonInterface::class);
    $mockCommon
      ->shouldReceive('quoteWrap')->with('test_table')->andReturn('`test_table`')->getMock()
      ->shouldReceive('quoteWrap')->with('key')->andReturn('`key`')->getMock()
      ->shouldReceive('quoteWrap')->with('value')->andReturn('`value`')->getMock()
      ->shouldReceive('quoteWrap')->with('created_at')->andReturn('`created_at`')->getMock()
      ->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR)->getMock();

    $mockColumns = Mockery::mock(ColumnsInterface::class, [ 'has' => TRUE ]);

    $this->insert = Insert::_into(
      'test_table',
      Mockery::mock(PDOWrapperInterface::class),
      Mockery::mock(ColumnsFactoryInterface::class, [ 'make' => $mockColumns ]),
      Mockery::mock(WhereFactoryInterface::class),
      $mockCommon,
    );

    $this->mockBindBuilder = Mockery::mock(PDOBindBuilderInterface::class)
      ->shouldReceive('addValueWithPrefix')->andReturn('<BINDED_VALUE>')
      ->getMock();
  });

  test('getSql() returns a valid SQL string', function () {
    $sql = $this->insert
      ->value('key', 25)
      ->values([
        'value' => NULL,
        'created_at' => '2020-01-01 00:00:00',
      ])
      ->getSql($this->mockBindBuilder);

    $expected = "INSERT INTO `test_table` (`key`, `value`, `created_at`) "
      . "VALUES (<BINDED_VALUE>, <BINDED_VALUE>, <BINDED_VALUE>);";

    expect($sql)->toBeString();
    expect($sql)->toBe($expected);
  });

  test('getSql() returns a valid SQL string when using rows() to insert multiple rows', function () {
    $sql = $this->insert
      ->rows([
        [ 'key' => 'key1', 'value' => 'value1' ],
        [ 'key' => 'key2', 'value' => 25 ],
        [ 'key' => 'key3' ],
      ])
      ->getSql($this->mockBindBuilder);

    $expected = "INSERT INTO `test_table` (`key`, `value`) VALUES "
      . "(<BINDED_VALUE>, <BINDED_VALUE>), "
      . "(<BINDED_VALUE>, <BINDED_VALUE>), "
      . "(<BINDED_VALUE>, <BINDED_VALUE>);";

    expect($sql)->toBeString();
    expect($sql)->toBe($expected);
  });

  test('getSql() throws when no value is set', function () {
    $this->insert
      ->getSql($this->mockBindBuilder);
  })->throws(
    Exception::class,
    'You must provide at least one value to insert',
  );

});


describe('Execution', function () {

  beforeEach(function () {
    $mockCommon = Mockery::mock(CommonInterface::class);
    $mockCommon
      ->shouldReceive('quoteWrap')->with('test_table')->andReturn('`test_table`')->getMock()
      ->shouldReceive('quoteWrap')->with('key')->andReturn('`key`')->getMock()
      ->shouldReceive('quoteWrap')->with('value')->andReturn('`value`')->getMock()
      ->shouldReceive('quoteWrap')->with('created_at')->andReturn('`created_at`')->getMock()
      ->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR)->getMock();

    $mockColumns = Mockery::mock(ColumnsInterface::class, [
      'has' => TRUE,
    ]);
    $mockColumns->autoincrement = [];
    $mockColumns->key = ['key'];

    $mockPdo = Mockery::mock(PDOWrapperInterface::class, [
      'isReadOnly' => FALSE,
      'execute' => Mockery::mock(PDOStatement::class, [
        'fetchAll' => [],
        'rowCount' => 1,
      ]),
    ]);

    $mockWhere = Mockery::mock(WhereInterface::class, [
      'getSql' => 'WHERE 1',
      'getValues' => [],
    ]);

    $this->insert = Insert::_into(
      'test_table',
      $mockPdo,
      Mockery::mock(ColumnsFactoryInterface::class, [ 'make' => $mockColumns ]),
      Mockery::mock(WhereFactoryInterface::class, [ 'make' => $mockWhere ]),
      $mockCommon,
    );
  });

  test('execute() returns the number of inserted rows', function () {
    $insertedRows = $this->insert
      ->value('key', 25)
      ->values([
        'value' => 'asdfadsf',
        'created_at' => '2020-01-01 00:00:00',
      ])
      ->execute();

    var_dump($insertedRows);

    expect($insertedRows)
      ->toBeInt()
      ->toEqual(1);
  });

});
