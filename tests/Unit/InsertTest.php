<?php

use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Insert;

require_once __DIR__ . '/../Feature/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});

describe('Builder', function () {

  test('static factory', function () {
    expect(Insert::into('my_table'))->toBeInstanceOf(Insert::class);
  });

});


describe('Insert values', function () {

  test('value() adds a column/value to the insert query', function () {
    $values = Insert::into('test_table')
      ->value('value', 'value1')
      ->getValues();

    expect($values)->toBeArray();
    expect($values)->toHaveCount(1);
    expect($values['value'])->toBe('value1');
  });

  test('values() adds multiple values correctly', function () {
    $values = Insert::into('test_table')
      ->values([
        'key' => 'key1',
        'value' => 'value',
      ])
      ->getValues();

    expect($values)->toBeArray();
    expect($values)->toHaveCount(2);
    expect($values['key'])->toBe('key1');
    expect($values['value'])->toBe('value');
  });

  test('value() and values() chained', function () {
    $values = Insert::into('test_table')
      ->value('key', 'key1')
      ->values([
        'value' => 25,
        'created_at' => '2020-01-01 00:00:00',
      ])
      ->getValues();

    expect($values)->toBeArray();
    expect($values)->toHaveCount(3);
    expect($values['key'])->toBe('key1');
    expect($values['value'])->toBe(25);
    expect($values['created_at'])->toBe('2020-01-01 00:00:00');
  });

  test('value() ignores columns that are not on the table', function () {
    $values = Insert::into('test_table')
      ->value('column1', 'value1')
      ->getValues();

    expect($values)->toBeArray();
    expect($values)->toHaveCount(0);
  });

  test('value() throws on invalid value', function () {
    Insert::into('test_table')
      ->value('value', [ 1, 2, 3 ])
      ->getValues();
  })->throws(Exception::class);

});


describe('SQL', function () {

  test('getSql() returns a valid SQL string', function () {
    $bindBuilder = new PDOBindBuilder();
    $sql = Insert::into('test_table')
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

});
