<?php

use Tribal2\DbHandler\Interfaces\ColumnsInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Insert;


describe('Builder', function () {

  test('static factory', function () {
    $insert = new Insert('my_table', [
      '_pdo' => Mockery::mock(PDO::class),
      '_columns' => Mockery::mock(ColumnsInterface::class),
      '_common' => Mockery::mock(CommonInterface::class),
    ]);

    expect($insert)->toBeInstanceOf(Insert::class);
  });

});


describe('Insert values', function () {

  beforeEach(function () {
    $this->dependencies = [
      '_pdo' => Mockery::mock(PDO::class),
      '_columns' => Mockery::mock(ColumnsInterface::class, [ 'has' => TRUE ]),
      '_common' => Mockery::mock(CommonInterface::class, [ 'checkValue' => NULL ]),
    ];
  });

  test('value() adds a column/value to the insert query', function () {
    $insert = new Insert('test_table', $this->dependencies);
    $values = $insert
      ->value('value', 'value1')
      ->getValues();

    expect($values[0])->toBeArray();
    expect($values[0])->toHaveCount(1);
    expect($values[0]['value'])->toBe('value1');
  });

  test('values() adds multiple values correctly', function () {
    $insert = new Insert('test_table', $this->dependencies);
    $values = $insert
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
    $insert = new Insert('test_table', $this->dependencies);
    $values = $insert
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
    $this->dependencies['_columns']->shouldReceive('has')->andReturn(FALSE);
    $insert = new Insert('test_table', $this->dependencies);
    $values = $insert
      ->value('column1', 'value1')
      ->getValues();

    expect($values[0])->toBeArray();
    expect($values[0])->toHaveCount(0);
  });

  test('value() throws on invalid value', function () {
    $this->dependencies['_common']->shouldReceive('checkValue')->andThrow(Exception::class);
    $insert = new Insert('test_table', $this->dependencies);
    $insert->value('value', [ 1, 2, 3 ]);
  })->throws(Exception::class);

  test('rows()', function () {
    $insert = new Insert('test_table', $this->dependencies);
    $values = $insert
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
    $this->dependencies = [
      '_pdo' => Mockery::mock(PDO::class),
      '_columns' => Mockery::mock(ColumnsInterface::class, [ 'has' => TRUE ]),
      '_common' => Mockery::mock(CommonInterface::class, [ 'checkValue' => NULL ]),
    ];
  });

  test('getSql() returns a valid SQL string', function () {
    $bindBuilder = new PDOBindBuilder();

    $this->dependencies['_common']
      ->shouldReceive('quoteWrap')->with('test_table')->andReturn('`test_table`')
      ->shouldReceive('quoteWrap')->with('key')->andReturn('`key`')
      ->shouldReceive('quoteWrap')->with('value')->andReturn('`value`')
      ->shouldReceive('quoteWrap')->with('created_at')->andReturn('`created_at`')
      ->shouldReceive('checkValue')->with(25, 'key')->andReturn(PDO::PARAM_INT)
      ->shouldReceive('checkValue')->with(NULL, 'value')->andReturn(PDO::PARAM_NULL)
      ->shouldReceive('checkValue')->with('2020-01-01 00:00:00', 'created_at')->andReturn(PDO::PARAM_STR);

    $insert = new Insert('test_table', $this->dependencies);
    $sql = $insert
      ->value('key', 25)
      ->values([
        'value' => NULL,
        'created_at' => '2020-01-01 00:00:00',
      ])
      ->getSql($bindBuilder);

    $expected = "INSERT INTO `test_table` (`key`, `value`, `created_at`) "
      . "VALUES (:key___1, :value___1, :created_at___1);";

    expect($sql)->toBeString();
    expect($sql)->toBe($expected);

    $expected2 = "INSERT INTO `test_table` (`key`, `value`, `created_at`) "
      . "VALUES (25, NULL, '2020-01-01 00:00:00');";

    expect($bindBuilder->debugQuery($sql))->toBe($expected2);
  });

  test('getSql() returns a valid SQL string when using rows() to insert multiple rows', function () {
    $bindBuilder = new PDOBindBuilder();

    $this->dependencies['_common']
      ->shouldReceive('quoteWrap')->with('test_table')->andReturn('`test_table`')
      ->shouldReceive('quoteWrap')->with('key')->andReturn('`key`')
      ->shouldReceive('quoteWrap')->with('value')->andReturn('`value`')
      ->shouldReceive('checkValue')->andReturn(PDO::PARAM_STR);

    $insert = new Insert('test_table', $this->dependencies);
    $sql = $insert
      ->rows([
        [ 'key' => 'key1', 'value' => 'value1' ],
        [ 'key' => 'key2', 'value' => 25 ],
        [ 'key' => 'key3' ],
      ])
      ->getSql($bindBuilder);

    $expected = "INSERT INTO `test_table` (`key`, `value`) VALUES "
      . "(:key___1, :value___1), "
      . "(:key___2, :value___2), "
      . "(:key___3, :value___3);";

    expect($sql)->toBeString();
    expect($sql)->toBe($expected);

    $expected2 = "INSERT INTO `test_table` (`key`, `value`) VALUES "
      . "('key1', 'value1'), "
      . "('key2', 25), "
      . "('key3', NULL);";

    expect($bindBuilder->debugQuery($sql))->toBe($expected2);
  });

});
