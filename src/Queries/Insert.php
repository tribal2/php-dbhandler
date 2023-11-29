<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;

use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;
use Tribal2\DbHandler\Queries\Common;
use Tribal2\DbHandler\Queries\Where;
use Tribal2\DbHandler\Table\Columns;

class Insert {

  private string $table;
  private Columns $dbColumns;
  private array $values = [ [] ];


  public static function into(string $table): self {
    return new self($table);
  }


  private function __construct(string $table) {
    $this->table = $table;
    $this->dbColumns = Columns::for($table);
  }


  public function value(string $column, $value): self {
    // Select the last row in the values array
    $row = &$this->values[count($this->values) - 1];

    // Add the value to the row if the column exists in the database
    if ($this->dbColumns->has($column)) {
      Common::checkValue($value, $column);
      $row[$column] = $value;
    }

    return $this;
  }


  public function values(array $values): self {
    foreach ($values as $col => $value) {
      $this->value($col, $value);
    }

    return $this;
  }


  public function rows(array $rows): self {
    // We only take the columns that exist in the database from the first row
    $columns = [];
    foreach ($rows[0] as $col => $_) {
      if ($this->dbColumns->has($col)) $columns[] = $col;
    }

    // We check the values of each row, if there is no value for a column we
    // set it to NULL
    $this->values = [];
    foreach ($rows as $row) {
      $valueRow = [];
      foreach ($columns as $col) {
        $valueRow[$col] = $row[$col] ?? NULL;
      }
      $this->values[] = $valueRow;
    }

    return $this;
  }


  public function getValues(): array {
    return $this->values;
  }


  public function getSql(?PDOBindBuilder $bindBuilder = NULL): string {
    if (count($this->values) === 0) {
      throw new Exception(
        'You must provide at least one value to insert',
        400,
      );
    }

    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $columns = [];
    $queryColumns = [];
    foreach ($this->values[0] as $col => $_) {
      $columns[] = $col;
      $queryColumns[] = Common::quoteWrap($col);
    }

    $rows = [];
    foreach ($this->values as $row) {
      $queryParams = [];
      foreach ($columns as $col) {
        $value = $row[$col] ?? NULL;
        $queryParams[] = $bindBuilder->addValueWithPrefix(
          $value,
          $col,
          Common::checkValue($value, $col),
        );
      }
      $rows[] = '(' . implode(', ', $queryParams) . ')';
    }

    $qColumns = implode(', ', $queryColumns);
    $qRows = implode(', ', $rows);

    $quotedTable = Common::quoteWrap($this->table);

    $query = "INSERT INTO {$quotedTable} ({$qColumns}) VALUES {$qRows};";

    return $query;
  }


  public function execute(
    ?PDO $pdo = NULL,
    ?PDOBindBuilder $bindBuilder = NULL,
  ): int {
    $_pdo = $pdo ?? PDOSingleton::get();

    // Check if there are collisions
    $this->checkForCollisions();

    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $query = $this->getSql($bindBuilder);
    $pdoStatement = $_pdo->prepare($query);

    // Bind values
    $bindBuilder->bindToStatement($pdoStatement);

    // Execute query
    $pdoStatement->execute();

    // Return the number of affected rows
    return $pdoStatement->rowCount();
  }


  private function checkForCollisions() {
    $isKeyAutoincrement = count($this->dbColumns->autoincrement) > 0;
    if ($isKeyAutoincrement) return;

    // If the primary key is not autoincremented we make sure it is not repeated
    $wheres = [];
    foreach ($this->values as $row) {
      $rowWhere = [];
      foreach ($this->dbColumns->key as $keyColName) {
        if (isset($row[$keyColName])) {
          $rowWhere[] = Where::equals(
            $keyColName,
            $row[$keyColName],
          );
        }
      }

      if (count($rowWhere) > 0) {
        $wheres[] = Where::and(...$rowWhere);
      }
    }

    if (count($wheres) === 0) return;

    $where = Where::or(...$wheres);
    $exists = Select::from($this->table)
      ->where($where)
      ->fetchFirst();

    if (!is_null($exists)) {
      throw new Exception(
        'The values you are trying to insert already exist in the database',
        409,
      );
    }
  }


}
