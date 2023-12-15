<?php

namespace Tribal2\DbHandler\Core;

use Exception;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tribal2\DbHandler\DbConfig;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\DbConfigInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;

class PDOWrapper implements PDOWrapperInterface {

  // Dependencies
  private ?DbConfigInterface $_config = NULL;
  private LoggerInterface $_logger;

  // Instance
  private PDO $_pdo;


  public static function fromPdo(
    PDO $pdo,
    ?LoggerInterface $logger = NULL,
  ): PDOWrapperInterface {
    $dbName = $pdo->query('SELECT DATABASE();')->fetchColumn();

    $instance = new self(NULL, $logger);
    $instance->_config = new DbConfig($dbName);
    $instance->_pdo = $pdo;

    return $instance;
  }


  public function __construct(
    ?DbConfigInterface $config = NULL,
    ?LoggerInterface $logger = NULL,
    ?PDO $pdo = NULL,
  ) {
    $this->_logger = $logger ?? new NullLogger();

    if (!is_null($config)) {
      $this->_config = $config;

      $this->_pdo = $pdo ?? new PDO(
        $config->getConnString(),
        $config->getUser(),
        $config->getPassword()
      );
    }
  }


  public function execute(
    string $query,
    PDOBindBuilderInterface $bindBuilder,
  ): PDOStatement {
    try {
      // Prepare statement
      $stmt = $this->_prepare($query, $bindBuilder);

      // Execute statement
      $this->_logger->debug("Executing statement: {$stmt->queryString}");
      if (!$stmt->execute()) {
        $eMsg = "Error executing statement: {$stmt->queryString}";
        $this->_logger->error($eMsg);
        throw new Exception($eMsg);
      }

      return $stmt;
    } catch (Exception $e) {
      $this->_logger->error($e->getMessage());
      throw $e;
    }
  }


  public function getDbName(): string {
    return $this->_config->getDbName();
  }


  public function getLastInsertId(): string|false {
    return $this->_pdo->lastInsertId();
  }


  public function setReadOnlyMode(bool $readOnly): void {
    if ($readOnly) {
      $this->_config->withReadOnlyMode();
    } else {
      $this->_config->withReadOnlyModeOff();
    }
  }


  public function isReadOnly(): bool {
    return $this->_config->isReadOnly();
  }


  public function beginTransaction(): bool {
    return $this->_pdo->beginTransaction();
  }


  public function commit(): bool {
    return $this->_pdo->commit();
  }


  public function rollBack(): bool {
    return $this->_pdo->rollBack();
  }


  public function inTransaction(): bool {
    return $this->_pdo->inTransaction();
  }


  private function _prepare(
    string $query,
    PDOBindBuilderInterface $bindBuilder,
  ): PDOStatement {
    $this->_logger->debug("Preparing query: $query");

    // Prepare statement
    $stmt = $this->_pdo->prepare($query);

    if (!$stmt) {
      $eMsg = "Error preparing statement: $query";
      $this->_logger->error($eMsg);
      throw new Exception($eMsg);
    }

    // Bind params
    $this->_logger->debug("Binding params: ", $bindBuilder->getValues());
    $this->_logger->debug("Final query: " . $bindBuilder->debugQuery($query));

    $bindBuilder->bindToStatement($stmt);

    return $stmt;
  }


}
