<?php

use Tribal2\DbHandler\Table\Columns;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;

$executeFakeResult = [
  (object)[
    'COLUMN_NAME' => 'test_table_id',
    'COLUMN_KEY' => 'PRI',
    'EXTRA' => 'auto_increment',
  ], (object)[
    'COLUMN_NAME' => 'key',
    'COLUMN_KEY' => '',
    'EXTRA' => '',
  ], (object)[
    'COLUMN_NAME' => 'value',
    'COLUMN_KEY' => '',
    'EXTRA' => '',
  ],
];

describe('Instance', function () use ($executeFakeResult) {

  beforeEach(function () use ($executeFakeResult) {
    $this->mockPDOWrapper = Mockery::mock(PDOWrapperInterface::class, [
      'getDbName' => 'test_db',
      'execute' => Mockery::mock(PDOStatement::class, [
        'fetchAll' => $executeFakeResult,
      ]),
    ]);
  });

  test('constructor', function () {
    $columns = new Columns($this->mockPDOWrapper);
    expect($columns)->toBeInstanceOf(Columns::class);
  });

  test('static factory', function () {
    $columns = Columns::_for('test_table', $this->mockPDOWrapper);
    expect($columns)->toBeInstanceOf(Columns::class);
  });

});


describe('Private methods', function () use ($executeFakeResult) {

  beforeEach(function () use ($executeFakeResult) {
    $this->fakeDbColumns = $executeFakeResult;

    $this->mockPDOWrapper = Mockery::mock(PDOWrapperInterface::class, [
      'getDbName' => 'test_db',
      'execute' => $executeFakeResult,
    ]);
  });

  test('parse()', function () {
      $columnsInstance = new Columns($this->mockPDOWrapper);

      $reflection = new ReflectionClass(Columns::class);
      $method = $reflection->getMethod('parse');
      $method->setAccessible(TRUE);

      $pdoException = new PDOException("SQLSTATE[99999]: Unknown error", 99999);
      $pdoException->errorInfo = ['99999', 0, "Unknown error"];

      $method->invoke($columnsInstance, $this->fakeDbColumns);

      // Verificar que las propiedades se han establecido correctamente
      expect($columnsInstance->columns)
        ->toBeArray()
        ->toHaveCount(3)
        ->toContain('test_table_id')
        ->toContain('key')
        ->toContain('value');

      expect($columnsInstance->key)
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('test_table_id');

      expect($columnsInstance->nonKey)
        ->toBeArray()
        ->toHaveCount(2)
        ->toContain('key')
        ->toContain('value');

      expect($columnsInstance->autoincrement)
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('test_table_id');
  });

});


describe('Public methods', function () use ($executeFakeResult) {

  beforeEach(function () use ($executeFakeResult) {
    $this->mockPDOWrapper = Mockery::mock(PDOWrapperInterface::class, [
      'getDbName' => 'test_db',
      'execute' => $executeFakeResult,
    ]);

    $this->mockBindBuilder = Mockery::mock(PDOBindBuilderInterface::class, [
      'addValueWithPrefix' => '<BINDED_VALUE>',
    ]);
  });

  test('getSql() generates correct SQL query', function () {
    $columns = new Columns($this->mockPDOWrapper);
    $columns->table = 'test_table';

    $sql = $columns->getSql($this->mockBindBuilder);
    $expectedSql = "
      SELECT
          COLUMN_NAME,
          COLUMN_KEY,
          EXTRA
      FROM
          information_schema.COLUMNS
      WHERE
          TABLE_SCHEMA   = <BINDED_VALUE>
          AND TABLE_NAME = <BINDED_VALUE>;
    ";

    expect($sql)->toBeString();
    expect($sql)->toBe($expectedSql);
  });

  test('has() correctly identifies existing columns', function () {
    $columns = new Columns($this->mockPDOWrapper);
    $columns->table = 'test_table';

    // Simula la respuesta de la base de datos
    $columns->columns = ['id', 'name', 'email'];

    expect($columns->has('name'))->toBeTrue();
    expect($columns->has('nonexistent_column'))->toBeFalse();
  });

});
