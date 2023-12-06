<?php

use Tribal2\DbHandler\DbHandler;
use Tribal2\DbHandler\DbTransaction;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;
use Tribal2\DbHandler\Helpers\Logger;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Where;

require_once __DIR__ . '/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::usePdoSingleton();
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});

describe('DbHandler config', function () {
  test('constructor', function () {
    expect(new DbHandler())->toBeInstanceOf(DbHandler::class);
  });

  test('getInstance()', function () {
    expect(DbHandler::getInstance())->toBeInstanceOf(DbHandler::class);
  });
});

describe('DbHandler methods', function () {
  beforeEach(function () {
    $this->db = DbHandler::getInstance();
  });

  test('disableCommits()', function () {
    $prevStatus = DbTransaction::getCommitsMode();
    expect($prevStatus)->toBe(PDOCommitModeEnum::ON);

    $this->db->disableCommits();
    $newStatus = DbTransaction::getCommitsMode();
    expect($newStatus)->not()->toBe($prevStatus);
    expect($newStatus)->toBe(PDOCommitModeEnum::OFF);
  });

  test('enableCommits()', function () {
    $prevStatus = DbTransaction::getCommitsMode();
    expect($prevStatus)->toBe(PDOCommitModeEnum::OFF);

    $this->db->enableCommits();
    $newStatus = DbTransaction::getCommitsMode();
    expect($newStatus)->not()->toBe($prevStatus);
    expect($newStatus)->toBe(PDOCommitModeEnum::ON);
  });

  test('checkIfTableExists()', function () {
    expect($this->db->checkIfTableExists('test_table'))->toBe(TRUE);
    expect($this->db->checkIfTableExists('test_tablex'))->toBe(FALSE);
  });

  test('getTableColumns()', function () {
    $columns = $this->db->getTableColumns('test_table');
    expect($columns)->toBeObject();
    expect((array)$columns)->toHaveKeys(['all', 'non', 'key']);

    $keyColArr = $columns->key;
    expect($keyColArr)->toBeArray();
  });

  test('checkIfExists() - should return true if exists', function () {
    $exists1 = $this->db->checkIfExists('test_table', 'test_table_id', 1);
    $exists2 = $this->db->checkIfExists('test_table', 'test_table_id', 2);

    expect($exists1)->toBe(TRUE);
    expect($exists2)->toBe(TRUE);
  });

  test('checkIfExists() - should return false on non-existent data', function () {
    $exists = $this->db->checkIfExists('test_table', 'test_table_id', 9999);
    expect($exists)->toBe(FALSE);
  });

  test('getRowCount() - fetches row count for a non-empty table', function() {
      $rowCount = $this->db->getRowCount('test_table');
      expect($rowCount)->toBe(2);
  });

  test('getRowCount() - throws an exception for a non-existent table', function() {
    $this->db->getRowCount('nonexistent_table');
  })->throws(Exception::class);

});

describe('DbHandler getSchemaInfo()', function () {
  beforeEach(function () {
    $this->db = DbHandler::getInstance();
  });

  test('fetches all schema information for a table', function() {
    $schemaInfo = $this->db->getSchemaInfo('test_table');

    // Assumption: TABLE_SCHEMA and TABLE_NAME are always available in the result
    expect(isset($schemaInfo->TABLE_SCHEMA))->toBeTrue();
    expect(isset($schemaInfo->TABLE_NAME))->toBeTrue();
    expect($schemaInfo->TABLE_NAME)->toBe('test_table');
  });

  test('fetches specific schema column for a table', function() {
    // Assuming we want to fetch the "AUTO_INCREMENT" column for the table
    $autoIncrement = $this->db->getSchemaInfo('test_table', 'AUTO_INCREMENT');

    // AUTO_INCREMENT is numeric or NULL
    expect(is_numeric($autoIncrement) || is_null($autoIncrement))->toBeTrue();
  });

  test('fetches multiple schema columns for a table', function() {
    $columnsToFetch = ['TABLE_NAME', 'AUTO_INCREMENT'];
    $schemaInfo = $this->db->getSchemaInfo('test_table', $columnsToFetch);

    expect(isset($schemaInfo->TABLE_NAME))->toBeTrue();
    expect($schemaInfo->TABLE_NAME)->toBe('test_table');

    expect(
      is_numeric($schemaInfo->AUTO_INCREMENT)
      || is_null($schemaInfo->AUTO_INCREMENT)
    )->toBeTrue();
  });

  test('returns null for non-existent table', function() {
    $res = $this->db->getSchemaInfo('non_existent_table');
    expect($res)->toBeNull();
  });

  test('ensures schema data consistency', function() {
    $autoInc1 = $this->db->getSchemaInfo('test_table', 'AUTO_INCREMENT');
    $autoInc2 = $this->db->getSchemaInfo('test_table', ['AUTO_INCREMENT']);

    expect($autoInc1)->toBe($autoInc2);
  });
});

describe('DbHandler executeQuery()', function () {
  beforeEach(function () {
    $this->db = DbHandler::getInstance();
  });

  test('with INSERT', function () {
    $bindBuilder = new PDOBindBuilder();
    $keyPlaceholder = $bindBuilder->addValue('DbHandlerTest');
    $valuePlaceholder = $bindBuilder->addValue(date('Y-m-d H:i:s'));
    $query = "
      INSERT INTO
        test_table(`key`, `value`)
      VALUES
        ($keyPlaceholder, $valuePlaceholder);
    ";
    $affectedRows = $this->db->executeQuery($query, $bindBuilder);
    expect($affectedRows)->toBe(1);

    $query = "SELECT * FROM test_table WHERE `key` = 'DbHandlerTest';";
    $results = $this->db->executeQuery($query, new PDOBindBuilder());
    expect($results)->toBeArray();
    expect($results)->toHaveCount(1);
    expect($results[0]->key)->toBe('DbHandlerTest');
  });

  test('with UPDATE', function () {
    $bindBuilder = new PDOBindBuilder();
    $valuePlaceholder = $bindBuilder->addValue('Hola mundo');
    $query = "
      UPDATE
        test_table
      SET
        `value` = {$valuePlaceholder}
      WHERE
        `key` = 'DbHandlerTest';
    ";
    $affectedRows = $this->db->executeQuery($query, $bindBuilder);
    expect($affectedRows)->toBe(1);

    $query = "SELECT * FROM test_table WHERE `key` = 'DbHandlerTest';";
    $results = $this->db->executeQuery($query, new PDOBindBuilder());
    expect($results)->toBeArray();
    expect($results)->toHaveCount(1);
    expect($results[0]->value)->toBe('Hola mundo');
  });

  test('with DELETE', function () {
    $bindBuilder = new PDOBindBuilder();
    $keyPlaceholder = $bindBuilder->addValue('DbHandlerTest');
    $query = "
      DELETE FROM
        test_table
      WHERE
        `key` = {$keyPlaceholder};
    ";
    $affectedRows = $this->db->executeQuery($query, $bindBuilder);
    expect($affectedRows)->toBe(1);

    $query = "SELECT * FROM test_table WHERE `key` = 'DbHandlerTest';";
    $results = $this->db->executeQuery($query, new PDOBindBuilder());
    expect($results)->toBeArray();
    expect($results)->toHaveCount(0);
  });

  test('with disallowed query type', function () {
      $bindBuilder = new PDOBindBuilder();
      $query = "
        ALTER TABLE
          test_table
        ADD COLUMN
          test_column VARCHAR(255);
      ";
      $this->db->executeQuery($query, $bindBuilder);
  })->throws(Exception::class);
});

describe('DbHandler callProcedure()', function () {
  beforeEach(function () {
    $this->db = DbHandler::getInstance();
  });

  test('get_test_rows', function () {
    $params = [
      'keyInput' => 'test',
      'valueInput' => 'te',
    ];
    $result = $this->db->callProcedure('get_test_rows', $params);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0])->toBeObject();
    expect($result[0]->value)->toBe('Test value 1');
  });
});

describe('DbHandler ReadOnlyMode', function () {
  $errMsg = 'En este momento el sistema solo está habilitado para consultas.';

  beforeEach(function () {
    /**
     * @var MockInterface $MockedDb
     */
    $MockedDb = Mockery::mock(DbHandler::class);
    $partiallyMockedDb = $MockedDb->makePartial();
    $partiallyMockedDb
      ->shouldReceive('checkIfInReadOnlyMode')
      ->andThrow(new Exception(
        'En este momento el sistema solo está habilitado para consultas.',
        503
      ));

    $this->db = $partiallyMockedDb;
  });

  test('executeQuery()', function () {
    $bindBuilder = new PDOBindBuilder();
    $query = "SELECT * FROM test_table WHERE `key` = 'DbHandlerTest';";
    $this->db->executeQuery($query, $bindBuilder);
  })->throws(Exception::class, $errMsg, 503);

  test('setData()', function () {
    $this->db->setData('test_table', [ 'key' => 'test_key', 'value' => 'test_value' ]);
  })->throws(Exception::class, $errMsg, 503);

  test('setDataMulti()', function () {
    $data = [
        [ 'key' => 'test_key1', 'value' => 'test_value1' ],
        [ 'key' => 'test_key2', 'value' => 'test_value2' ],
    ];
    $this->db->setData('test_table', $data);
  })->throws(Exception::class, $errMsg, 503);

  test('updateData()', function () {
    $this->db->updateData('test_table', [ 'key' => 'test_key', 'value' => 'test_value' ]);
  })->throws(Exception::class, $errMsg, 503);

  test('setUpdateData()', function () {
    $this->db->setUpdateData('test_table', [ 'key' => 'test_key', 'value' => 'test_value' ]);
  })->throws(Exception::class, $errMsg, 503);

  test('deleteData()', function () {
    $this->db->deleteData('test_table', [ 'key' => 'test_key', 'value' => 'test_value' ]);
  })->throws(Exception::class, $errMsg, 503);
});

describe('DbHandler INSERT', function () {
  beforeEach(function () {
    $this->db = DbHandler::getInstance();
  });

  afterEach(function () {
    $bindBuilder = new PDOBindBuilder();
    $query = "
      DELETE FROM
        test_table
      WHERE
        `key` LIKE {$bindBuilder->addValue('test_key%')};
    ";
    $this->db->executeQuery($query, $bindBuilder);
  });

  test('setData() - insert valid record', function () {
    $data = [
        'key' => 'test_key',
        'value' => 'test_value',
    ];
    $this->db->setData('test_table', $data);

    $result = $this->db->executeQuery(
      "SELECT * FROM test_table WHERE `key` = 'test_key'",
      new PDOBindBuilder(),
    );
    expect($result)->toBeArray()->and($result[0]->key)->toBe('test_key');
  });

  test('setData() - attempt duplicate record insertion', function () {
      $data = [
          'test_table_id' => 1,
          'key' => 'random',
          'value' => 'duplicate key test',
      ];
      $this->db->setData('test_table_no_auto_increment', $data);
  })->throws(Exception::class, DbHandler::ERR_REPETIDO);

  test('setData() - insert record without required fields', function () {
      $data = [
          'value' => 'test_value',
      ];
      $this->db->setData('test_table', $data);
  })->throws(Exception::class);

  test('setData() - insert record with wrong data type', function () {
      $data = [
          'key' => ['test_key'],
          'value' => 'test_value',
          'created_at' => '2023-10-25 00:00:00'
      ];
      $this->db->setData('test_table', $data);
  })->throws(Exception::class);

  test('setData() - insert record with special characters', function () {
      $data = [
          'key' => 'test_key',
          'value' => 'value with "special" characters and \'quotes\'',
      ];
      $this->db->setData('test_table', $data);
      $result = $this->db->executeQuery(
        "SELECT * FROM test_table WHERE `key` = 'test_key'",
        new PDOBindBuilder(),
      );
      expect($result[0]->value)->toBe('value with "special" characters and \'quotes\'');
  });

  test('setDataMulti() - insert multiple rows at once', function() {
    $data = [
        ['key' => 'test_key1', 'value' => 'test_value1'],
        ['key' => 'test_key2', 'value' => 'test_value2'],
    ];

    $result = $this->db->setDataMulti('test_table', $data);
    expect($result)->toBe('Se introdujeron 2 registros en la base de datos.');

    // Fetch data to verify insertion
    $storedData = $this->db->executeQuery(
      'SELECT * FROM test_table WHERE `key` IN ("test_key1", "test_key2")',
      new PDOBindBuilder(),
    );
    expect($storedData)->toHaveCount(2);
    expect($storedData[0]->key)->toBe('test_key1');
    expect($storedData[1]->key)->toBe('test_key2');
  });

  test('setDataMulti() - throws an exception when trying to insert duplicate rows', function() {
    $data = [
        ['test_table_id' => 999, 'key' => 'test_key3', 'value' => 'test_value3'],
        ['test_table_id' => 999, 'key' => 'test_key3', 'value' => 'test_value3'], // Intentional duplicate
    ];

    $this->expectException(Exception::class);
    $this->db->setDataMulti('test_table', $data);
  })->throws(Exception::class);

  test('setUpdateData() - creates a new record when not existing', function() {
    $data = ['key' => 'test_key_upsert_new', 'value' => 'new_value'];

    $this->db->setUpdateData('test_table', $data);

    // Fetch data to verify insertion
    $storedData = $this->db->executeQuery(
      'SELECT * FROM test_table WHERE `key` = "test_key_upsert_new"',
      new PDOBindBuilder(),
    );

    expect($storedData[0]->value)->toBe('new_value');
  });

  test('setUpdateData() - updates a record when already existing', function() {
    // Insert initial data for testing
    $initialData = ['key' => 'test_key_upsert_existing', 'value' => 'initial_value'];
    $this->db->setData('test_table', $initialData);

    // Update data
    $updateData = [
      'test_table_id' => $this->db->getLastInsertId(),
      'key' => 'test_key_upsert_existing',
      'value' => 'updated_value',
    ];

    $this->db->setUpdateData('test_table', $updateData);

    // Fetch data to verify update
    $storedData = $this->db->executeQuery(
      'SELECT * FROM test_table WHERE `key` = "test_key_upsert_existing"',
      new PDOBindBuilder(),
    );

    expect($storedData[0]->value)->toBe('updated_value');
  });

  test('setUpdateData() - throws an exception when encountering an error', function() {
    // Intentionally creating an invalid data to trigger an error
    $faultyData = ['invalid_column' => 'some_value'];
    $this->db->setUpdateData('test_table', $faultyData);
  })->throws(Exception::class);

  test('getLastInsertId() - fetches last inserted ID after an insert operation', function() {
    $this->db->setData('test_table', ['key' => 'test_key', 'value' => 'test_value']);
    $lastInsertId = $this->db->getLastInsertId();

    expect(is_numeric($lastInsertId))->toBeTrue();
    expect($lastInsertId)->toBeGreaterThan(0);
  });

  test('getLastInsertId() - fetches null or 0 when no insert operation has been done', function() {
      // Before any insert operation
      $lastInsertId = $this->db->getLastInsertId();

      expect(
        $lastInsertId === NULL
        || $lastInsertId === "0"
      )->toBeTrue();
  });

});

describe('DbHandler UPDATE', function () {
  beforeEach(function () {
    $this->db = DbHandler::getInstance();
  });

  afterEach(function () {
    $bindBuilder = new PDOBindBuilder();
    $query = "
      DELETE FROM
        test_table
      WHERE
        `key` LIKE {$bindBuilder->addValue('test_key%')};
    ";
    $this->db->executeQuery($query, $bindBuilder);
  });

  test('updateData() - updates a record successfully', function() {
    // Insert initial data for testing
    $initialData = [
      'key' => 'test_key_update',
      'value' => 'initial_value',
    ];
    $this->db->setData('test_table', $initialData);

    // Update data
    $updateData = [
      'test_table_id' => $this->db->getLastInsertId(),
      'value' => 'updated_value',
    ];
    $this->db->updateData('test_table', $updateData);

    // Fetch data to verify update
    $storedData = $this->db->executeQuery(
      'SELECT * FROM test_table WHERE `key` = "test_key_update"',
      new PDOBindBuilder(),
    );

    expect($storedData[0]->value)->toBe('updated_value');
  });

  test('updateData() - updates a record successfully with null values', function() {
    // Insert initial data for testing
    $initialData = [
      'key' => 'test_key_update',
      'value' => 'initial_value',
    ];
    $this->db->setData('test_table', $initialData);
    $insertId = $this->db->getLastInsertId();

    // Update data
    $updateData = [
      'test_table_id' => $insertId,
      'value' => NULL,
    ];
    $this->db->updateData('test_table', $updateData, ignoreNull: FALSE);

    // Fetch data to verify update
    $storedData = $this->db->executeQuery(
      "SELECT * FROM test_table WHERE `test_table_id` = {$insertId}",
      new PDOBindBuilder(),
    );

    expect($storedData[0]->value)->toBeNull();
  });

  test('updateData() - throws an exception when no key is provided', function() {
    $updateData = [
      'key' => 'test_key_update',
      'value' => 'updated_value',
    ];
    $this->db->updateData('test_table', $updateData);
  })->throws(
    Exception::class,
    "No se encontró un valor clave para la columna 'test_table_id' en el registro a actualizar.",
    400,
  );

  test('updateData() - throws an exception when no data is changed', function() {
    $updateData = [
      'usr_rol_id' => 'test',
      'orden' => '9999',
    ];

    $this->db->updateData('usr_rol_id', $updateData);
  })->throws(Exception::class);

});

describe('DbHandler DELETE', function () {
  beforeEach(function () {
    $this->db = DbHandler::getInstance();
  });

  afterEach(function () {
    $bindBuilder = new PDOBindBuilder();
    $query = "
      DELETE FROM
        test_table
      WHERE
        `key` LIKE {$bindBuilder->addValue('test_key%')};
    ";
    $this->db->executeQuery($query, $bindBuilder);
  });

  test('deleteData() - deletes a single record using a single primary key', function() {
    // Insert initial data for testing
    $initialData = ['key' => 'test_key_delete_single', 'value' => 'test_value_delete_single'];
    $this->db->setData('test_table', $initialData);

    // Delete the record
    $deletedCount = $this->db->deleteData(
      'test_table',
      $this->db->getLastInsertId(),
    );
    expect($deletedCount)->toBe(1);

    // Fetch data to verify deletion
    $storedData = $this->db->executeQuery(
      'SELECT * FROM test_table WHERE `key` = "test_key_delete_single"',
      new PDOBindBuilder(),
    );

    expect($storedData)->toBeEmpty();
  });

  test('deleteData() - ensures correct number of deleted records', function() {
    // Insert multiple records for testing
    $data1 = ['key' => 'test_key_bulk_delete_1', 'value' => 'value1'];
    $data2 = ['key' => 'test_key_bulk_delete_2', 'value' => 'value2'];
    $this->db->setDataMulti('test_table', [ $data1, $data2 ]);
    $dataId1 = $this->db->getLastInsertId();

    // Delete one of the records
    $deletedCount = $this->db->deleteData('test_table', $dataId1);
    expect($deletedCount)->toBe(1);

    // Delete another record
    $deletedCount = $this->db->deleteData('test_table', [
      'key' => 'test_key_bulk_delete_2',
    ]);
    expect($deletedCount)->toBe(1);
  });

});

describe('DbHandler SELECT', function () {
  beforeEach(function () {
    $this->db = DbHandler::getInstance();
  });

  test('getDataArr() [using args]', function () {
    $results = $this->db->getDataArr(
      'test_table',
      'value',
      [ 'test_table_id' => 1 ]
    );
    expect($results)->toBeArray();
    expect($results)->toHaveCount(1);
    expect($results[0]->value)->toBe('Test value 1');
  });

  test('getDataArr() [using args] with limit and sort', function () {
    $results = $this->db->getDataArr(
      'test_table',
      'value',
      [],
      2,
      'test_table_id ASC',
    );
    expect($results)->toBeArray();
    expect($results)->toHaveCount(2);
    expect($results[0]->value)->toBe('Test value 1');
  });

  test('getDataArr() [using obj query]', function () {
    $query = [
      'table' => 'test_table',
      'columns' => 'value',
      'where' => [ 'test_table_id' => 1 ],
    ];
    $results = $this->db->getDataArr($query);
    expect($results)->toBeArray();
    expect($results)->toHaveCount(1);
    expect($results[0]->value)->toBe('Test value 1');
  });

  test('getDataArr() [using obj query] with limit and sort', function () {
    $query = [
      'table' => 'test_table',
      'columns' => 'value',
      'limit' => 2,
      'sort' => 'test_table_id ASC',
    ];
    $results = $this->db->getDataArr($query);
    expect($results)->toBeArray();
    expect($results)->toHaveCount(2);
    expect($results[0]->value)->toBe('Test value 1');
  });

  test('getDataArr() [using obj query] with limit and sort array', function () {
    $query = [
      'table' => 'test_table',
      'columns' => 'value',
      'limit' => 2,
      'sort' => [
        'test_table_id' => 'ASC',
      ],
    ];
    $results = $this->db->getDataArr($query);
    expect($results)->toBeArray();
    expect($results)->toHaveCount(2);
    expect($results[0]->value)->toBe('Test value 1');
  });

  test('getDataArr() [using obj query] with group and having', function () {
    $query = [
      'table' => 'test_table',
      'group_by' => 'key',
      'columns' => [
        'key',
        'count(*)',
      ],
      'where' => [
        'created_at' => [
          'operator' => '>',
            'value' => date('Y-m-d H:i:s', strtotime('-1 week')),
        ],
      ],
      'having' => [
        'count(*)' => [
          'operator' => '>=',
          'value' => 1,
        ],
      ],
    ];
    $results = $this->db->getDataArr($query);
    expect($results)->toBeArray();
    expect(count($results))->toBeGreaterThan(0);
  });

  test('getDataArr() should return empty array', function () {
    $query = [
      'table' => 'test_table',
      'columns' => 'value',
      'where' => [ 'test_table_id' => 99999 ],
    ];
    $results = $this->db->getDataArr($query);
    expect($results)->toBeArray();
    expect($results)->toHaveCount(0);
  });

  test('getDataOrNull() should return NULL', function () {
    $query = [
      'table' => 'test_table',
      'columns' => 'value',
      'where' => [ 'test_table_id' => 99999 ],
    ];
    $results = $this->db->getDataOrNull($query);
    expect($results)->toBeNull();
  });

  test('getDataRow()', function () {
    $results = $this->db->getDataRow(
      'test_table',
      [ 'test_table_id' => 1 ]
    );
    expect($results)->toBeObject();
    expect($results)->toHaveKeys(['test_table_id', 'key', 'value', 'created_at']);
  });

  test('getDataRow() with warning', function () {
    /**
     * Simulamos una instancia de la clase Logger
     */
    $loggerMock = Mockery::mock(Logger::class);
    $loggerMock->shouldReceive('log');
    DbHandler::setLogger($loggerMock);

    $results = $this->db->getDataRow('test_table');

    $loggerMock->shouldHaveReceived('log', [
      Mockery::any(),
      Mockery::any(),
      Logger::WARNING,
    ]);
    expect($results)->toBeObject();
    expect($results)->toHaveKeys(['test_table_id', 'key', 'value', 'created_at']);
  });

  test('getDataColumn()', function () {
    $results = $this->db->getDataColumn(
      'test_table',
      'value'
    );
    expect($results)->toBeArray();
    expect($results[0])->toBe('Test value 1');
  });

  test('getValue() [using args]', function () {
    $results = $this->db->getValue(
      'test_table',
      'value',
      [ 'test_table_id' => 1 ]
    );
    expect($results)->toBe('Test value 1');
  });

  test('getValue() [using args] should return NULL', function () {
    $results = $this->db->getValue(
      'test_table',
      'value',
      [ 'test_table_id' => 99999 ]
    );
    expect($results)->toBeNull();
  });

  test('getValue() [using obj query]', function () {
    $query = [
      'table' => 'test_table',
      'columns' => 'value',
      'where' => [ 'test_table_id' => 1 ],
    ];
    $results = $this->db->getValue($query);
    expect($results)->toBe('Test value 1');
  });

  test('getValue() [using obj query] with several columns should throw', function () {
    $query = [
      'table' => 'test_table',
      'columns' => 'key value',
      'where' => [ 'test_table_id' => 1 ],
    ];
    $this->db->getValue($query);
  })->throws(Exception::class);

  test('getDistincts()', function () {
    $results = $this->db->getDistincts('test_table', 'key');
    expect($results)->toBeArray();
    expect($results[0])->toBe('test1');
  });

  test('getDistincts() with invalid args should throw', function () {
    $this->db->getDistincts('test_table', 'key, value');
  })->throws(Exception::class);

  test('getCalculatedColumnValue() for MIN', function () {
    $firstItemCreatedAt = $this->db->getValue(
      'test_table',
      'created_at',
      sort: [ 'created_at' => 'ASC' ],
    );

    $result = $this->db->getCalculatedColumnValue('test_table', 'min', 'created_at');
    expect($result)->toBe($firstItemCreatedAt);
  });

  test('getCalculatedColumnValue() for MAX', function () {
    $lastItemCreatedAt = $this->db->getValue(
      'test_table',
      'created_at',
      sort: [ 'created_at' => 'DESC' ],
    );

    $result = $this->db->getCalculatedColumnValue(
      'test_table',
      'max',
      'created_at',
    );
    expect($result)->toBe($lastItemCreatedAt);
  });

  test('getCalculatedColumnValue() with invalid operator should throw', function () {
      $this->db->getCalculatedColumnValue('test_table', 'invalid_operator', 'created_at');
  })->throws(Exception::class);

  test('getCalculatedColumnValue() for table with no data should return NULL', function () {
      $result = $this->db->getCalculatedColumnValue(
        'test_table',
        'max',
        'created_at',
        [
          'created_at' => [
            'operator' => '<',
            'value' => '2000-01-01 00:00:00',
          ],
        ],
      );
      expect($result)->toBeNull();
  });

});

describe('DbHandler handleException', function () {
  beforeEach(function () {
    $this->db = DbHandler::getInstance();
  });

  test('deals with duplicate entry', function() {
    $reflection = new ReflectionClass(DbHandler::class);
    $method = $reflection->getMethod('handleException');
    $method->setAccessible(TRUE);

    // Mock a PDOException for the "Duplicate entry" scenario
    $pdoException = new PDOException(
      "SQLSTATE[23000]: Integrity constraint violation: Duplicate entry",
      23000,
    );
    $pdoException->errorInfo = [
      '23000',
      1062,
      "Duplicate entry 'xyz' for key 'primary'"
    ];

    $method->invoke($this->db, $pdoException, 'someMethod', []);
  })->throws(
    Exception::class,
    "Ya este registro existe en la base de datos.",
    409,
  );

  test('handleException handles Data too long scenario', function() {
      $reflection = new ReflectionClass(DbHandler::class);
      $method = $reflection->getMethod('handleException');
      $method->setAccessible(TRUE);

      $pdoException = new PDOException(
        "SQLSTATE[22001]: String data, right truncated: "
          . "1406 Data too long for column 'description' at row 1",
        '22001',
      );
      $pdoException->errorInfo = [
        '22001',
        1406,
        "Data too long for column 'description' at row 1",
      ];

      $method->invoke($this->db, $pdoException, 'someMethod', []);
  })->throws(
    Exception::class,
    "El campo 'description' contiene demasiada información.",
    400,
  );

  test('handleException handles Out of range scenario', function() {
      $reflection = new ReflectionClass(DbHandler::class);
      $method = $reflection->getMethod('handleException');
      $method->setAccessible(TRUE);

      $pdoException = new PDOException(
        "SQLSTATE[22003]: Numeric value out of range: "
          . "1264 Out of range value for column 'count' at row 1",
        22003,
      );
      $pdoException->errorInfo = [
        '22003',
        1264,
        "Out of range value for column 'count' at row 1"
      ];

      $method->invoke($this->db, $pdoException, 'someMethod', []);
  })->throws(
    Exception::class,
    "El campo 'count' contiene demasiada información.",
    400,
  );

  test('handleException throws default error message for unknown scenarios', function() {
      $reflection = new ReflectionClass(DbHandler::class);
      $method = $reflection->getMethod('handleException');
      $method->setAccessible(TRUE);

      $pdoException = new PDOException("SQLSTATE[99999]: Unknown error", 99999);
      $pdoException->errorInfo = ['99999', 0, "Unknown error"];

      $method->invoke($this->db, $pdoException, 'someMethod', []);
  })->throws(
    Exception::class,
    'No se pudo hacer la operación con la base de datos.',
    500
  );

});


describe('DbHandler static method queries', function () {
  test('select', function () {
    $result = DbHandler::select('test_table')
      ->where(Where::equals('test_table_id', 1))
      ->fetchValue('value');

    expect($result)->toBe('Test value 1');
  });
});
