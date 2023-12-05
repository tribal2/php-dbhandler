<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\Abstracts\QueryModAbstract;
use Tribal2\DbHandler\Interfaces\ColumnsFactoryInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\QueryInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;

class Update extends QueryModAbstract implements QueryInterface {

  private array $values = [];
  private ?WhereInterface $whereClause = NULL;


  public static function _table(string $table,
    ?PDO $pdo = NULL,
    ?CommonInterface $common = NULL,
    ?ColumnsFactoryInterface $columnsFactory = NULL,
  ): self {
    $instance = new self($pdo, $common, $columnsFactory);
    $instance->table($table);

    return $instance;
  }


  public function table(string $table): self {
    $this->table = $table;

    // Get the columns of the table
    $this->dbColumns = $this->_columnsFactory->make($table);

    return $this;
  }


  public function set(string $column, $value): self {
    if (!$this->dbColumns->has($column)) {
      $eMsg = "Column '{$column}' does not exist in table '{$this->table}'";
      throw new Exception($eMsg, 400);
    }

    $this->_common->checkValue($value, $column);

    $this->values[$column] = $value;

    return $this;
  }


  public function where(WhereInterface $where): self {
    $this->whereClause = $where;
    return $this;
  }


  public function getSql(?PDOBindBuilderInterface $bindBuilder = NULL): string {
    if (empty($this->values)) {
      throw new Exception('No values provided for update', 400);
    }

    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $setParts = [];
    foreach ($this->values as $col => $value) {
      $parts = [
        $this->_common->quoteWrap($col),
        $bindBuilder->addValueWithPrefix(
          $value,
          $col,
          $this->_common->checkValue($value, $col),
        ),
      ];

      $setParts[] = implode(' = ', $parts);
    }

    $queryArr = [
      'UPDATE',
      $this->_common->quoteWrap($this->table),
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
    ?PDOBindBuilderInterface $bindBuilder = NULL
  ): int {
    $this->beforeExecute();

    $_pdo = $pdo ?? PDOSingleton::get();
    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $query = $this->getSql($bindBuilder);

    $pdoStatement = $_pdo->prepare($query);
    $bindBuilder->bindToStatement($pdoStatement);

    $pdoStatement->execute();

    return $pdoStatement->rowCount();
  }


}
