<?php

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
