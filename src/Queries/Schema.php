<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Traits\QueryBeforeExecuteDoNothingTrait;

class Schema extends QueryAbstract {
  use QueryBeforeExecuteDoNothingTrait;

  private string $_query;


  public static function _checkIfTableExists(
    string $table,
    PDOWrapperInterface $pdo,
  ): bool {
    $schema = new self($pdo);
    return $schema->checkIfTableExists($table);
  }


  public function getSql(?PDOBindBuilderInterface $_ = NULL): string {
    return $this->_query;
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

    $resultArr = parent::_execute($bindBuilder, $this->_pdo);

    return count($resultArr) > 0;
  }


}
