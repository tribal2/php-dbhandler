<?php

use Tribal2\DbHandler\Queries\Select;
use Tribal2\DbHandler\Queries\Where;

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

  test('simple where', function () {
    $builder = new Select('my_table');
    $builder->columns(['column1', 'column2']);
    $builder->where(
      Where::equals('column1', 'value1')
    );

    $expected = 'SELECT `column1`, `column2` FROM `my_table` WHERE `column1` = :column1___1;';
    expect($builder->getSql())->toBe($expected);
  });

  test('where with a mix of or/and', function () {
    $builder = new Select('my_table');
    $builder->columns(['column1', 'column2']);
    $builder->where(
      Where::or(
        Where::equals('column1', 'value11'),
        Where::and(
          Where::equals('column1', 'value12'),
          Where::equals('column3', 'value3')
        )
      ),
    );

    $expected = "SELECT `column1`, `column2` FROM `my_table` WHERE "
      . "("
      .   "`column1` = :column1___1 "
      .   "OR ("
      .     "`column1` = :column1___2 "
      .     "AND `column3` = :column3___1"
      .   ")"
      . ");";
    expect($builder->getSql())->toBe($expected);
  });

});


describe('SELECT builder with grouping', function () {
  test('simple where', function () {
    $builder = new Select('my_table');
    $builder->column('column1')
      ->column('sum(column2)')
      ->groupBy('column1')
      ->having(Where::greaterThan('sum(column2)', 0));

    $expected = "SELECT `column1`, sum(column2) FROM `my_table` "
      . "GROUP BY `column1` HAVING sum(column2) > :sum_column2____1;";

    expect($builder->getSql())->toBe($expected);
  });
});
