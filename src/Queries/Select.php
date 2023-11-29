<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use stdClass;
use Tribal2\DbHandler\Enums\OrderByDirectionEnum;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;
use Tribal2\DbHandler\Queries\Common;
use Tribal2\DbHandler\Queries\Where;

class Select {

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
  private ?int $limit = NULL;
  private ?int $offset = NULL;

  private int $fetchMethod = PDO::FETCH_OBJ;


  public static function from(string $table): self {
    return new self($table);
  }


  private function __construct(string $table) {
    $this->table = $table;
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
    $this->limit = $limit;
    return $this;
  }


  public function offset(int $offset): self {
    $this->offset = $offset;
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


  public function fetchMethod(int $method): self {
    $this->fetchMethod = $method;
    return $this;
  }


  public function execute(
    ?PDO $pdo = NULL,
    ?PDOBindBuilder $bindBuilder = NULL
  ): array {
    $_pdo = $pdo ?? PDOSingleton::get();
    $_bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $query = $this->getSql($_bindBuilder);
    $pdoStatement = $_pdo->prepare($query);

    // Bind values
    $_bindBuilder->bindToStatement($pdoStatement);

    // Execute query
    $pdoStatement->execute();

    return $pdoStatement->fetchAll($this->fetchMethod);
  }


  public function fetchAll(): array {
    return $this->execute();
  }


  public function fetchFirst(): ?stdClass {
    $result = $this
      ->limit(1)
      ->execute();

    return $result[0] ?? NULL;
  }


  public function fetchColumn(?string $colName = NULL): array {
    if (!is_null($colName)) {
      $this->columns = [ $colName ];
    }

    // Check if there is only one column
    if (is_null($colName) && count($this->columns) !== 1) {
      throw new \Exception('Only one column can be selected');
    }

    $result = $this
      ->fetchMethod(PDO::FETCH_COLUMN)
      ->execute();

    return $result;
  }


  public function fetchValue(?string $colName = NULL): ?string {
    $this->limit(1);
    $result = $this->fetchColumn($colName);

    return $result[0] ?? NULL;
  }


  public function fetchDistincts(string $colName): array {
    if (is_null($colName)) {
      throw new Exception('Column name must be specified');
    }

    return $this->fetchColumn("DISTINCT(`{$colName}`)");
  }


  public function fetchCount(): int {
    $result = $this->fetchValue('COUNT(*)');
    return (int)$result;
  }


  public function getSql(?PDOBindBuilder $bindBuilder = NULL): string {
    $_bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $queryParts = [
      // SELECT
      'SELECT',
      empty($this->columns) ? '*' : Common::parseColumns($this->columns),
      // FROM
      'FROM ' . Common::quoteWrap($this->table),
      // WHERE
      is_null($this->where)
        ? NULL
        : 'WHERE ' . $this->where->getSql($_bindBuilder),
      // GROUP BY
      empty($this->groupBy) ? NULL : 'GROUP BY ' . Common::parseColumns($this->groupBy),
      // HAVING
      is_null($this->having)
        ? NULL
        : 'HAVING ' . $this->having->getSql($_bindBuilder),
      // ORDER BY
      empty($this->orderBy) ? '' : 'ORDER BY ' . implode(', ', $this->orderBy),
      // LIMIT
      is_null($this->limit)
        ? NULL
        : 'LIMIT ' . $_bindBuilder->addValueWithPrefix(
          $this->limit,
          'limit',
          PDO::PARAM_INT
        ),
      // OFFSET
      is_null($this->offset)
        ? NULL
        : 'OFFSET ' . $_bindBuilder->addValueWithPrefix(
          $this->offset,
          'offset',
          PDO::PARAM_INT
        ),
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
      : 'LIMIT ' . $bindBuilder->addValue($limit, PDO::PARAM_INT);

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
