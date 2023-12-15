<?php

namespace Tribal2\DbHandler\Interfaces;

use Psr\Log\LoggerInterface;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;

interface TransactionInterface {


  public function __construct(
    PDOWrapperInterface $pdoWrapper,
    ?LoggerInterface $logger = NULL,
  );


  public function setCommitsModeOn(): void;


  public function setCommitsModeOff(): void;


  public function getCommitsMode(): PDOCommitModeEnum;


  public function setThrowOnError(bool $throw = TRUE): void;


  public function begin(): bool;


  public function commit(): bool;


  public function rollback(): bool;


  public function check(): bool;


}
