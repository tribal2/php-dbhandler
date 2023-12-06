<?php

namespace Tribal2\DbHandler\Core;

use Exception;
use PDO;
use PDOStatement;
use Tribal2\DbHandler\Helpers\Logger;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\DbConfigInterface;
use Tribal2\DbHandler\Interfaces\LoggerInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;

class PDOWrapper implements PDOWrapperInterface {

  // Dependencies
  private DbConfigInterface $_config;
  private LoggerInterface $_logger;

  // Instance
  private PDO $_pdo;
  private string $_queryType;


  public function __construct(
    DbConfigInterface $config,
    LoggerInterface $logger,
  ) {
    $this->_config = $config;
    $this->_logger = $logger;

    $this->_pdo = new PDO(
      $config->getConnString(),
      $config->getUser(),
      $config->getPassword()
    );
  }


  public function execute(
    string $query,
    PDOBindBuilderInterface $bindBuilder,
    ?int $fetchMode = PDO::FETCH_OBJ,
  ): array|int {
    try {
      // Set query type
      $this->setQueryType($query);

      // Throw if read only mode is enabled
      $this->_verifyReadOnlyMode($query);

      // Prepare statement
      $stmt = $this->_prepare($query, $bindBuilder);

      // Execute statement
      $this->_execute($stmt);

      // Log query execution result
      $this->_logQueryExecutionResult($stmt);

      // Return results
      return $this->_return($stmt, $fetchMode);
    } catch (Exception $e) {
      $this->_logger->log($e->getMessage(), Logger::ERROR);
      throw $e;
    }
  }


  public function getDbName(): string {
    return $this->_config->getDbName();
  }


  private function setQueryType(string $query): void {
    // Trim query, remove new lines and spaces
    $noNewlines = str_replace("\n", ' ', $query);
    $trimmed = trim($noNewlines);

    $words = explode(' ', $trimmed);
    $firstWord = $words[0];

    // SELECT, CALL, UPDATE, DELETE, INSERT
    $this->_queryType = strtoupper($firstWord);
  }


  private function _verifyReadOnlyMode(): void {
    // Allow select queries always
    if ($this->_queryType === 'SELECT') {
      return;
    }

    // Throw if read only mode is enabled
    if ($this->_config->isReadOnly()) {
      $eMsg = "Can't execute statement. Read only mode is enabled.";
      $this->_logger->log($eMsg, Logger::ERROR);
      throw new Exception($eMsg);
    }
  }


  private function _prepare(
    string $query,
    PDOBindBuilderInterface $bindBuilder,
  ): PDOStatement {
    $this->_logger->log("Preparing query: $query", Logger::DEBUG);

    // Prepare statement
    $stmt = $this->_pdo->prepare($query);

    if (!$stmt) {
      $eMsg = "Error preparing statement: $query";
      $this->_logger->log($eMsg, Logger::ERROR);
      throw new Exception($eMsg);
    }

    // Bind params
    $this->_logger->log(
      "Binding params: " . json_encode($bindBuilder->getValues()),
      Logger::DEBUG,
    );
    $this->_logger->log(
      "Final query: " . $bindBuilder->debugQuery($query),
      Logger::DEBUG,
    );

    $bindBuilder->bindToStatement($stmt);

    return $stmt;
  }


  private function _execute(PDOStatement $stmt): void {
    $this->_logger->log("Executing statement: {$stmt->queryString}", Logger::DEBUG);

    if (!$stmt->execute()) {
      $eMsg = "Error executing statement: $stmt";
      $this->_logger->log($eMsg, Logger::ERROR);
      throw new Exception($eMsg);
    }
  }


  private function _logQueryExecutionResult(
    PDOStatement $statement,
  ): void {
    if ($this->_queryType === 'INSERT') {
      $resTitle = 'Last insert id';
      $result = $this->_pdo->lastInsertId();
    } else {
      $resTitle = $this->_queryType === 'SELECT'
        ? 'Rows fetched'
        : 'Affected rows';
      $result = $statement->rowCount();
    }

    $this->_logger->log(
      "Query executed successfully. {$resTitle}: {$result}.",
      Logger::DEBUG,
    );
  }


  private function _return(
    PDOStatement $stmt,
    int $fetchMode,
  ): array|int {
    switch ($this->_queryType) {
      case 'SELECT':
      case 'CALL':
      case 'SHOW':
        return $stmt->fetchAll($fetchMode);

      // case 'INSERT':
      //   return $this->_pdo->lastInsertId();

      default:
        return $stmt->rowCount();
    }
  }


}
