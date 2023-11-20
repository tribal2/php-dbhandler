<?php

use Tribal2\DbHandler\Enums\SqlValueTypeEnum;
use Tribal2\DbHandler\Queries\Common;

describe('quoteWrap()', function () {

  test('wraps non-function column names with backticks', function () {
    $columnName = 'column_name';
    $expected = '`column_name`';
    $result = Common::quoteWrap($columnName);

    expect($result)->toBe($expected);
  });

  test('does not wrap function-like column names with backticks', function () {
    $columnName = 'COUNT(*)';
    $result = Common::quoteWrap($columnName);

    expect($result)->toBe($columnName);
  });

});

describe('parseColumns()', function () {

  test('returns single quoted column name when passed a string', function () {
    $column = 'username';
    $expected = '`username`';

    $result = Common::parseColumns($column);
    expect($result)->toBe($expected);
  });

  test('returns multiple quoted column name when passed a string of comma separated columns', function () {
    $column = 'username, password';
    $expected = '`username`, `password`';

    $result = Common::parseColumns($column);
    expect($result)->toBe($expected);
  });

  test('returns comma-separated quoted column names when passed an array', function () {
    $columns = ['username', 'email'];
    $expected = '`username`, `email`';

    $result = Common::parseColumns($columns);

    expect($result)->toBe($expected);
  });

  test('does not quote function calls', function () {
    $columns = ['COUNT(*)', 'username'];
    $expected = 'COUNT(*), `username`';

    $result = Common::parseColumns($columns);

    expect($result)->toBe($expected);
  });

});

describe('checkValue()', function () {

  test('does not throw an exception when setting a string value', function() {
    Common::checkValue('some_value', 'column_name');
  })->throwsNoExceptions();

  test('does not throw an exception when setting a numeric value', function() {
    Common::checkValue(123, 'column_name');
  })->throwsNoExceptions();

  test('throws an exception when trying to set an array value', function() {
    Common::checkValue(['some', 'value']);

  })->throws(Exception::class, NULL, 500);

  test('throws an exception when trying to set an object value', function() {
    $obj = new stdClass();
    Common::checkValue($obj);

  })->throws(Exception::class, NULL, 500);

  test('throws an exception when trying to set a string and a numeric value is expected', function() {
    Common::checkValue('not_a_number', 'column_name', [ SqlValueTypeEnum::INTEGER ]);
  })->throws(
    Exception::class,
    "The value to write in the database must be number. The value entered for 'column_name' is of type 'string'.",
    500
  );
});
