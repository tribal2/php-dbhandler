<?php

use Tribal2\DbHandler\Queries\Common;

describe('Common', function () {

  test('quoteWrap wraps non-function column names with backticks', function () {
    $columnName = 'column_name';
    $expected = '`column_name`';
    $result = Common::quoteWrap($columnName);

    expect($result)->toBe($expected);
  });

  test('quoteWrap does not wrap function-like column names with backticks', function () {
    $columnName = 'COUNT(*)';
    $result = Common::quoteWrap($columnName);

    expect($result)->toBe($columnName);
  });

});
