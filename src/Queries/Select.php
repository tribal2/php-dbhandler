<?php

namespace Tribal2\DbHandler\Queries;

use Tribal2\DbHandler\Enums\OrderByDirectionEnum;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Common;
use Tribal2\DbHandler\Queries\Where;

class Select {

  private PDOBindBuilder $bindBuilder;

  private string $table;

  /**
   * Columns to select
   *
   * @var string[]
   */
  private array $columns = [];

  private ?Where $where = NULL;

  private array $groupBy = [];

  private ?Where $having = NULL;

  private array $orderBy = [];

  private string $limit = '';
  private string $offset = '';



  public static function from(string $table): self {
    return new self($table);
  }


  private function __construct(string $table) {
    $this->table = Common::quoteWrap($table);
    $this->bindBuilder = new PDOBindBuilder();
  }


  public function column(string $column): self {
    $this->columns[] = $column;
    return $this;
  }


  public function columns(array $columns): self {
    foreach ($columns as $column) {
      if (!is_string($column)) {
        throw new \Exception('Each element of $columns must be a string');
      }

      $this->columns[] = $column;
    }
    return $this;
  }


  public function where(Where $where): self {
    $this->where = $where;
    return $this;
  }


  public function groupBy(string $groupBy): self {
    $this->groupBy[] = $groupBy;
    return $this;
  }


  public function having(Where $having): self {
    if (count($this->groupBy) === 0) {
      throw new \Exception('HAVING clause requires GROUP BY clause');
    }

    $this->having = $having;
    return $this;
  }


  public function limit(int $limit): self {
    $limitPlaceHolder = $this->bindBuilder->addValueWithPrefix(
      $limit,
      'limit',
      \PDO::PARAM_INT
    );

    $this->limit = "LIMIT {$limitPlaceHolder}";
    return $this;
  }


  public function offset(int $offset): self {
    $offsetPlaceHolder = $this->bindBuilder->addValueWithPrefix(
      $offset,
      'offset',
      \PDO::PARAM_INT
    );

    $this->offset = "OFFSET {$offsetPlaceHolder}";
    return $this;
  }


  public function orderBy(
    string $column,
    OrderByDirectionEnum $direction = OrderByDirectionEnum::ASC,
  ): self {
    $quotedCol = Common::quoteWrap($column);
    $this->orderBy[] = "{$quotedCol} {$direction->value}";
    return $this;
  }


  public function execute(\PDO $pdo, $options): array {
    $query = $this->getSql();
    $pdoStatement = $pdo->prepare($query);

    // Bind values
    $this->bindBuilder->bindToStatement($pdoStatement);

    // Execute query
    $pdoStatement->execute();

    // Return results
    return $pdoStatement->fetchAll($options);
  }


  public function getSql(): string {
    $queryParts = [
      // SELECT
      'SELECT',
      empty($this->columns) ? '*' : Common::parseColumns($this->columns),
      // FROM
      "FROM {$this->table}",
      // WHERE
      is_null($this->where)
        ? ''
        : 'WHERE ' . $this->where->getSql($this->bindBuilder),
      // GROUP BY
      empty($this->groupBy) ? '' : 'GROUP BY ' . Common::parseColumns($this->groupBy),
      // HAVING
      is_null($this->having)
        ? ''
        : 'HAVING ' . $this->having->getSql($this->bindBuilder),
      // ORDER BY
      empty($this->orderBy) ? '' : 'ORDER BY ' . implode(', ', $this->orderBy),
      // LIMIT
      $this->limit,
      // OFFSET
      $this->offset,
    ];

    return implode(' ', array_filter($queryParts)) . ';';
  }


  /**
   * Generate a SELECT query
   *
   * @param PDOBindBuilder $bindBuilder PDOBindBuilder instance
   * @param array          $queryArr    Name of the table or array/object with
   *                                    the following keys:
   *                                    - table,
   *                                    - [columns],
   *                                    - [where],
   *                                    - [group_by],
   *                                    - [having],
   *                                    - [limit],
   *                                    - [sort]
   *
   * @return string The generated query
   */
  public static function queryFromArray(
    PDOBindBuilder $bindBuilder,
    array $queryArr
  ): string {
    $table = $queryArr['table'];

    // Configuramos las columnas a obtener
    $cols = Common::parseColumns($queryArr['columns'] ?? '*');

    // Configuramos las cláusulas
    $where = $queryArr['where'] ?? '';
    if (is_array($where)) {
      $where = Where::generate($bindBuilder, $where);
    }
    $_where = ($where !== '') ? "WHERE $where" : '';

    // Configuramos el agrupamiento
    $group_by = $queryArr['group_by'] ?? '';
    $group_by = is_array($group_by)
      ? implode(', ', $group_by)
      : $group_by;
    $_group_by = ($group_by !== '') ? "GROUP BY `$group_by`" : '';

    // Configuramos el having
    $having = $queryArr['having'] ?? '';
    if (is_array($having)) {
      $having = Where::generate($bindBuilder, $having);
    }
    $_having = ($_group_by !== '' && $having !== '')
      ? "HAVING $having"
      : '';

    // Configuramos el orden
    $sort = $queryArr['sort'] ?? [];
    if(is_array($sort)) {
      $orderArray = [];
      foreach($sort as $sortCol => $sortOrder) {
        $orderArray[] = "$sortCol $sortOrder";
      }
      $sort = implode(', ', $orderArray);
    }
    $_order = ($sort !== '') ? "ORDER BY $sort" : '';

    // Configuramos el límite de búsqueda
    $limit = $queryArr['limit'] ?? 0;
    $_limit = ($limit === 0)
      ? ''
      : 'LIMIT ' . $bindBuilder->addValue($limit, \PDO::PARAM_INT);

    // Configuramos el final del query
    $queryEndArr = [];
    $queryEndParts = [$_where, $_group_by, $_having, $_order, $_limit];
    foreach($queryEndParts as $part) {
      if($part !== '') { $queryEndArr[] = $part; }
    }
    $queryEnd = implode(' ', $queryEndArr);

    return "SELECT $cols FROM $table $queryEnd;";
  }


}
