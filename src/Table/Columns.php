<?php

namespace Tribal2\DbHandler\Table;

use PDO;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;

class Columns {

  public string $table;

  public array $columns = [];
  public array $key = [];
  public array $nonKey = [];
  public array $autoincrement = [];


  public static function for(string $table): Columns {
    return new Columns($table);
  }


  private function __construct(string $table) {
    $this->table = $table;

    $dbColumns = $this->fetch();
    $this->parse($dbColumns);
  }


  private function fetch(): array {
    $pdo = PDOSingleton::get();
    $dbName = PDOSingleton::getDBName();
    $bindBuilder = new PDOBindBuilder();

    $dbPlaceholder = $bindBuilder->addValue($dbName);
    $tablePlaceholder = $bindBuilder->addValue($this->table);

    $query = "
      SELECT
          COLUMN_NAME,
          COLUMN_KEY,
          EXTRA
      FROM
          information_schema.COLUMNS
      WHERE
          TABLE_SCHEMA   = {$dbPlaceholder}
          AND TABLE_NAME = {$tablePlaceholder};
    ";

    $sth = $pdo->prepare($query);
    $bindBuilder->bindToStatement($sth);

    $sth->execute();

    return $sth->fetchAll(PDO::FETCH_OBJ);
  }


  private function parse(array $columns): void {
    foreach ($columns as $column) {
      $this->columns[] = $column->COLUMN_NAME;

      if ($column->COLUMN_KEY === 'PRI') {
        $this->key[] = $column->COLUMN_NAME;
      } else {
        $this->nonKey[] = $column->COLUMN_NAME;
      }

      if ($column->EXTRA === 'auto_increment') {
        $this->autoincrement[] = $column->COLUMN_NAME;
      }
    }
  }


}
