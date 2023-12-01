<?php

namespace Tribal2\DbHandler;

use Exception;
use PDO;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;

class Schema {

  private PDO $_pdo;
  private PDOBindBuilderInterface $_bindBuilder;


  public static function checkIfTableExists(string $table): bool {
    $schema = new self();
    return $schema->_checkIfTableExists($table);
  }


  public function __construct(
    ?PDO $pdo = NULL,
    ?PDOBindBuilderInterface $bindBuilder = NULL
  ) {
    $this->_pdo = $pdo ?? PDOSingleton::get();
    $this->_bindBuilder = $bindBuilder ?? new PDOBindBuilder();
  }


  /**
   * Helper method verify if a database table exists
   * @param string $table Name of the table
   *
   * @return bool True if the table exists, false otherwise
   * @throws Exception
   */
  public function _checkIfTableExists(string $table): bool {
    $tablePlaceholder = $this->_bindBuilder->addValue($table);
    $query = "SHOW TABLES LIKE {$tablePlaceholder};";

    $sth = $this->_pdo->prepare($query);
    $this->_bindBuilder->bindToStatement($sth);

    $sth->execute();

    return ($sth->rowCount() === 1);
  }


}
