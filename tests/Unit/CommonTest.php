<?php

use Tribal2\DbHandler\Enums\SqlValueTypeEnum;
use Tribal2\DbHandler\Queries\Common;

describe('quoteWrap()', function () {

  beforeEach(function () {
    $this->common = new Common();
  });

  test('wraps non-function column names with backticks', function () {
    $columnName = 'column_name';
    $expected = '`column_name`';
    $result = $this->common->quoteWrap($columnName);

    expect($result)->toBe($expected);
  });

  test('does not wrap function-like column names with backticks', function () {
    $columnName = 'COUNT(*)';
    $result = $this->common->quoteWrap($columnName);

    expect($result)->toBe($columnName);
  });

});

describe('parseColumns()', function () {

  beforeEach(function () {
    $this->common = new Common();
  });

  test('returns single quoted column name when passed a string', function () {
    $column = 'username';
    $expected = '`username`';

    $result = $this->common->parseColumns($column);
    expect($result)->toBe($expected);
  });

  test('returns multiple quoted column name when passed a string of comma separated columns', function () {
    $column = 'username, password';
    $expected = '`username`, `password`';

    $result = $this->common->parseColumns($column);
    expect($result)->toBe($expected);
  });

  test('returns comma-separated quoted column names when passed an array', function () {
    $columns = ['username', 'email'];
    $expected = '`username`, `email`';

    $result = $this->common->parseColumns($columns);

    expect($result)->toBe($expected);
  });

  test('does not quote function calls', function () {
    $columns = ['COUNT(*)', 'username'];
    $expected = 'COUNT(*), `username`';

    $result = $this->common->parseColumns($columns);

    expect($result)->toBe($expected);
  });

});

describe('checkValue()', function () {

  beforeEach(function () {
    $this->common = new Common();
  });

  test('should return PDO::PARAM_INT when a integer or numeric integer is provided', function() {
    $res = $this->common->checkValue('12345', 'column_name', [ SqlValueTypeEnum::INTEGER ]);
    expect($res)->toBe(PDO::PARAM_INT);

    $res2 = $this->common->checkValue(12345);
    expect($res2)->toBe(PDO::PARAM_INT);
  });

  test('should return PDO::PARAM_STR when a float or numeric float is provided', function() {
    $res = $this->common->checkValue('123.45', 'column_name', [ SqlValueTypeEnum::INTEGER ]);
    expect($res)->toBe(PDO::PARAM_STR);

    $res = $this->common->checkValue(123.45);
    expect($res)->toBe(PDO::PARAM_STR);
  });

  test('should return PDO::PARAM_BOOL when a boolean is provided', function() {
    $res = $this->common->checkValue(TRUE, 'column_name', [ SqlValueTypeEnum::BOOLEAN ]);
    expect($res)->toBe(PDO::PARAM_BOOL);

    $res = $this->common->checkValue(FALSE);
    expect($res)->toBe(PDO::PARAM_BOOL);
  });

  test('should return PDO::STR when a non-numerique string is provided', function() {
    $res = $this->common->checkValue('TRUE', 'column_name', [ SqlValueTypeEnum::STRING ]);
    expect($res)->toBe(PDO::PARAM_STR);

    $res = $this->common->checkValue('I am a string');
    expect($res)->toBe(PDO::PARAM_STR);
  });

  test('does not throw an exception when setting a string value', function() {
    $this->common->checkValue('some_value', 'column_name');
  })->throwsNoExceptions();

  test('does not throw an exception when setting a numeric value', function() {
    $this->common->checkValue(123, 'column_name');
  })->throwsNoExceptions();

  test('throws an exception when trying to set an array value', function() {
    $this->common->checkValue(['some', 'value']);

  })->throws(Exception::class, NULL, 500);

  test('throws an exception when trying to set an object value', function() {
    $obj = new stdClass();
    $this->common->checkValue($obj);

  })->throws(Exception::class, NULL, 500);

  test('throws an exception when trying to set a string and a numeric value is expected', function() {
    $this->common->checkValue('not_a_number', 'column_name', [ SqlValueTypeEnum::INTEGER ]);
  })->throws(
    Exception::class,
    "The value to write in the database must be number. The value entered for 'column_name' is of type 'string'.",
    500
  );
});
