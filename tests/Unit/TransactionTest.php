<?php

use Tribal2\DbHandler\Core\Transaction;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;

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

  test('with no active transaction', function () {
    $pdoMock = Mockery::mock(PDOWrapperInterface::class, [
      'inTransaction' => FALSE,
      'beginTransaction' => TRUE,
    ]);

    $transaction = new Transaction($pdoMock);

    expect($transaction->begin())->toBeTrue();
  });

  test('with an active transaction', function () {
    $pdoMock = Mockery::mock(PDOWrapperInterface::class, [
      'inTransaction' => TRUE,
    ]);

    $transaction = new Transaction($pdoMock);

    expect($transaction->begin())->toBeFalse();
  });

});

describe('commit()', function () {

  test('ok', function () {
    $pdoMock = Mockery::mock(PDOWrapperInterface::class, [
      'inTransaction' => TRUE,
      'commit' => TRUE,
    ]);

    $transaction = new Transaction($pdoMock);

    expect($transaction->commit())->toBeTrue();
  });

  test('with commits mode off', function () {
    $pdoMock = Mockery::mock(PDOWrapperInterface::class, [
      'inTransaction' => TRUE,
    ]);

    $transaction = new Transaction($pdoMock);

    $transaction->setCommitsModeOff();

    expect($transaction->commit())->toBeFalse();
  });

});

describe('rollback()', function () {

  test('with an active transaction', function () {
    $pdoMock = Mockery::mock(PDOWrapperInterface::class, [
      'inTransaction' => TRUE,
      'rollBack' => TRUE,
    ]);

    $transaction = new Transaction($pdoMock);

    expect($transaction->rollback())->toBeTrue();
  });

  test('with no active transaction', function () {
    $pdoMock = Mockery::mock(PDOWrapperInterface::class, [
      'inTransaction' => FALSE,
    ]);

    $transaction = new Transaction($pdoMock);

    expect($transaction->rollback())->toBeFalse();
  });

});

describe('check()', function () {

  test('returns true when a transaction is active', function () {
    $pdoMock = Mockery::mock(PDOWrapperInterface::class, [
      'inTransaction' => TRUE,
    ]);

    $transaction = new Transaction($pdoMock);

    expect($transaction->check())->toBeTrue();
  });

  test('returns false when no transaction is active', function () {
    $pdoMock = Mockery::mock(PDOWrapperInterface::class, [
      'inTransaction' => FALSE,
    ]);

    $transaction = new Transaction($pdoMock);

    expect($transaction->check())->toBeFalse();
  });

});

describe('error handling with $throw flag enabled', function () {

  beforeEach(function () {
    $this->transactionFactory = function (bool $inTransaction) {
      $tx = new Transaction(
        Mockery::mock(PDOWrapperInterface::class, [
          'inTransaction' => $inTransaction,
        ]),
      );

      $tx->setThrowOnError(TRUE);

      return $tx;
    };
  });

  test('begin() with an active transaction already started', function () {
    $transaction = ($this->transactionFactory)(inTransaction: TRUE);
    $transaction->begin();
  })->throws(Exception::class);

  test('commit() with no active transaction started', function () {
    $transaction = ($this->transactionFactory)(inTransaction: FALSE);
    $transaction->commit();
  })->throws(Exception::class);

  test('commit() with commitMode = OFF', function () {
    $transaction = ($this->transactionFactory)(inTransaction: TRUE);
    $transaction->setCommitsModeOff();
    $transaction->commit();
  })->throws(Exception::class);

  test('rollback() with no active transaction started', function () {
    $transaction = ($this->transactionFactory)(inTransaction: FALSE);
    $transaction->rollback();
  })->throws(Exception::class);

});
