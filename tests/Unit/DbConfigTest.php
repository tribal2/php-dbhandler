<?php

use Tribal2\DbHandler\DbConfig;
use Tribal2\DbHandler\Interfaces\DbConfigInterface;

describe('DbConfig', function () {

  test('constructor', function () {
    $config = new DbConfig(
      dbName: 'test_db',
      user: 'root',
      password: 'password'
    );

    expect($config)->toBeInstanceOf(DbConfigInterface::class);
  });

  test('constructor using builder', function () {
    $config = DbConfig::create('test_db')
      ->withUser('root')
      ->withPassword('password');

    expect($config)->toBeInstanceOf(DbConfigInterface::class);
  });

  test('getConnString() generates correct connection string', function () {
    $config = new DbConfig(
      dbName: 'test_db',
      user: 'root',
      password: 'password'
    );

    $expectedConnString = 'mysql:host=localhost;port=3306;dbname=test_db;charset=utf8mb4;';
    expect($config->getConnString())->toEqual($expectedConnString);
  });

  test('getConnString() generates correct connection string using setters', function () {
    $config = new DbConfig(
      dbName: 'test_db',
      user: 'root',
      password: 'password'
    );

    $config->withHost('mariadb');
    $config->withPort(1234);
    $config->withCharset('utf8');

    $expectedConnString = 'mysql:host=mariadb;port=1234;dbname=test_db;charset=utf8;';
    expect($config->getConnString())->toEqual($expectedConnString);
  });

  test('getDbName()', function () {
    $dbName = DbConfig::create('test_db')->getDbName();

    expect($dbName)
      ->toBeString()
      ->toEqual('test_db');
  });

  test('getUser()', function () {
    $config = DbConfig::create('test_db');
    $user = $config->getUser();

    expect($user)
      ->toBeNull();

    $config->withUser('root');
    $user = $config->getUser();

    expect($user)
      ->toBeString()
      ->toEqual('root');
  });

  test('getPassword()', function () {
    $config = DbConfig::create('test_db');
    $user = $config->getPassword();

    expect($user)
      ->toBeNull();

    $config->withPassword('root');
    $user = $config->getPassword();

    expect($user)
      ->toBeString()
      ->toEqual('root');
  });

  test('isReadOnly()', function () {
    $config = DbConfig::create('test_db');
    $isReadOnly = $config->isReadOnly();

    expect($isReadOnly)
      ->toBeBool()
      ->toBeFalse();

    // Turning on read only mode
    $config->withReadOnlyMode();
    $isReadOnly = $config->isReadOnly();

    expect($isReadOnly)
      ->toBeBool()
      ->toBeTrue();

    // Turning off read only mode
    $config->withReadOnlyModeOff();
    $isReadOnly = $config->isReadOnly();

    expect($isReadOnly)
      ->toBeBool()
      ->toBeFalse();
  });

});


describe('Exceptions', function () {

  test("withPort('invalid_string') should throw InvalidArgumentException", function () {
    DbConfig::create('test_db')->withPort('invalid');
  })
    ->throws(InvalidArgumentException::class);

});
