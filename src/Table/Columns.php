<?php

namespace Tribal2\DbHandler\Table;

use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Interfaces\ColumnsInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Traits\QueryBeforeExecuteCheckTableTrait;
use Tribal2\DbHandler\Traits\QueryFetchResultsTrait;

class Columns extends QueryAbstract implements ColumnsInterface {
  use QueryBeforeExecuteCheckTableTrait;
  use QueryFetchResultsTrait;

  public array $columns = [];
  public array $key = [];
  public array $nonKey = [];
  public array $autoincrement = [];


  protected function beforeExecute(): void {
    $this->checkTable();
  }


  public static function _for(
    string $table,
    PDOWrapperInterface $pdoWrapper
  ): Columns {
    $instance = new Columns($pdoWrapper);

    return $instance->for($table);
  }


  public function for(string $table): self {
    $this->table = $table;

    $dbColumns = $this->execute();
    $this->parse($dbColumns);

    return $this;
  }


  public function has(string $column): bool {
    return in_array($column, $this->columns);
  }


  public function getSql(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
  ): string {
    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $db = $bindBuilder->addValueWithPrefix($this->_pdo->getDbName(), 'db');
    $table = $bindBuilder->addValueWithPrefix($this->table, 'table');

    $query = "
      SELECT
          COLUMN_NAME,
          COLUMN_KEY,
          EXTRA
      FROM
          information_schema.COLUMNS
      WHERE
          TABLE_SCHEMA   = {$db}
          AND TABLE_NAME = {$table};
    ";

    return $query;
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
