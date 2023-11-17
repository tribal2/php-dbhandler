<?php

use Tribal2\DbHandler\DbConfig;
use Tribal2\DbHandler\PDOSingleton;
use PDO;
use Exception;

describe('PDOSingleton', function () {

  beforeEach(function () {
    $pdoMock = Mockery::mock(PDO::class);
    PDOSingleton::set($pdoMock);
  });

  afterEach(function () {
    PDOSingleton::destroy();
  });

  test('configure sets configuration', function () {
    $config = new DbConfig(
      host: 'localhost',
      port: 3306,
      encoding: 'utf8',
      dbName: 'test_db',
      user: 'root',
      password: 'password'
    );
    PDOSingleton::configure($config);
    expect(PDOSingleton::getDbName())->toEqual('test_db');
  });

  test('Singleton returns the same instance', function () {
    $pdo1 = PDOSingleton::get();
    $pdo2 = PDOSingleton::get();
    expect($pdo1)->toBe($pdo2);
  });

  test('get throws exception if not configured', function () {
    PDOSingleton::destroy();
    PDOSingleton::get();
  })->throws(Exception::class);
});
