<?php

use Tribal2\DbHandler\Queries\WhereClause;
use Tribal2\DbHandler\PDOBindBuilder;

describe('WhereClause', function () {

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

  test('isNull creates a correct WhereClause', function () {
    $clause = WhereClause::isNull('column');
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` IS :column___1");
  });

  test('isNotNull creates a correct WhereClause', function () {
    $clause = WhereClause::isNotNull('column');
    expect($clause->getSql($this->bindBuilder))->toEqual("`column` IS NOT :column___1");
  });

  test('verify sql after binding numeric values', function () {
    $clause = WhereClause::in('column', [100, 200]);
    $sql = $clause->getSql($this->bindBuilder);

    expect($sql)->toEqual("`column` IN (:column___1, :column___2)");
    expect($this->bindBuilder->debugQuery($sql))->toEqual("`column` IN (100, 200)");
  });

  test('verify sql after binding string values', function () {
    $clause = WhereClause::in('column', ['first', 'second']);
    $sql = $clause->getSql($this->bindBuilder);

    expect($sql)->toEqual("`column` IN (:column___1, :column___2)");
    expect($this->bindBuilder->debugQuery($sql))->toEqual("`column` IN ('first', 'second')");
  });

});
