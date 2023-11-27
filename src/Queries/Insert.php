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
  private array $values = [];


  public static function into(string $table): self {
    return new self($table);
  }


  private function __construct(string $table) {
    $this->table = Common::quoteWrap($table);
    $this->dbColumns = Columns::for($table);
  }


  public function value(string $column, $value): self {
    if ($this->dbColumns->has($column)) {
      Common::checkValue($value, $column);
      $this->values[$column] = $value;
    }

    return $this;
  }


  public function values(array $values): self {
    foreach ($values as $col => $value) {
      $this->value($col, $value);
    }

    return $this;
  }


  public function execute(
    ?PDO $pdo = NULL,
    ?PDOBindBuilder $bindBuilder = NULL,
  ): bool {
    $_pdo = $pdo ?? PDOSingleton::get();

    // Check if there are collisions
    $this->checkForCollisions();

    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $query = $this->getSql($bindBuilder);
    $pdoStatement = $_pdo->prepare($query);

    // Bind values
    $bindBuilder->bindToStatement($pdoStatement);

    // Execute query
    return $pdoStatement->execute();
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

    $queryColumns = [];
    $queryParams = [];

    foreach ($this->values as $col => $value) {
      $queryColumns[] = Common::quoteWrap($col);
      $queryParams[] = $bindBuilder->addValueWithPrefix(
        $value,
        $col,
        Common::checkValue($value, $col),
      );
    }

    $qColumns = implode(', ', $queryColumns);
    $qParams = implode(', ', $queryParams);

    $query = "INSERT INTO {$this->table} ({$qColumns}) VALUES ({$qParams});";

    return $query;
  }


  private function checkForCollisions() {
    $isKeyAutoincrement = count($this->dbColumns->autoincrement) > 0;
    if ($isKeyAutoincrement) return;

    // If the primary key is not autoincremented we make sure it is not repeated
    $wheres = [];
    foreach ($this->dbColumns->key as $keyColName) {
      if (isset($this->values[$keyColName])) {
        $wheres[] = Where::equals(
          $keyColName,
          $this->values[$keyColName],
        );
      }
    }

    if (count($wheres) === 0) return;

    $where = Where::and(...$wheres);
    $exists = Select::from($this->table)
      ->where($where)
      ->fetchFirst();

    if (!is_null($exists)) {
      throw new Exception(
        "The values you are trying to insert already exist in the database",
        409,
      );
    }
  }


}
