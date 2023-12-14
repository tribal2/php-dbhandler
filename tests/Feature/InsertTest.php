<?php

use Tribal2\DbHandler\Factories\ColumnsFactory;
use Tribal2\DbHandler\Queries\Insert;
use Tribal2\DbHandler\Queries\Select;
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
    Insert::_into(
      'test_table',
      $myPdo,
      $this->columnsFactory = new ColumnsFactory($myPdo),
    )
      ->value('key', 'this is a test key')
      ->value('value', 'this is a test value')
      ->execute();
  })->throws(
    Exception::class,
    "Can't execute statement. Read only mode is enabled.",
    409,
  );

});


describe('value()', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
    $this->columnsFactory = new ColumnsFactory($this->myPdo);
  });

  test('insert records in an autoincremented table', function () {
    $insert = Insert::_into('test_table', $this->myPdo, $this->columnsFactory)
      ->value('key', 'this is a test key')
      ->value('value', 'this is a test value');

    $select = Select::_from('test_table', $this->myPdo);

    // First row
    $insertResult1 = $insert->execute();
    expect($insertResult1)->toBeInt();
    expect($insertResult1)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records->data)->toHaveCount(3);
    expect($records->data[2]->key)->toBe('this is a test key');

    // Second row
    $insertResult2 = $insert->execute();
    expect($insertResult2)->toBeInt();
    expect($insertResult2)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records->data)->toHaveCount(4);
    expect($records->data[3]->key)->toBe('this is a test key');
  });

  test('insert records in NON autoincremented table without collision', function () {
    $insert = Insert::_into('test_table_no_auto_increment', $this->myPdo, $this->columnsFactory)
      ->value('test_table_id', 3)
      ->value('key', 'this is a test key')
      ->value('value', 'this is a test value');

    $select = Select::_from('test_table_no_auto_increment', $this->myPdo);

    // First row
    $insertResult1 = $insert->execute();
    expect($insertResult1)->toBeInt();
    expect($insertResult1)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records->data)->toHaveCount(3);
    expect($records->data[2]->key)->toBe('this is a test key');

    // Second row
    $insert->value('test_table_id', 4);
    $insertResult2 = $insert->execute();
    expect($insertResult2)->toBeInt();
    expect($insertResult2)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records->data)->toHaveCount(4);
    expect($records->data[3]->key)->toBe('this is a test key');
  });

  test('insert records in NON autoincremented table WITH collision should throw', function () {
    $insert = Insert::_into('test_table_no_auto_increment', $this->myPdo, $this->columnsFactory)
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


describe('values()', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
    $this->columnsFactory = new ColumnsFactory($this->myPdo);
  });

  test('insert records in an autoincremented table', function () {
    $insert = Insert::_into('test_table', $this->myPdo, $this->columnsFactory)
      ->values([
        'key' => 'values()',
        'value' => 'this is a test value',
      ]);

    $select = Select::_from('test_table', $this->myPdo)
      ->where(Where::equals('key', 'values()'));

    // First row
    $insertResult1 = $insert->execute();
    expect($insertResult1)->toBeInt();
    expect($insertResult1)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records->data)->toHaveCount(1);
    expect($records->data[0]->key)->toBe('values()');

    // Second row
    $insertResult2 = $insert->execute();
    expect($insertResult2)->toBeInt();
    expect($insertResult2)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records->data)->toHaveCount(2);
    expect($records->data[1]->key)->toBe('values()');
  });

  test('insert records in NON autoincremented table without collision', function () {
    $insert = Insert::_into('test_table_no_auto_increment', $this->myPdo, $this->columnsFactory)
      ->values([
        'test_table_id' => 333,
        'key' => 'xxx 333',
        'value' => 'this is a test value 333',
      ]);

    $select = Select::_from('test_table_no_auto_increment', $this->myPdo)
      ->where(Where::like('key', 'xxx%'));

    // First row
    $insertResult1 = $insert->execute();
    expect($insertResult1)->toBeInt();
    expect($insertResult1)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records->data)->toHaveCount(1);
    expect($records->data[0]->key)->toBe('xxx 333');

    // Second row
    $insert->values([
      'test_table_id' => 444,
      'key' => 'xxx 444',
      'value' => 'this is a test value 444',
    ]);
    $insertResult2 = $insert->execute();
    expect($insertResult2)->toBeInt();
    expect($insertResult2)->toBe(1);

    // check if the record was inserted
    $records = $select->fetchAll();
    expect($records->data)->toHaveCount(2);
    expect($records->data[1]->key)->toBe('xxx 444');
  });

  test('insert records in NON autoincremented table WITH collision should throw', function () {
    $insert = Insert::_into('test_table_no_auto_increment', $this->myPdo, $this->columnsFactory)
      ->values([
        'test_table_id' => 555,
        'key' => 'xxx 555',
        'value' => 'this is a test value 555',
      ]);

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


describe('rows()', function () {

  beforeEach(function () {
    $this->myPdo = DbTestSchema::getPdoWrapper();
    $this->columnsFactory = new ColumnsFactory($this->myPdo);
  });

  test('insert multiple records in an autoincremented table', function () {
    $insertResult = Insert::_into('test_table', $this->myPdo, $this->columnsFactory)
      ->rows([
        ['key' => 'rows() test 1', 'value' => 'test value 1'],
        ['key' => 'rows() test 2', 'value' => 'test value 2'],
        ['key' => 'rows() test 3', 'value' => 'test value 3'],
      ])
      ->execute();

    // check the number of inserted records
    expect($insertResult)->toBeInt();
    expect($insertResult)->toBe(3);

    // check if the actual records were inserted
    $records = Select::_from('test_table', $this->myPdo)
      ->where(Where::like('key', 'rows() test%'))
      ->fetchAll();

    expect($records->data)
      ->toBeArray()
      ->toHaveCount(3);
    expect($records->data[2]->key)->toBe('rows() test 3');
  });

});
