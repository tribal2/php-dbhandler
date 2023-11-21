<?php

use Tribal2\DbHandler\Queries\WhereClause;
use Tribal2\DbHandler\PDOBindBuilder;

describe('WhereClause - getSql()', function () {

  beforeEach(function() {
    $this->bindBuilder = new PDOBindBuilder();
  });

  test('equals creates a correct WhereClause', function () {
    $clause = WhereClause::equals('column', 'value');
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` = :column___1");
  });

  test('notEquals creates a correct WhereClause', function () {
    $clause = WhereClause::notEquals('column', 'value');
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` <> :column___1");
  });

  test('isNull creates a correct WhereClause', function () {
    $clause = WhereClause::isNull('column');
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` IS :column___1");
  });

  test('isNotNull creates a correct WhereClause', function () {
    $clause = WhereClause::isNotNull('column');
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` IS NOT :column___1");
  });

});


describe('WhereClause - getSqlForArrayOfValues()', function () {

  beforeEach(function() {
    $this->bindBuilder = new PDOBindBuilder();
  });

  test('in creates a correct WhereClause for an array of values', function () {
    $clause = WhereClause::in('column', ['value1', 'value2']);
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` IN (:column___1, :column___2)");

    $clause2 = WhereClause::notIn('column', ['value1', 'value2']);
    expect($clause2->getSql($this->bindBuilder))->toEqual("`column` NOT IN (:column___3, :column___4)");
  });

  test('between creates a correct WhereClause for range values', function () {
    $clause = WhereClause::between('column', 10, 20);
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` BETWEEN :column___1 AND :column___2");

    $clause2 = WhereClause::notBetween('column', 10, 20);
    expect($clause2->getSql($this->bindBuilder))->toEqual("`column` NOT BETWEEN :column___3 AND :column___4");
  });

});


describe('WhereClause - getSqlForArrayOfWhereClauses()', function () {

  beforeEach(function() {
    $this->bindBuilder = new PDOBindBuilder();
  });

  test('AND and getSql', function () {
    $clauseAnd = WhereClause::and(
      WhereClause::equals('column1', 'value1'),
      WhereClause::greaterThan('column2', 1),
      WhereClause::lessThanOrEquals('column3', 2),
    );

    $expectedSql = ""
      . "("
      .   "`column1` = :column1___1"
      .   " AND "
      .   "`column2` > :column2___1"
      .   " AND "
      .   "`column3` <= :column3___1"
      . ")";

    expect($clauseAnd->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

  test('OR and getSqlForArrayOfValues', function () {
    $clauseOr = WhereClause::or(
      WhereClause::in('column', ['value1', 'value2']),
      WhereClause::notIn('column', ['value3', 'value4']),
    );

    $expectedSql = ""
      . "("
      .   "`column` IN (:column___1, :column___2)"
      .   " OR "
      .   "`column` NOT IN (:column___3, :column___4)"
      . ")";
    expect($clauseOr->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

  test('mix of AND / OR', function () {
    $clauseOr = WhereClause::or(
      WhereClause::and(
        WhereClause::greaterThan('column1', 1),
        WhereClause::lessThanOrEquals('column1', 5),
      ),
      WhereClause::and(
        WhereClause::greaterThan('column2', 1),
        WhereClause::lessThanOrEquals('column2', 5),
      ),
    );

    $expectedSql = ""
      . "("
      .   "("
      .     "`column1` > :column1___1"
      .     " AND "
      .     "`column1` <= :column1___2"
      .   ")"
      .   " OR "
      .   "("
      .     "`column2` > :column2___1"
      .     " AND "
      .     "`column2` <= :column2___2"
      .   ")"
      . ")";
    expect($clauseOr->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

  test('nested ANDs', function () {
    $clauseAnd = WhereClause::and(
      WhereClause::equals('column1', 'value1'),
      WhereClause::and(
        WhereClause::greaterThan('column2', 1),
        WhereClause::and(
          WhereClause::lessThanOrEquals('column3', 2),
          WhereClause::equals('column4', 'value4'),
        ),
      ),
    );

    $expectedSql = ""
      . "("
      .   "`column1` = :column1___1"
      .   " AND "
      .   "("
      .     "`column2` > :column2___1"
      .     " AND "
      .     "("
      .       "`column3` <= :column3___1"
      .       " AND "
      .       "`column4` = :column4___1"
      .     ")"
      .   ")"
      . ")";

    expect($clauseAnd->getSql($this->bindBuilder))->toEqual($expectedSql);
  });

});


describe('WhereClause - verify sql after binding values', function () {

  beforeEach(function() {
    $this->bindBuilder = new PDOBindBuilder();
  });

  test('numeric values', function () {
    $clause = WhereClause::in('column', [100, 200]);
    $sql = $clause->getSql($this->bindBuilder);

    expect($sql)->toEqual("`column` IN (:column___1, :column___2)");
    expect($this->bindBuilder->debugQuery($sql))->toEqual("`column` IN (100, 200)");
  });

  test('string values', function () {
    $clause = WhereClause::in('column', ['first', 'second']);
    $sql = $clause->getSql($this->bindBuilder);

    expect($sql)->toEqual("`column` IN (:column___1, :column___2)");
    expect($this->bindBuilder->debugQuery($sql))->toEqual("`column` IN ('first', 'second')");
  });

});
