<?php

namespace Tribal2\DbHandler\Core;

use Exception;
use Psr\Log\LoggerInterface;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;
use Tribal2\DbHandler\Helpers\LoggerNull;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;

class Transaction {

  // Dependencies
  private static PDOWrapperInterface $pdoWrapper;
  private static ?LoggerInterface $logger = NULL;


  // Class properties
  private bool $throw = FALSE;
  private PDOCommitModeEnum $commitMode = PDOCommitModeEnum::ON;


  public function __construct(
    PDOWrapperInterface $pdoWrapper,
    ?LoggerInterface $logger = NULL,
  ) {
    $this->pdoWrapper = $pdoWrapper;

    // Use default logger if none is provided
    $this->logger = ($logger === NULL)
      ? new LoggerNull()
      : $logger;
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


  public function begin(): bool {
    $dbh = $this->pdoWrapper->getPdo();

    if ($dbh->inTransaction()) {
      return $this->errorHandler('There is already an active transaction');
    }

    return $dbh->beginTransaction();
  }


  public function commit(): bool {
    $dbh = $this->pdoWrapper->getPdo();

    if (!$dbh->inTransaction()) {
      return $this->errorHandler('There is no active transaction.');
    }

    if ($this->getCommitsMode() === PDOCommitModeEnum::OFF) {
      return $this->errorHandler('Commits are disabled');
    }

    return $dbh->commit();
  }


  public function rollback(): bool {
    $dbh = $this->pdoWrapper->getPdo();

    if (!$dbh->inTransaction()) {
      return $this->errorHandler('There is no active transaction');
    }

    return $dbh->rollBack();
  }


  public function check() {
    $dbh = $this->pdoWrapper->getPdo();

    return $dbh->inTransaction();
  }


  private function errorHandler($msg): bool {
    $this->logger->error($msg);

    if ($this->throw) {
      throw new Exception($msg);
    }

    return FALSE;
  }


}
