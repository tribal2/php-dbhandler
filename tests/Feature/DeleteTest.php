<?php

use Tribal2\DbHandler\Queries\Delete;
use Tribal2\DbHandler\Queries\Where;

require_once __DIR__ . '/../Feature/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});


describe('Read only mode', function () {

  test('insert records should throw', function () {
    $myPdo = DbTestSchema::getReadOnlyPdoWrapper();
    Delete::_from('test_table', $myPdo)
      ->execute();
  })->throws(
    Exception::class,
    "Can't execute statement. Read only mode is enabled.",
    409,
  );

});


describe('Delete', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
  });

  test('delete records with Where', function () {
    $deleteCount = Delete::_from('test_table', $this->myPdo)
      ->where(Where::equals('test_table_id', 1))
      ->execute();

    expect($deleteCount)->toBeInt();
    expect($deleteCount)->toBe(1);
  });

  test('delete multiple records', function () {
    $deleteCount = Delete::_from('test_table_no_auto_increment', $this->myPdo)
      ->where(Where::like('key', 'test%'))
      ->execute();

    expect($deleteCount)->toBeInt();
    expect($deleteCount)->toBe(2);
  });

});
