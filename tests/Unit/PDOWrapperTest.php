<?php

use Psr\Log\LoggerInterface;
use Tribal2\DbHandler\Core\PDOWrapper;
use Tribal2\DbHandler\DbConfig;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;

describe('Constructor', function() {

  beforeEach(function () {
    $this->pdo = Mockery::mock(PDO::class);
    $this->config = Mockery::mock(DbConfig::class);
    $this->logger = Mockery::mock(LoggerInterface::class, [
      'debug' => TRUE,
      'error' => TRUE,
    ]);
    $this->wrapper = new PDOWrapper($this->config, $this->logger, $this->pdo);
  });

  test('constructor sets PDO instance', function () {
    expect($this->wrapper)->toBeInstanceOf(PDOWrapper::class);
  });

  test('use factory to instantiate from pdo', function() {
    $mockStatement = Mockery::mock(PDOStatement::class, [
      'fetchColumn' => 'dbname',
    ]);
    $this->pdo->shouldReceive('query')->andReturn($mockStatement);
    $wrapper = PDOWrapper::fromPDO($this->pdo);
    expect($wrapper)->toBeInstanceOf(PDOWrapper::class);
  });

});


describe('Methods', function() {

  beforeEach(function () {
    $this->pdo = Mockery::mock(PDO::class);
    $this->config = Mockery::mock(DbConfig::class);
    $this->logger = Mockery::mock(LoggerInterface::class, [
      'debug' => TRUE,
      'error' => TRUE,
    ]);
    $this->wrapper = new PDOWrapper($this->config, $this->logger, $this->pdo);
  });

  test('execute method prepares and executes statement', function () {
    $query = 'SELECT * FROM table';
    $bindBuilder = Mockery::mock(PDOBindBuilderInterface::class, [
      'getValues' => [],
      'debugQuery' => $query,
    ]);

    $stmt = Mockery::mock(PDOStatement::class);
    $stmt->queryString = $query;
    $stmt->shouldReceive('execute')->andReturn(TRUE);

    $this->pdo->shouldReceive('prepare')->with($query)->andReturn($stmt);
    $bindBuilder->shouldReceive('bindToStatement')->with($stmt);

    $result = $this->wrapper->execute($query, $bindBuilder);
    expect($result)->toBeInstanceOf(PDOStatement::class);
  });

  test('getDbName() should return the database name', function() {
    $mockStatement = Mockery::mock(PDOStatement::class, [
      'fetchColumn' => 'dbname',
    ]);
    $this->pdo->shouldReceive('query')->andReturn($mockStatement);
    $wrapper = PDOWrapper::fromPDO($this->pdo);
    expect($wrapper->getDbName())->toBe('dbname');
  });

  test('beginTransaction starts a transaction', function () {
    $this->pdo->shouldReceive('beginTransaction')->andReturn(TRUE);
    expect($this->wrapper->beginTransaction())->toBeTrue();
  });

  test('commit commits a transaction', function () {
    $this->pdo->shouldReceive('commit')->andReturn(TRUE);
    expect($this->wrapper->commit())->toBeTrue();
  });

  test('rollBack rolls back a transaction', function () {
    $this->pdo->shouldReceive('rollBack')->andReturn(TRUE);
    expect($this->wrapper->rollBack())->toBeTrue();
  });

  test('inTransaction checks if in a transaction', function () {
    $this->pdo->shouldReceive('inTransaction')->andReturn(FALSE);
    expect($this->wrapper->inTransaction())->toBeFalse();
  });

  test('getLastInsertId returns the last insert ID', function () {
    $lastInsertId = '123';
    $this->pdo->shouldReceive('lastInsertId')->andReturn($lastInsertId);
    expect($this->wrapper->getLastInsertId())->toBe($lastInsertId);
  });

  test('setReadOnlyMode sets read-only mode', function () {
    $this->config->shouldReceive('withReadOnlyMode')->andReturn($this->config);
    $this->config->shouldReceive('withReadOnlyModeOff')->andReturn($this->config);
    $this->config->shouldReceive('isReadOnly')->andReturn(TRUE);

    $this->wrapper->setReadOnlyMode(TRUE);
    $this->config->shouldHaveReceived('withReadOnlyMode');

    expect($this->wrapper->isReadOnly())->toBeTrue();

    $this->wrapper->setReadOnlyMode(FALSE);
    $this->config->shouldHaveReceived('withReadOnlyModeOff');
  });

});


describe('Exceptions', function() {

  beforeEach(function () {
    $this->pdo = Mockery::mock(PDO::class);
    $this->config = Mockery::mock(DbConfig::class);
    $this->logger = Mockery::mock(LoggerInterface::class, [
      'debug' => TRUE,
      'error' => TRUE,
    ]);
    $this->wrapper = new PDOWrapper($this->config, $this->logger, $this->pdo);
  });

  test('execute method fails on _prepare', function () {
    $query = 'SELECT * FROM table';
    $bindBuilder = Mockery::mock(PDOBindBuilderInterface::class);
    $exception = new Exception('Test Exception');

    $this->pdo->shouldReceive('prepare')->with($query)->andThrow($exception);

    $executeFn = fn() => $this->wrapper->execute($query, $bindBuilder);
    expect($executeFn)->toThrow(Exception::class, 'Test Exception');

    $this->logger->shouldHaveReceived('error')->with('Test Exception');
  });

  test('execute method fails on statement execution', function () {
    $query = 'SELECT * FROM table';
    $bindBuilder = Mockery::mock(PDOBindBuilderInterface::class, [
      'getValues' => [],
      'debugQuery' => $query,
      'bindToStatement' => TRUE,
    ]);

    $mockStatement = Mockery::mock(PDOStatement::class);
    $mockStatement->queryString = $query;
    $this->pdo->shouldReceive('prepare')->with($query)->andReturn($mockStatement);

    $mockStatement->shouldReceive('execute')->andReturn(FALSE);

    $executeFn = fn() => $this->wrapper->execute($query, $bindBuilder);
    expect($executeFn)->toThrow(Exception::class, 'Error executing statement');
  });

  test('prepare method throws an exception if statement preparation fails', function () {
    $bindBuilder = Mockery::mock(PDOBindBuilderInterface::class);
    $query = 'SELECT * FROM invalid_table';
    $errorMsg = "Error preparing statement: $query";

    $this->pdo->shouldReceive('prepare')->with($query)->andReturn(FALSE);

    $executeFn = fn() => $this->wrapper->execute($query, $bindBuilder);
    expect($executeFn)->toThrow(Exception::class, $errorMsg);

    $this->logger->shouldHaveReceived('error')->with($errorMsg);
    $this->logger->shouldHaveReceived('debug');
  });

});
