<?php

use Tribal2\DbHandler\DbTransaction;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;
use Tribal2\DbHandler\PDOSingleton;
use PDO;

describe('CommitsMode', function () {

  test('getCommitsMode()', function () {
    expect(DbTransaction::getCommitsMode())->toBe(PDOCommitModeEnum::ON);
  });

  test('setCommitsModeOff()', function () {
    DbTransaction::setCommitsModeOff();
    expect(DbTransaction::getCommitsMode())->toBe(PDOCommitModeEnum::OFF);
  });

  test('setCommitsModeOn()', function () {
    DbTransaction::setCommitsModeOn();
    expect(DbTransaction::getCommitsMode())->toBe(PDOCommitModeEnum::ON);
  });
});

describe('begin()', function () {

  test('with no active transaction', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(FALSE);
    $pdoMock->shouldReceive('beginTransaction')->andReturn(TRUE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    expect(DbTransaction::begin())->toBeTrue();
  });

  test('with an active transaction', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    expect(DbTransaction::begin())->toBeFalse();
  });

  test('with an active transaction and $throw flag enabled', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    DbTransaction::$throw = TRUE;
    DbTransaction::begin();
  })->throws(Exception::class);

});
