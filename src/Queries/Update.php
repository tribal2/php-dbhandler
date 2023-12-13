<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Interfaces\ColumnsFactoryInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Traits\ColumnsAwareTrait;
use Tribal2\DbHandler\Traits\QueryBeforeExecuteCheckIfReadOnlyTrait;
use Tribal2\DbHandler\Traits\QueryBeforeExecuteCheckTableTrait;
use Tribal2\DbHandler\Traits\QueryFetchCountTrait;

class Update extends QueryAbstract {
  use ColumnsAwareTrait;
  use QueryBeforeExecuteCheckIfReadOnlyTrait;
  use QueryBeforeExecuteCheckTableTrait;
  use QueryFetchCountTrait;

  private array $values = [];
  private ?WhereInterface $whereClause = NULL;


  protected function afterConstruct(): void {
    $this->setColumnsFactory();
  }


  protected function beforeExecute(): void {
    $this->checkTable();
    $this->checkIfReadOnly();
  }


  public static function _table(
    string $table,
    PDOWrapperInterface $pdo,
    ?ColumnsFactoryInterface $columnsFactory = NULL,
    ?CommonInterface $common = NULL,
  ): self {
    $instance = new self($pdo, $common);

    if (!is_null($columnsFactory)) {
      $instance->setColumnsFactory($columnsFactory);
    }

    $instance->table($table);

    return $instance;
  }


  public function table(string $table): self {
    $this->table = $table;

    // Get the columns of the table
    $this->dbColumns = $this->_columnsFactory->make($table);

    return $this;
  }


  public function set(string $column, mixed $value): self {
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


}
