<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\SchemaInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Traits\QueryFetchResultsTrait;

class Schema extends QueryAbstract implements SchemaInterface {
  use QueryFetchResultsTrait;

  private string $database;
  private string $_query;


  public function afterConstruct(): void {
    $this->database = $this->_pdo->getDbName();
  }


  public function getSql(?PDOBindBuilderInterface $_ = NULL): string {
    return $this->_query;
  }


  public function getDatabase(): string {
    return $this->database;
  }


  /**
   * Helper method verify if a database table exists
   * @param string $table Name of the table
   *
   * @return bool True if the table exists, false otherwise
   * @throws Exception
   */
  public function checkIfTableExists(string $table): bool {
    $bindBuilder = new PDOBindBuilder();
    $tablePlaceholder = $bindBuilder->addValueWithPrefix(
      $table,
      'table',
    );
    $this->_query = "SHOW TABLES LIKE {$tablePlaceholder};";

    $resultArr = $this->execute($bindBuilder);

    return count($resultArr) > 0;
  }


  public function getStoredProcedureArguments(string $procedure): array {
    $bindBuilder = new PDOBindBuilder();
    $dbPlaceholder = $bindBuilder->addValue($this->database);
    $namePlaceholder = $bindBuilder->addValue($procedure);

    $this->_query = "
      SELECT
          ORDINAL_POSITION,
          PARAMETER_NAME,
          DATA_TYPE,
          CHARACTER_MAXIMUM_LENGTH
      FROM
          information_schema.PARAMETERS
      WHERE
          SPECIFIC_SCHEMA = {$dbPlaceholder}
          AND SPECIFIC_NAME = {$namePlaceholder};
    ";

    return $this->execute($bindBuilder);
  }


}
