<?php

use Tribal2\DbConfig;
use Tribal2\DbHandler;
use Tribal2\PDOSingleton;

beforeAll(function () {
  $dbConfig = new DbConfig(
    $_ENV['MYSQL_HOST'],
    $_ENV['MYSQL_PORT'],
    $_ENV['MYSQL_ENCODING'],
    $_ENV['MYSQL_DATABASE'],
    $_ENV['MYSQL_USER'],
    $_ENV['MYSQL_PASSWORD'],
  );

  PDOSingleton::configure($dbConfig);
});

describe('DbHandler config', function () {
  test('constructor', function () {
    expect(new DbHandler())->toBeInstanceOf(DbHandler::class);
  });

  test('getInstance()', function () {
    expect(DbHandler::getInstance())->toBeInstanceOf(DbHandler::class);
  });
});
