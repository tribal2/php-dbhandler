<?php

use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Delete;
use Tribal2\DbHandler\Queries\Where;

require_once __DIR__ . '/../Feature/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});


describe('Delete Builder', function () {

  test('static factory', function () {
    expect(Delete::from('my_table'))->toBeInstanceOf(Delete::class);
  });

});


describe('Delete SQL Generation', function () {

  test('getSql() generates correct SQL query', function () {
    $bindBuilder = new PDOBindBuilder();

    $delete = Delete::from('test_table')
      ->where(Where::equals('key', 'keyValue'));

    $sql = $delete->getSql($bindBuilder);
    $expectedSql = 'DELETE FROM `test_table` WHERE `key` = :key___1;';

    expect($sql)->toBeString();
    expect($sql)->toBe($expectedSql);

    $expectedSqlWithValues = "DELETE FROM `test_table` WHERE `key` = 'keyValue';";
    expect($bindBuilder->debugQuery($sql))->toBe($expectedSqlWithValues);
  });

});
