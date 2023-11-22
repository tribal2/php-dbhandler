<?php

namespace Tribal2\DbHandler;

use Exception;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;

class Schema {


  /**
   * Helper method verify if a database table exists
   * @param string $table Name of the table
   *
   * @return bool True if the table exists, false otherwise
   * @throws Exception
   */
  public static function checkIfTableExists(string $table): bool {
    $pdo = PDOSingleton::get();
    $bindBuilder = new PDOBindBuilder();

    $tablePlaceholder = $bindBuilder->addValue($table);
    $query = "SHOW TABLES LIKE {$tablePlaceholder};";

    $sth = $pdo->prepare($query);
    $bindBuilder->bindToStatement($sth);

    $sth->execute();

    return ($sth->rowCount() === 1);
  }


}
