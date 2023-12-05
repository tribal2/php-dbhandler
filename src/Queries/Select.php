<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use stdClass;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Enums\OrderByDirectionEnum;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\QueryInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;
use Tribal2\DbHandler\Queries\Common;
use Tribal2\DbHandler\Queries\Where;

class Select extends QueryAbstract implements QueryInterface {

  /**
   * Columns to select
   *
   * @var string[]
   */
  private array $columns = [];
  private ?WhereInterface $where = NULL;
  private array $groupBy = [];
  private ?WhereInterface $having = NULL;
  private array $orderBy = [];
  private ?int $limit = NULL;
  private ?int $offset = NULL;

  private int $fetchMethod = PDO::FETCH_OBJ;


  public static function _from(
    string $table,
    ?PDO $pdo = NULL,
    ?CommonInterface $common = NULL,
  ): self {
    $select = new self($pdo, $common);
    $select->from($table);

    return $select;
  }


  public function from(string $table): self {
    $this->table = $table;

    return $this;
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


  public function where(WhereInterface $where): self {
    $this->where = $where;
    return $this;
  }


  public function groupBy(string $groupBy): self {
    $this->groupBy[] = $groupBy;
    return $this;
  }


  public function having(WhereInterface $having): self {
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
    $quotedCol = $this->_common->quoteWrap($column);
    $this->orderBy[] = "{$quotedCol} {$direction->value}";
    return $this;
  }


  public function fetchMethod(int $method): self {
    $this->fetchMethod = $method;
    return $this;
  }


  public function execute(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
    ?PDO $pdo = NULL,
  ): array {
    $executedPdoStatement = parent::_execute($bindBuilder, $pdo);

    // Return an array with values depending on the fetch method
    return $executedPdoStatement->fetchAll($this->fetchMethod);
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


  public function getSql(?PDOBindBuilderInterface $bindBuilder = NULL): string {
    $_bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $queryParts = [
      // SELECT
      'SELECT',
      empty($this->columns) ? '*' : $this->_common->parseColumns($this->columns),
      // FROM
      'FROM ' . $this->_common->quoteWrap($this->table),
      // WHERE
      is_null($this->where)
        ? NULL
        : 'WHERE ' . $this->where->getSql($_bindBuilder),
      // GROUP BY
      empty($this->groupBy) ? NULL : 'GROUP BY ' . $this->_common->parseColumns($this->groupBy),
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
   * @param PDOBindBuilderInterface $bindBuilder PDOBindBuilder instance
   * @param array                   $queryArr    Name of the table or array/object with
   *                                             the following keys:
   *                                             - table,
   *                                             - [columns],
   *                                             - [where],
   *                                             - [group_by],
   *                                             - [having],
   *                                             - [limit],
   *                                             - [sort]
   * @param CommonInterface|null    $common      Common instance
   *
   * @return string The generated query
   * @deprecated Use Select::from instead
   */
  public static function queryFromArray(
    PDOBindBuilderInterface $bindBuilder,
    array $queryArr,
    ?CommonInterface $common = NULL,
  ): string {
    $table = $queryArr['table'];

    // Configuramos las columnas a obtener
    $_common = $common ?? new Common();
    $cols = $_common->parseColumns($queryArr['columns'] ?? '*');

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
