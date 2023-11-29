<?php

use Tribal2\DbHandler\Queries\Insert;
use Tribal2\DbHandler\Queries\Select;

require_once __DIR__ . '/../Feature/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});

describe('Insert', function () {

  test('insert records in an autoincremented table', function () {
    $insert = Insert::into('test_table')
      ->value('key', 'this is a test key')
      ->value('value', 'this is a test value');

    $select = Select::from('test_table');

    // First row
    $insertResult1 = $insert->execute();
    expect($insertResult1)->toBeInt();
    expect($insertResult1)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records)->toHaveCount(3);
    expect($records[2]->key)->toBe('this is a test key');

    // Second row
    $insertResult2 = $insert->execute();
    expect($insertResult2)->toBeInt();
    expect($insertResult2)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records)->toHaveCount(4);
    expect($records[3]->key)->toBe('this is a test key');
  });

  test('insert records in NON autoincremented table without collision', function () {
    $insert = Insert::into('test_table_no_auto_increment')
      ->value('test_table_id', 3)
      ->value('key', 'this is a test key')
      ->value('value', 'this is a test value');

    $select = Select::from('test_table_no_auto_increment');

    // First row
    $insertResult1 = $insert->execute();
    expect($insertResult1)->toBeInt();
    expect($insertResult1)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records)->toHaveCount(3);
    expect($records[2]->key)->toBe('this is a test key');

    // Second row
    $insert->value('test_table_id', 4);
    $insertResult2 = $insert->execute();
    expect($insertResult2)->toBeInt();
    expect($insertResult2)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records)->toHaveCount(4);
    expect($records[3]->key)->toBe('this is a test key');
  });

  test('insert records in NON autoincremented table WITH collision should throw', function () {
    $insert = Insert::into('test_table_no_auto_increment')
      ->value('test_table_id', 5)
      ->value('key', 'this is a test key')
      ->value('value', 'this is a test value');

    // First row
    $insert->execute();

    // Second row (should throw)
    $insert->execute();
  })->throws(
    Exception::class,
    'The values you are trying to insert already exist in the database',
    409,
  );

});
