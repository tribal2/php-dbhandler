<?php

use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Queries\Common;
use Tribal2\DbHandler\Queries\Where;

class Select {

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
