<?php

use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Update;
use Tribal2\DbHandler\Queries\Where;

require_once __DIR__ . '/../Feature/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});

describe('Update Builder', function () {

  test('static factory', function () {
    expect(Update::table('my_table'))->toBeInstanceOf(Update::class);
  });

});


describe('set()', function () {

  test('should throw on invalid column name', function () {
    Update::table('test_table')
      ->set('invalid_key', 'updated_key');
  })->throws(
    Exception::class,
    // Column '{$column}' does not exist in table '{$this->table}'
    "Column 'invalid_key' does not exist in table 'test_table'",
    400,
  );

  test('should throw on invalid value type', function () {
    Update::table('test_table')
      ->set('key', [ 'updated_key' ]);
  })->throws(
    Exception::class,
    "The value to write in the database must be string or number or NULL or "
      . "boolean. The value entered for 'key' is of type 'array'.",
    500,
  );

});


describe('SQL', function () {

  test('update a single value', function () {
    $bindBuilder = new PDOBindBuilder();

    $updateSql = Update::table('test_table')
      ->set('key', 'value1')
      ->getSql($bindBuilder);

    $expectedSql = 'UPDATE `test_table` SET `key` = :key___1;';

    expect($updateSql)->toBeString();
    expect($updateSql)->toBe($expectedSql);

    $expectedSqlWithValues = "UPDATE `test_table` SET `key` = 'value1';";

    expect($bindBuilder->debugQuery($updateSql))->toBe($expectedSqlWithValues);
  });

  test('update multiple values of different type', function () {
    $bindBuilder = new PDOBindBuilder();

    $updateSql = Update::table('test_table')
      ->set('key', 'updated_key')
      ->set('value', 123)
      ->getSql($bindBuilder);

    $expectedSql = 'UPDATE `test_table` SET `key` = :key___1, `value` = :value___1;';

    expect($updateSql)->toBeString();
    expect($updateSql)->toBe($expectedSql);

    $expectedSqlWithValues = "UPDATE `test_table` SET `key` = 'updated_key', `value` = 123;";

    expect($bindBuilder->debugQuery($updateSql))->toBe($expectedSqlWithValues);
  });

  test('single value with WHERE clause', function () {
    $bindBuilder = new PDOBindBuilder();

    $updateSql = Update::table('test_table')
      ->set('key', 'updated_key')
      ->where(Where::equals('test_table_id', 1))
      ->getSql($bindBuilder);

    $expectedSql = 'UPDATE `test_table` SET `key` = :key___1 WHERE `test_table_id` = :test_table_id___1;';

    expect($updateSql)->toBeString();
    expect($updateSql)->toBe($expectedSql);

    $expectedSqlWithValues = "UPDATE `test_table` SET `key` = 'updated_key' WHERE `test_table_id` = 1;";

    expect($bindBuilder->debugQuery($updateSql))->toBe($expectedSqlWithValues);
  });

});
