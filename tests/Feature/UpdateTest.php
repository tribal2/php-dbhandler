<?php

use Tribal2\DbHandler\Factories\ColumnsFactory;
use Tribal2\DbHandler\Queries\Select;
use Tribal2\DbHandler\Queries\Update;
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
    Update::_table('test_table', $myPdo, new ColumnsFactory($myPdo))
      ->set('value', 'updated value')
      ->execute();
  })->throws(
    Exception::class,
    "Can't execute statement. Read only mode is enabled.",
    409,
  );

});


describe('Update', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
    $this->columnsFactory = new ColumnsFactory($this->myPdo);
  });

  test('update records with Where', function () {
    $where = Where::equals('test_table_id', 1);

    $updateResult = Update::_table('test_table', $this->myPdo, $this->columnsFactory)
      ->set('value', 'updated value')
      ->where($where)
      ->execute();

    expect($updateResult)->toBeInt();
    expect($updateResult)->toBe(1);

    $updatedRow = Select::_from('test_table', $this->myPdo)
      ->where($where)
      ->fetchFirst();

    // check if the record was updated
    expect($updatedRow->value)->toBe('updated value');
  });

  test('update all records', function () {
    $updateResult = Update::_table('test_table', $this->myPdo, $this->columnsFactory)
      ->set('value', 'updated value')
      ->execute();

    expect($updateResult)->toBeInt();
    expect($updateResult)->toBe(1);

    $updatedRows = Select::_from('test_table', $this->myPdo)
      ->where(Where::equals('value', 'updated value'))
      ->fetchAll();

    // check if all records were updated
    expect($updatedRows->data)
      ->toBeArray()
      ->toHaveLength(2);
    expect($updatedRows->data[0]->value)->toBe('updated value');
    expect($updatedRows->data[1]->value)->toBe('updated value');
  });

});
