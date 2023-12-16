<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Core\FetchPaginatedResult;
use Tribal2\DbHandler\Core\FetchResult;
use Tribal2\DbHandler\Enums\OrderByDirectionEnum;
use Tribal2\DbHandler\Interfaces\CacheAwareInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\FetchPaginatedResultInterface;
use Tribal2\DbHandler\Interfaces\FetchResultInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\WhereInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Common;
use Tribal2\DbHandler\Queries\Where;
use Tribal2\DbHandler\Traits\CacheAwareTrait;
use Tribal2\DbHandler\Traits\QueryBeforeExecuteCheckTableTrait;
use Tribal2\DbHandler\Traits\QueryFetchResultsTrait;

class Select extends QueryAbstract implements CacheAwareInterface {
  use QueryBeforeExecuteCheckTableTrait;
  use QueryFetchResultsTrait;
  use CacheAwareTrait;

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


  protected function beforeExecute(): void {
    $this->checkTable();
  }


  public static function _from(
    string $table,
    PDOWrapperInterface $pdo,
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
        throw new Exception('Each element of $columns must be a string');
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
      throw new Exception('HAVING clause requires GROUP BY clause');
    }

    $this->having = $having;
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


  public function limit(?int $limit = NULL): self {
    $this->limit = $limit;
    return $this;
  }


  public function offset(?int $offset = NULL): self {
    $this->offset = $offset;
    return $this;
  }


  public function paginate(int $itemsPerPage): self {
    $this->limit = $itemsPerPage;
    $this->offset = 0;

    return $this;
  }


  public function fetchPage(?int $page = NULL): FetchPaginatedResultInterface {
    if ($this->limit === NULL || $this->offset === NULL) {
      throw new Exception('You must call paginate() before fetchPage().');
    }

    if ($page !== NULL) {
      $this->offset = ($page - 1) * $this->limit;
    }

    $result = $this->execute();

    return new FetchPaginatedResult(
      data: $result,
      count: $this->fetchCount(),
      page: $page ?? ($this->offset / $this->limit + 1),
      perPage: $this->limit,
    );
  }


  public function fetchNextPage(): FetchPaginatedResultInterface {
    $this->offset += $this->limit;
    return $this->fetchPage();
  }


  public function fetchPreviousPage(): FetchPaginatedResultInterface {
    if ($this->offset === 0) {
      throw new Exception('There is no previous page.');
    }

    $this->offset -= $this->limit;
    return $this->fetchPage();
  }


  public function fetchFirstPage(): FetchPaginatedResultInterface {
    $this->offset = 0;

    return $this->fetchPage();
  }


  public function fetchLastPage(): FetchPaginatedResultInterface {
    $totalPages = ceil($this->fetchCount() / $this->limit);
    $this->offset = ($totalPages - 1) * $this->limit;

    return $this->fetchPage();
  }


  public function fetchMethod(int $method): self {
    $this->fetchMethod = $method;
    return $this;
  }


  public function fetchAll(): FetchResultInterface {
    $resultArr = $this->execute();

    return new FetchResult($resultArr);
  }


  public function fetchFirst(): mixed {
    $actualLimit = $this->limit;

    $result = $this
      ->limit(1)
      ->execute();

    $this->limit = $actualLimit;

    return $result[0] ?? NULL;
  }


  public function fetchLast(): mixed {
    $actualLimit = $this->limit;
    $actualOffset = $this->offset;

    $result = $this
      ->limit(1)
      ->offset($this->fetchCount() - 1)
      ->execute();

    $this->limit = $actualLimit;
    $this->offset = $actualOffset;

    return $result[0] ?? NULL;
  }


  public function fetchColumn(?string $colName = NULL): FetchResultInterface {
    $actualColumns = $this->columns;
    $actualFetchMethod = $this->fetchMethod;

    $this->columns = [
      $colName ?? $this->checkAndGetSingleColumn(),
    ];

    $result = $this
      ->fetchMethod(PDO::FETCH_COLUMN)
      ->execute();

    $this->columns = $actualColumns;
    $this->fetchMethod = $actualFetchMethod;

    return new FetchResult($result);
  }


  public function fetchValue(?string $colName = NULL): ?string {
    $actualLimit = $this->limit;

    $this->limit(1);
    $result = $this->fetchColumn($colName);

    $this->limit = $actualLimit;

    return $result->count === 0
      ? NULL
      : $result->data[0];
  }


  public function fetchDistincts(?string $colName = NULL): FetchResultInterface {
    $_colName = $colName ?? $this->checkAndGetSingleColumn();
    return $this->fetchColumn("DISTINCT(`{$_colName}`)");
  }


  public function fetchCount(): int {
    // Store actual value of columns, limit and offset;
    $actualColumns = $this->columns;
    $actualLimit = $this->limit;
    $actualOffset = $this->offset;

    // Reset values and execute query
    $result = $this
      ->limit()
      ->offset()
      ->fetchValue('COUNT(*)');

    // Restore values
    $this->columns = $actualColumns;
    $this->limit = $actualLimit;
    $this->offset = $actualOffset;

    // Return result
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


  private function checkAndGetSingleColumn(): string {
    if (count($this->columns) === 0) {
      $eNoColMsg = 'There are no columns to select. Provide a column name to '
        . 'this method or use ->column(<column>) before.';
      throw new Exception($eNoColMsg);
    }

    if (count($this->columns) !== 1) {
      $eManyColMsg = 'There are more than one column to select. Provide a '
        . 'column name to this method.';
      throw new Exception($eManyColMsg);
    }

    return $this->columns[0];
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
   * @codeCoverageIgnore
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
