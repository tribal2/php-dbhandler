<?php

use Tribal2\DbHandler\Queries\Select;

describe('SELECT builder', function () {

  test('all columns from a table', function () {
    $builder = new Select('my_table');

    expect($builder->getSql())->toBe('SELECT * FROM `my_table`;');
  });

  test('throws on invalid columns', function () {
    $builder = new Select('my_table');
    $builder->columns(['column1', 1234, TRUE]);
  })->throws(\Exception::class);

  test('first 5 records of table with column1 and column2', function () {
    $builder = new Select('my_table');
    $builder->columns(['column1', 'column2']);
    $builder->limit(5);

    $expected = 'SELECT `column1`, `column2` FROM `my_table` LIMIT :limit___1;';
    expect($builder->getSql())->toBe($expected);
  });

});
