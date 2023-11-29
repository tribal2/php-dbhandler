<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;
use Tribal2\DbHandler\Queries\Common;
use Tribal2\DbHandler\Queries\Where;
use Tribal2\DbHandler\Table\Columns;

class Update {

  private string $table;
  private array $values = [];
  private Columns $dbColumns;
  private ?Where $whereClause = NULL;


  public static function table(string $table): self {
    return new self($table);
  }


  private function __construct(string $table) {
    $this->table = $table;
    $this->dbColumns = Columns::for($table);
  }


  public function set(string $column, $value): self {
    if (!$this->dbColumns->has($column)) {
      $eMsg = "Column '{$column}' does not exist in table '{$this->table}'";
      throw new Exception($eMsg, 400);
    }

    Common::checkValue($value, $column);

    $this->values[$column] = $value;

    return $this;
  }


  public function where(Where $where): self {
    $this->whereClause = $where;
    return $this;
  }


  public function getSql(?PDOBindBuilder $bindBuilder = NULL): string {
    if (empty($this->values)) {
      throw new Exception('No values provided for update', 400);
    }

    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $setParts = [];
    foreach ($this->values as $col => $value) {
      $parts = [
        Common::quoteWrap($col),
        $bindBuilder->addValueWithPrefix(
          $value,
          $col,
          Common::checkValue($value, $col),
        ),
      ];

      $setParts[] = implode(' = ', $parts);
    }

    $queryArr = [
      'UPDATE',
      Common::quoteWrap($this->table),
      'SET',
      implode(', ', $setParts),
      $this->whereClause
        ? 'WHERE '. $this->whereClause->getSql($bindBuilder)
        : NULL
    ];

    return implode(' ', array_filter($queryArr)) . ';';
  }


  public function execute(
    ?PDO $pdo = NULL,
    ?PDOBindBuilder $bindBuilder = NULL
  ): bool {
    $_pdo = $pdo ?? PDOSingleton::get();
    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $query = $this->getSql($bindBuilder);

    $pdoStatement = $_pdo->prepare($query);
    $bindBuilder->bindToStatement($pdoStatement);

    return $pdoStatement->execute();
  }


}
