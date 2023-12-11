<?php

use Tribal2\DbHandler\Core\Transaction;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\PDOSingleton;

describe('CommitsMode', function () {

  beforeEach(function () {
    $this->transaction = new Transaction(
      Mockery::mock(PDOWrapperInterface::class),
    );
  });

  test('getCommitsMode()', function () {
    expect($this->transaction->getCommitsMode())->toBe(PDOCommitModeEnum::ON);
  });

  test('setCommitsModeOff()', function () {
    $this->transaction->setCommitsModeOff();
    expect($this->transaction->getCommitsMode())->toBe(PDOCommitModeEnum::OFF);
  });

  test('setCommitsModeOn()', function () {
    $this->transaction->setCommitsModeOn();
    expect($this->transaction->getCommitsMode())->toBe(PDOCommitModeEnum::ON);
  });
});

describe('begin()', function () {

  beforeEach(function () {
    $this->transaction = new Transaction(
      Mockery::mock(PDOWrapperInterface::class),
    );
  });

  test('with no active transaction', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(FALSE);
    $pdoMock->shouldReceive('beginTransaction')->andReturn(TRUE);
    PDOSingleton::set($pdoMock);

    expect($this->transaction->begin())->toBeTrue();
  });

  test('with an active transaction', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);
    PDOSingleton::set($pdoMock);

    expect($this->transaction->begin())->toBeFalse();
  });

});

describe('commit()', function () {

  beforeEach(function () {
    $this->transaction = new Transaction(
      Mockery::mock(PDOWrapperInterface::class),
    );
  });

  test('ok', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);
    $pdoMock->shouldReceive('commit')->andReturn(TRUE);
    PDOSingleton::set($pdoMock);

    expect($this->transaction->commit())->toBeTrue();
  });

  test('with commits mode off', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);
    PDOSingleton::set($pdoMock);

    $this->transaction->setCommitsModeOff();

    expect($this->transaction->commit())->toBeFalse();
  });

});

describe('rollback()', function () {

  beforeEach(function () {
    $this->transaction = new Transaction(
      Mockery::mock(PDOWrapperInterface::class),
    );
  });

  test('with an active transaction', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);
    $pdoMock->shouldReceive('rollBack')->andReturn(TRUE);
    PDOSingleton::set($pdoMock);

    $this->transaction->begin();
    expect($this->transaction->rollback())->toBeTrue();
  });

  test('with no active transaction', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(FALSE);
    PDOSingleton::set($pdoMock);

    expect($this->transaction->rollback())->toBeFalse();
  });

});

describe('check()', function () {

  beforeEach(function () {
    $this->transaction = new Transaction(
      Mockery::mock(PDOWrapperInterface::class),
    );
  });

  test('returns true when a transaction is active', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);
    PDOSingleton::set($pdoMock);

    expect($this->transaction->check())->toBeTrue();
  });

  test('returns false when no transaction is active', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(FALSE);
    PDOSingleton::set($pdoMock);

    expect($this->transaction->check())->toBeFalse();
  });

});

describe('error handling with $throw flag enabled', function () {

  beforeEach(function () {
    $this->transaction = new Transaction(
      Mockery::mock(PDOWrapperInterface::class),
    );

    $this->transaction->$throw = TRUE;

    $this->transaction->setCommitsModeOn();
  });

  test('begin() with an active transaction already started', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);
    PDOSingleton::set($pdoMock);

    $this->transaction->begin();
  })->throws(Exception::class);

  test('commit() with no active transaction started', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(FALSE);
    PDOSingleton::set($pdoMock);

    $this->transaction->commit();
  })->throws(Exception::class);

  test('commit() with commitMode = OFF', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(TRUE);
    PDOSingleton::set($pdoMock);

    $this->transaction->setCommitsModeOff();

    $this->transaction->commit();
  })->throws(Exception::class);

  test('rollback() with no active transaction started', function () {
    $pdoMock = Mockery::mock(PDO::class);
    $pdoMock->shouldReceive('inTransaction')->andReturn(FALSE);
    PDOSingleton::set($pdoMock);

    $this->transaction->rollback();
  })->throws(Exception::class);

});
