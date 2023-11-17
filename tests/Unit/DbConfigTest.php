<?php
use Tribal2\DbHandler\DbConfig;

describe('DbConfig', function () {

  test('constructor', function () {
    $config = new DbConfig(
      host: 'localhost',
      port: 3306,
      encoding: 'utf8',
      dbName: 'test_db',
      user: 'root',
      password: 'password'
    );

    expect($config->host)->toEqual('localhost');
    expect($config->port)->toEqual(3306);
  });

  test('getConnString() generates correct connection string', function () {
    $config = new DbConfig(
      host: 'localhost',
      port: 3306,
      encoding: 'utf8',
      dbName: 'test_db',
      user: 'root',
      password: 'password'
    );

    $expectedConnString = 'mysql:host=localhost; port=3306; dbname=test_db; charset=utf8;';
    expect($config->getConnString())->toEqual($expectedConnString);
  });

});
