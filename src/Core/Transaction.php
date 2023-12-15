<?php

namespace Tribal2\DbHandler\Core;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\TransactionInterface;

class Transaction implements TransactionInterface {

  // Dependencies
  private PDOWrapperInterface $pdoWrapper;
  private ?LoggerInterface $logger = NULL;


  // Class properties
  private bool $throw = FALSE;
  private PDOCommitModeEnum $commitMode = PDOCommitModeEnum::ON;


  public function __construct(
    PDOWrapperInterface $pdoWrapper,
    ?LoggerInterface $logger = NULL,
  ) {
    $this->pdoWrapper = $pdoWrapper;

    // Use default logger if none is provided
    $this->logger = $logger ?? new NullLogger();
  }


  final public function setCommitsModeOn(): void {
    $this->commitMode = PDOCommitModeEnum::ON;
  }


  final public function setCommitsModeOff(): void {
    $this->commitMode = PDOCommitModeEnum::OFF;
  }


  final public function getCommitsMode(): PDOCommitModeEnum {
    return $this->commitMode;
  }


  public function setThrowOnError(bool $throw = TRUE): void {
    $this->throw = $throw;
  }


  public function begin(): bool {
    if ($this->pdoWrapper->inTransaction()) {
      return $this->errorHandler('There is already an active transaction');
    }

    $result = $this->pdoWrapper->beginTransaction();

    if (!$result) {
      return $this->errorHandler('Failed to begin transaction');
    }

    return $result;
  }


  public function commit(): bool {
    if (!$this->pdoWrapper->inTransaction()) {
      return $this->errorHandler('There is no active transaction.');
    }

    if ($this->getCommitsMode() === PDOCommitModeEnum::OFF) {
      return $this->errorHandler('Commits are disabled');
    }

    $result = $this->pdoWrapper->commit();

    if (!$result) {
      return $this->errorHandler('Failed to commit transaction');
    }

    return $result;
  }


  public function rollback(): bool {
    if (!$this->pdoWrapper->inTransaction()) {
      return $this->errorHandler('There is no active transaction');
    }

    $result = $this->pdoWrapper->rollBack();

    if (!$result) {
      return $this->errorHandler('Failed to rollback transaction');
    }

    return $result;
  }


  public function check(): bool {
    return $this->pdoWrapper->inTransaction();
  }


  private function errorHandler(string $msg): bool {
    $this->logger->error($msg);

    if ($this->throw) {
      throw new Exception($msg);
    }

    return FALSE;
  }


}
