<?php

use Tribal2\DbHandler\DbTransaction;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;
use Tribal2\DbHandler\PDOSingleton;

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

});

describe('commit()', function () {

  test('ok', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);
    $pdoMock->shouldReceive('commit')->andReturn(TRUE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    expect(DbTransaction::commit())->toBeTrue();
  });

  test('with commits mode off', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    DbTransaction::setCommitsModeOff();

    expect(DbTransaction::commit())->toBeFalse();
  });

});

describe('rollback()', function () {

  test('with an active transaction', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);
    $pdoMock->shouldReceive('rollBack')->andReturn(TRUE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    DbTransaction::begin();
    expect(DbTransaction::rollback())->toBeTrue();
  });

  test('with no active transaction', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(FALSE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    expect(DbTransaction::rollback())->toBeFalse();
  });

});

describe('check()', function () {

  test('returns true when a transaction is active', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    expect(DbTransaction::check())->toBeTrue();
  });

  test('returns false when no transaction is active', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(FALSE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    expect(DbTransaction::check())->toBeFalse();
  });

});

describe('error handling with $throw flag enabled', function () {

  beforeEach(function () {
    DbTransaction::$throw = TRUE;
    DbTransaction::setCommitsModeOn();
  });

  test('begin() with an active transaction already started', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    DbTransaction::begin();
  })->throws(Exception::class);

  test('commit() with no active transaction started', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(FALSE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    DbTransaction::commit();
  })->throws(Exception::class);

  test('commit() with commitMode = OFF', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    DbTransaction::setCommitsModeOff();

    DbTransaction::commit();
  })->throws(Exception::class);

  test('rollback() with no active transaction started', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(FALSE);

    $reflection = new ReflectionClass(PDOSingleton::class);
    $method = $reflection->getMethod('set');
    $method->setAccessible(TRUE);
    $method->invokeArgs(NULL, [$pdoMock]);

    DbTransaction::rollback();
  })->throws(Exception::class);

});
