<?php

namespace Tribal2\DbHandler;

use Exception;
use PDO;
use PDOException;
use stdClass;
use Tribal2\DbHandler\Enums\PDOCommitModeEnum;
use Tribal2\DbHandler\Helpers\Cache;
use Tribal2\DbHandler\Helpers\Logger;
use Tribal2\DbHandler\Interfaces\CacheInterface;
use Tribal2\DbHandler\Interfaces\LoggerInterface;

class DbHandler {

  private static $instances = [];

  private static ?LoggerInterface $logger = NULL;

  private static ?CacheInterface $cache = NULL;

  private static bool $isReadOnlyMode = FALSE;


  /**
   * Objeto PDO
   * @var PDO
   */
  protected $dbh;


  /**
   * Nombre de la base de datos
   * @var string
   */
  private $dbName;


  /**
   * CONSTANTES
   */

  const ERR_REPETIDO = 'Ya este registro existe en la base de datos.';


  final public static function setLogger(LoggerInterface $logger) {
    self::$logger = $logger;
  }


  final public static function setCache(CacheInterface $cache) {
    self::$cache = $cache;
  }


  final public static function setReadOnlyMode(bool $mode) {
    self::$isReadOnlyMode = $mode;
  }


  /**
   * Singleton. Todos las instancias singleton de esta clase y clase heredadas, usan PDOSingleton
   * @return static
   */
  final public static function getInstance(): static {
    $class = get_called_class();
    if (!isset(self::$instances[$class])) {
      self::$instances[$class] = new static();
    }

    return self::$instances[$class];
  }


  /**
   * CONSTRUCTOR
   * @param PDO $pdo (optional)
   */
  public function __construct(PDO $pdo = NULL) {
    if (DbHandler::$logger === NULL) {
      DbHandler::$logger = new Logger();
    }

    if (DbHandler::$cache === NULL) {
      DbHandler::$cache = new Cache();
    }

    self::$logger::log();
    $this->setDBH($pdo);
  }


  private function setDBH(?PDO $pdo = NULL) {
    self::$logger::log();

    // Si no se provee una instancia de PDO, usamos PDOSingleton
    $this->dbh = $pdo ?? PDOSingleton::get();
  }


  private function resetDBH(?PDO $pdo = NULL) {
    self::$logger::log();

    PDOSingleton::destroy();

    // @todo 1 hay que actualizar el objeto PDO en cada instancia
    // foreach (self::$instances as $instance) {
    //   $instance->setDBH($pdo);
    // }

    $this->setDBH($pdo);
  }


  final public function disableCommits() {
    self::$logger::log();
    DbTransaction::setCommitsModeOff();
  }


  final public function enableCommits() {
    self::$logger::log();
    DbTransaction::setCommitsModeOn();
  }


  final public function transactionManager($action) {
    self::$logger::log();

    switch($action) {
      case 'begin':
        if ($this->dbh->inTransaction()) {
          self::$logger::log('>>> Ya hay una transacción iniciada.');
          return NULL;
        }
        return $this->dbh->beginTransaction();

      case 'commit':
        if (!$this->dbh->inTransaction()) {
          self::$logger::log('>>> No hay ninguna transacción iniciada.');
          return NULL;
        }
        if (DbTransaction::getCommitsMode() === PDOCommitModeEnum::OFF) {
          self::$logger::log('>>> Los commits están desabilitados.');
          return NULL;
        }
        return $this->dbh->commit();

      case 'rollback':
        if (!$this->dbh->inTransaction()) {
          self::$logger::log('>>> No hay ninguna transacción iniciada.');
          return NULL;
        }
        return $this->dbh->rollBack();

      case 'check':
        return $this->dbh->inTransaction();

      default:
        throw new Exception('Acción desconocida.');
    }
  }


  /**
   * Función de ayuda para verificar la existencia de una tabla en la base de datos
   *
   * @param str $table Nombre de la tabla.
   *
   * @return bool T:Sí existe | F: No existe
   * @throws Exception
   */
  public function checkIfTableExists($table) {
    try {
      self::$logger::log();

      // Creamos la consulta y la ejecutamos
      $query = "SHOW TABLES LIKE :table;";
      $sth = $this->dbh->prepare($query);
      $sth->bindValue(':table', $table);

      $sth->execute();

      return ($sth->rowCount() === 1);
    }

    catch (Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función de ayuda para obtener un listado con las columnas de una tabla
   * @param string $table Nombre de la tabla.
   *
   * @return stdClass Objeto[] con las propiedades 'all', 'non', 'key' e 'inc'
   * @throws Exception
   */
  public function getTableColumns(string $table): stdClass {
    try {
      self::$logger::log();

      $inCache = self::$cache->get(__METHOD__, func_get_args());
      if (!empty($inCache)) { return $inCache; }

      // Obtenemos el nombre de la base de datos
      $this->dbName = empty($this->dbName)
                      ? $this->dbh->query('SELECT database();')->fetchColumn()
                      : $this->dbName;

      // Creamos la consulta y la ejecutamos
      $query = "
        SELECT
            COLUMN_NAME,
            COLUMN_KEY,
            EXTRA
        FROM
            information_schema.COLUMNS
        WHERE
            TABLE_SCHEMA   = :db
            AND TABLE_NAME = :table;
      ";

      // Pegamos variables y ejecutamos consulta
      $sth = $this->dbh->prepare($query);
      self::$logger::log($query, "QUERY");
      $sth->bindValue(':db', $this->dbName);
      self::$logger::log($this->dbName, ":db");
      $sth->bindValue(':table', $table);
      self::$logger::log($table, ":table");

      $sth->execute();

      $dbColumns = $sth->fetchAll(PDO::FETCH_OBJ);
      self::$logger::log($dbColumns, "RESULTS");

      $columns = new stdClass;
      $columns->all = [];
      $columns->non = [];
      $columns->key = [];
      $columns->inc = [];

      foreach($dbColumns as $row) {
        $columns->all[] = $row->COLUMN_NAME;
        if ($row->COLUMN_KEY === 'PRI') {
          $columns->key[] = $row->COLUMN_NAME;
          if ($row->EXTRA === 'auto_increment') {
            $columns->inc[] = $row->COLUMN_NAME;
          }
        } else {
          $columns->non[] = $row->COLUMN_NAME;
        }
      }

      self::$cache->set(__METHOD__, func_get_args(), $columns);

      self::$logger::log($columns, "{$table} >>>");
      return $columns;
    }

    catch(Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función de ayuda para verificar la existencia de un valor 'z'
   * en una columna 'y' de una tabla 'x'
   *
   * @param string  $tab    Nombre de la tabla
   * @param arr|str $keyVal Matriz con formato: 'columna => valor', o nombre de la columna
   * @param str|int $val    Valor a buscar
   *
   * @return bool
   */
  public function checkIfExists(string $tab, $keyVal, $val = '') {
    try {
      self::$logger::log();

      $baseQuery = "SELECT * FROM $tab WHERE ";

      if(is_array($keyVal)) {
        $where = [];
        foreach($keyVal as $k => $v) { $where[] = "$k LIKE :$k"; }

        $query = $baseQuery . implode(' AND ', $where) . " LIMIT 1;";
        $sth = $this->dbh->prepare($query);

        foreach($keyVal as $k => $v) {
          $sth->bindValue(":$k", $v);
        }
      }

      else {
        $key = $keyVal;
        $query = $baseQuery . "$key LIKE :val LIMIT 1;";
        $sth = $this->dbh->prepare($query);
        $sth->bindValue(':val', $val);
      }

      $sth->execute();
      $exists = $sth->rowCount() > 0;

      self::$logger::log($exists, '>>>');
      return $exists;
    }

    catch (Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función para obtener información de la base de datos
   *
   * @param str|arr|obj $table       Nombre de la tabla o array/objeto con los parámetros para la función.
   * @param str|arr     $columns     Nombre de las columnas a buscar. DEFAULT = *
   * @param str|arr     $where       Array con las condiciones de búsqueda. DEFAULT = 1
   * @param int         $limit       Cantidad de resultados a devolver. DEFALT = 0 (todos)
   * @param array       $sort        Array con las columnas para ordenar los resultados.
   * @param bool|int    $fetch_style Formato de respuesta a devolver. T:object, F:Array.
   *                                 DEFAULT = TRUE. Se puede usar también PDO::FETCH_*
   *
   * @return array      Array con datos encontrados en la base de datos.
   */
  public function getDataArr(
    $table,
    $columns = '*',
    $where = '',
    $limit = 0,
    $sort = [],
    $fetch_style = TRUE
  ) {
    //@todo 3 Renombrar a getData() y hacer refactor
    try {
      self::$logger::log();

      $bindBuilder = new PDOBindBuilder();

      $group_by = '';
      $having = '';

      // Si en lugar de un str, recibimos un array como nombre de tabla...
      if (is_array($table) || is_object($table)) {
        if(is_object($table)) { $table = (array)$table; }
        $defaults = [
          'columns' => '*',
          'where' => '',
          'group_by' => '',
          'having' => '',
          'limit' => 0,
          'sort' => [],
          'fetch_style' => TRUE,
        ];

        // Obtenemos los parámetros del array/object
        foreach($defaults as $defKey => $defVal) {
          $$defKey = isset($table[$defKey]) ? $table[$defKey] : $defVal;
        }

        $table = $table['table'];
      }

      // Configuramos las columnas a obtener
      $cols = $this->parseColumns($columns);

      // Configuramos las cláusulas
      if (is_array($where)) {
        $where = $this->generateWhere($bindBuilder, $where);
      }
      $_where = ($where !== '') ? "WHERE $where" : '';

      // Configuramos el agrupamiento
      $group_by = is_array($group_by)
        ? implode(', ', $group_by)
        : $group_by;
      $_group_by = ($group_by !== '') ? "GROUP BY `$group_by`" : '';

      // Configuramos el having
      if (is_array($having)) {
        $having = $this->generateWhere($bindBuilder, $having);
      }
      $_having = ($_group_by !== '' && $having !== '')
        ? "HAVING $having"
        : '';

      // Configuramos el orden
      if(is_array($sort)) {
        $orderArray = [];
        foreach($sort as $sortCol => $sortOrder) {
          $orderArray[] = "$sortCol $sortOrder";
        }
        $sort = implode(', ', $orderArray);
      }
      $_order = ($sort !== '') ? "ORDER BY $sort" : '';

      // Configuramos el límite de búsqueda
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

      // Creamos la consulta y la ejecutamos
      $query = "SELECT $cols FROM $table $queryEnd;";
      $sth = $this->dbh->prepare($query);
      self::$logger::log([$query, $bindBuilder->getValues()], '$query');
      self::$logger::log($bindBuilder->debugQuery($query), 'Query');

      // Configuramos los parámetros
      $bindBuilder->bindToStatement($sth);

      $sth->execute();

      // Configuramos el formato de respuesta
      if(is_bool($fetch_style)) {
        $formato = $fetch_style ? PDO::FETCH_OBJ : PDO::FETCH_ASSOC;
      } else {
        $formato = $fetch_style;
      }

      self::$logger::log($sth->rowCount(), 'Filas');

      return $sth->fetchAll($formato);
    }

    catch(Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función para obtener información de la base de datos
   *
   * @param str|arr|obj $table       Nombre de la tabla o array/objeto con los parámetros para la función.
   * @param str|arr     $columns     Nombre de las columnas a buscar. DEFAULT = *
   * @param str|arr     $where       Array con las condiciones de búsqueda. DEFAULT = 1
   * @param int         $limit       Cantidad de resultados a devolver. DEFALT = 0 (todos)
   * @param array       $sort        Array con las columnas para ordenar los resultados
   * @param bool|int    $fetch_style Formato de respuesta a devolver. T:object, F:Array.
   *                                 DEFAULT = TRUE. Se puede usar también PDO::FETCH_*
   *
   * @return array|null Array con datos encontrados en la base de datos. Si no existen, se devuelve NULL
   */
  public function getData($table, $columns = '*', $where = '', $limit = 0, $sort = [], $fetch_style = TRUE) {
    self::$logger::log();
    self::$logger::log(
      NULL,
      '##DEPRECATION NOTICE## Rename method to getDataOrNull()',
      Logger::WARNING,
    );

    $getDataArr = $this->getDataArr(
      $table,
      $columns,
      $where,
      $limit,
      $sort,
      $fetch_style
    );

    return (count($getDataArr) > 0)
      ? $getDataArr
      : NULL;
  }


      /**
   * Función para obtener información de la base de datos
   *
   * @param str|arr|obj $table       Nombre de la tabla o array/objeto con los parámetros para la función.
   * @param str|arr     $columns     Nombre de las columnas a buscar. DEFAULT = *
   * @param str|arr     $where       Array con las condiciones de búsqueda. DEFAULT = 1
   * @param int         $limit       Cantidad de resultados a devolver. DEFALT = 0 (todos)
   * @param arr         $sort        Array con los campos para ordenar los resultados
   * @param bool|int    $fetch_style Formato de respuesta a devolver. T:object, F:Array.
   *                                 DEFAULT = TRUE. Se puede usar también PDO::FETCH_*
   *
   * @return array|null Array con datos encontrados en la base de datos. Si no existen, se devuelve NULL
   */
  public function getDataOrNull($table, $columns = '*', $where = '', $limit = 0, $sort = [], $fetch_style = TRUE) {
    self::$logger::log();

    $getDataArr = $this->getDataArr(
      $table,
      $columns,
      $where,
      $limit,
      $sort,
      $fetch_style
    );

    return $getDataArr ? $getDataArr : NULL;
  }


  /**
   * Devuelve una fila única de una tabla de la base de datos
   * @param mixed $table   Nombre de la tabla o array con los parámetros
   * @param array $where   (opcional) Array con los parámetros de la cláusula WHERE
   * @param mixed $columns (opcional) Nombre de la columna o columnas a buscar
   *
   * @return mixed Objeto con los datos de la fila encontrada o NULL en caso de no encontrar resultados
   * @throws Exception
   */
  public function getDataRow($table, array $where = [], $columns = '*') {
    self::$logger::log();

    $dbData = (is_array($table) || is_object($table))
      ? $this->getDataArr($table)
      : $this->getDataArr($table, $columns, $where);

    $dbDataCount = count($dbData);

    if ($dbDataCount > 1) {
      self::$logger::log(NULL, '>>> Este query genera más de 1 resultado.', Logger::WARNING);
    }

    return ($dbDataCount === 0)
      ? NULL
      : $dbData[0];
  }


  /**
   * Devuelve un array con los valores de una columna
   *
   * @param string $table
   * @param string $column
   * @param ?array $where
   *
   * @return array
   */
  public function getDataColumn(
    string $table,
    string $column,
    array $where = [],
  ): array {
    self::$logger::log();

    $query = [
      'table' => $table,
      'columns' => $column,
      'where' => $where,
      'fetch_style' => PDO::FETCH_COLUMN,
    ];

    return self::getDataArr($query);
  }


  /**
   * Devuelve un valor único de una columna de una tabla de la base de datos
   * @param mixed  $table  Nombre de la tabla o array con los parámetros
   * @param string $column (opcional) Nombre de la columna
   * @param array  $where  (opcional) Array con los parámetros de la cláusula WHERE
   * @param array  $sort   (opcional) Array con los campos para ordenar los resultados
   *
   * @return string
   * @throws Exception
   */
  public function getValue(
    $table,
    $column = NULL,
    $where = NULL,
    $sort = [],
  ) {
    self::$logger::log();

    $tab = is_array($table) ? $table['table'] : $table;
    $col = is_array($table) ? $table['columns'] : $column;
    $whr = is_array($table) ? $table['where'] : $where;
    $srt = is_array($table)
      ? (
          empty($table['sort'])
            ? []
            : $table['sort']
        )
      : $sort;

    if (
      !is_string($col)
      || strpos($col, ' ') !== FALSE
      || strpos($col, ',') !== FALSE
    ) {
      $col_err = 'El parámetro columna sólo acepta cadenas de texto sin '
        . 'espacios ni comas, ya que solo se puede especificar una columna.';
      throw new Exception($col_err, 500);
    }

    $query = [
      'table' => $tab,
      'columns' => $col,
      'where' => $whr,
      'sort' => $srt,
      'limit' => 1,
      'fetch_style' => PDO::FETCH_COLUMN
    ];
    $dbData = $this->getDataArr($query);

    self::$logger::log($dbData, '>>>');
    return $dbData ? $dbData[0] : NULL;
  }


  /**
   * Método para obtener valores únicos de una columna de una tabla
   * @param string $table  Nombre de la tabla
   * @param string $column Nombre de la columna
   *
   * @return mixed Array with distinct values or NULL
   * @throws Exception
   */
  public function getDistincts($table, $column) {
    try {
      self::$logger::log();

      // Validamos los parámetros
      $params = [$table, $column];
      foreach ($params as $param) {
        if (
          !is_string($param)
          || strpos($param, ' ') !== FALSE
          || strpos($param, ',') !== FALSE
        ) {
          $err = 'Este método solo acepta cadenas de texto, sin comas ni '
            . 'espacios como parámetros.';
          throw new Exception($err, 500);
        }
      }

      // Creamos la consulta y la ejecutamos
      $query = "SELECT DISTINCT(`{$column}`) FROM {$table};";
      self::$logger::log($query, '$query');
      $sth = $this->dbh->prepare($query);
      $sth->execute();

      self::$logger::log($sth->rowCount(), 'Filas');
      return ($sth->rowCount() > 0)
              ? $sth->fetchAll(PDO::FETCH_COLUMN)
              : NULL;
    }

    catch(Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función para obtener el valor mínimo o máximo en una columna de una tabla de la base de datos
   * @param str   $table    Nombre de la tabla.
   * @param str   $operator Operación a efectuvar sobre la columna 'min'|'max'
   * @param str   $column   Nombre de la columna de la que vamos a solicitar el valor máximo
   * @param array $where    Condición WHERE para la consulta
   *
   * @return int Valor de la columna
   * @throws Exception
   */
  public function getCalculatedColumnValue(
    string $table,
    string $operator,
    string $column,
    array  $where = [],
  ) {
    try {
      self::$logger::log();

      $operatorToFunc = [
        'min' => 'MIN',
        'max' => 'MAX',
        'sum' => 'SUM',
        'count' => 'COUNT',
      ];

      // Validamos el parámetro $operator
      if (!array_key_exists($operator, $operatorToFunc)) {
        throw new Exception(
          "El parámetro 'operator' debe ser "
            . implode(', ', array_keys($operatorToFunc))
        );
      }

      $sqlFunction = $operatorToFunc[$operator];

      // Ajustamos cláusula WHERE
      $bindBuilder = new PDOBindBuilder();
      $_where = $this->generateWhere($bindBuilder, $where);
      $queryEnd = empty($_where) ? '' : " WHERE {$_where}";

      // Creamos la consulta y la ejecutamos
      $query = "SELECT $sqlFunction($column) FROM $table$queryEnd;";
      $sth = $this->dbh->prepare($query);
      self::$logger::log([$query, $bindBuilder->getValues()], '$query');

      // Configuramos los parámetros
      $bindBuilder->bindToStatement($sth);

      self::$logger::log($bindBuilder->debugQuery($query), 'Final query');
      $sth->execute();

      self::$logger::log($sth->rowCount(), 'Filas');

      $dbData = $sth->fetchAll(PDO::FETCH_COLUMN);

      self::$logger::log($dbData, '>>>');

      return $dbData ? $dbData[0] : NULL;
    }

    catch(Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función para ejecutar un 'query'
   *
   * @param string         $query
   * @param PDOBindBuilder $bindBuilder
   *
   * @return array|int
   */
  public function executeQuery(string $query, PDOBindBuilder $bindBuilder) {
    try {
      self::$logger::log();

      $this->checkIfInReadOnlyMode();

      $queryType = strtoupper(substr(trim($query), 0, 6));
      $allowedTypes = ['INSERT', 'UPDATE', 'DELETE', 'SELECT'];

      if (!in_array($queryType, $allowedTypes)) {
        throw new Exception('Tipo de query no permitido.');
      }

      self::$logger::log([$query, $bindBuilder->getValues()], '$query');
      $sth = $this->dbh->prepare($query);

      // Configuramos los parámetros
      $bindBuilder->bindToStatement($sth);

      self::$logger::log($bindBuilder->debugQuery($query), 'Final query');
      $sth->execute();

      self::$logger::log($sth->rowCount(), 'Líneas afectadas:');

      return ($queryType === 'SELECT')
        ? $sth->fetchAll(PDO::FETCH_OBJ)
        : $sth->rowCount();
    }

    catch(Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función para ejecutar un 'stored procedure' de la base de datos
   *
   * @param str     $procName Nombre del procedimiento a ejecutar.
   * @param str|arr $params   Parámetros para el procedimiento en el orden
   *                          correspondiente. DEFAULT = ''
   * @param bool    $fetchObj Formato de respuesta a devolver. T:object,
   *                          F:Array. DEFAULT = TRUE
   *
   * @return str|obj Datos encontrados en la base de datos. Si es un valor
   *                 único se devuelve como string.
   */
  public function callProcedure($procName, $params = '', $fetchObj = TRUE) {
    try {
      self::$logger::log();

      // Configuramos los parámetros
      if(is_array($params)) {
        $keys = [];
        foreach($params as $key => $value) {
          $keys[] = ":$key";
        }
        $procParams = implode(', ', $keys);
      } elseif(is_string($params)) {
        $procParams = "'$params'";
      }

      $query = "CALL $procName($procParams);";
      self::$logger::log($query, '$query');

      $sth = $this->dbh->prepare($query);

      //Pegamos parámetros
      if(is_array($params)) {
        foreach($params as $key => $value) {
          $sth->bindValue(":{$key}", $value);
          self::$logger::log($value, ":{$key}");
        }
      }

      $sth->execute();
      self::$logger::log($sth->rowCount(), 'Resultados');

      // Configuramos el formato de respuesta
      $formato = $fetchObj ? PDO::FETCH_OBJ : PDO::FETCH_ASSOC;

      return ($sth->rowCount() > 0)
        ? $sth->fetchAll($formato)
        : NULL;
    }

    catch(Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función para crear nuevos registros en la base de datos.
   * @param string $table    Nombre de la tabla en la que se creará el registro.
   * @param mixed  $postData Información sobre el registro a crear.
   *
   * @throws Exception
   */
  public function setData(string $table, $postData) {
    try{
      self::$logger::log();

      $bindBuilder = new PDOBindBuilder();

      $this->checkIfInReadOnlyMode();

      // Nos aseguramos que la información esté almacenada en un ARRAY
      $data = is_object($postData) ? (array)$postData : $postData;

      // Obtenemos las columnas de la tabla (obj con 4 props: non, all)
      $dbTableCols = $this->getTableColumns($table);

      // Creamos un array que se usará para generar el query con todas las
      // columnas que NO SEAN claves primarias, las claves primarias se
      // asignarán más adelante.
      $dataCols = $dbTableCols->non;

      // Verificamos si la clave primaria no es autogenerada y es provista a
      // través de POST
      $is_auto_incremented_key = count($dbTableCols->inc) > 0;

      // Si la clave primaria no es autogenerada nos aseguramos que no esté repetida
      $keyCols = $dbTableCols->key;
      if (!$is_auto_incremented_key) {
        $keyVal = [];
        foreach ($keyCols as $keyColName) {
          // Si se envían datos para un campo que es clave primaria
          if (isset($data[$keyColName])) {
            $keyVal[$keyColName] = $data[$keyColName];
            // Agregamos el campo al listado de columnas $dataCols
            $dataCols[] = $keyColName;
          }
        }

        if (!empty($keyVal)) {
          if ($this->checkIfExists($table, $keyVal)) {
            throw new Exception(self::ERR_REPETIDO, 409);
          }
        }
      }

      // Eliminamos columnas que no tengan valor y verificamos el tipo de dato
      // de las que sí tienen
      $queryColumns = [];
      $queryParams = [];
      foreach ($dataCols as $col) {
        if (!isset($data[$col])) continue;

        $this->checkValue($data[$col], $col);
        $queryColumns[] = "`$col`";
        $queryParams[] = $bindBuilder->addValueWithPrefix($data[$col], $col);
      }

      // Generamos el query dinámico
      $qColumns = implode(', ', $queryColumns);
      $qParams = implode(', ', $queryParams);
      $query = "INSERT INTO {$table} ({$qColumns}) VALUES ({$qParams});";

      $sth = $this->dbh->prepare($query);
      self::$logger::log($bindBuilder->debugQuery($query), 'Query');

      // Hacemos bind de los parámetros
      $bindBuilder->bindToStatement($sth);

      $sth->execute();
      self::$logger::log('>>> OK');
    }

    catch (Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función para crear nuevos registros en la base de datos en una sola
   * operación.
   * INSERT INTO
   *    table (col1, col2, col3)
   * VALUES
   *    (col1_data11, col2_data12, col3_data13),
   *    (col1_data21, col2_data22, col3_data23),
   *    ...
   *    (col1_datan1, col2_datan2, col3_datan3);
   *
   * @param string $table    Nombre de la tabla en la que se creará el registro.
   * @param array  $postData Información sobre el registro a crear.
   *
   * @return stdClass Objeto con mensaje de confirmación
   * @throws Exception
   */
  public function setDataMulti(string $table, array $postData) {
    try{
      self::$logger::log();

      $this->checkIfInReadOnlyMode();

      // Nos aseguramos que $postData sea un array y contenga información
      if (!is_array($postData) || empty($postData)) {
        self::$logger::log('>>> NO DATA');
        return 'NO DATA';
      }

      // Nos aseguramos que la información esté almacenada en un ARRAY de ARRAYS
      $data = [];
      foreach ($postData as $dataRow) {
        $data[] = is_object($dataRow) ? (array)$dataRow : $dataRow;
      }

      // Obtenemos las columnas de la tabla
      $dbTblColumns = $this->getTableColumns($table);
      $dbTableCols = $dbTblColumns->all;

      // Eliminamos columnas que no tengan valor en el primer array de $data
      // (primer registro) --> $data[0]
      $queryCols = array_keys($data[0]);
      $queryColsQuoted = [];
      foreach ($queryCols as $col) {
        if(!in_array($col, $dbTableCols)) {
          throw new Exception("Esta tabla no contiene ninguna columna '$col'", 500);
        }
        $queryColsQuoted[] = "`{$col}`";
      }

      $bindBuilder = new PDOBindBuilder();

      // Generamos la sección VALUES (), (), (), del query
      $queryRows = [];
      foreach ($data as $row) {
        $queryRowArr = [];

        foreach ($row as $_col => $_val) {
          $this->checkValue($_val, $_col);
          $queryRowArr[] = $bindBuilder->addValueWithPrefix($_val, $_col);
        }

        $queryRow = '(' . implode(', ', $queryRowArr) . ')';
        $queryRows[] = $queryRow;
      }

      // Generamos el QUERY
      $queryColsList = implode(', ', $queryColsQuoted);
      $queryValuesList = implode(', ', $queryRows);

      $query = "INSERT INTO {$table} ({$queryColsList}) VALUES {$queryValuesList};";
      self::$logger::log($query, '$query');
      self::$logger::log($bindBuilder->debugQuery($query), 'Query');
      $sth = $this->dbh->prepare($query);

      // Hacemos bind de los parámetros
      $bindBuilder->bindToStatement($sth);

      // Ejecutamos el query
      $sth->execute();
      self::$logger::log('>>> OK');

      // Devolvemos mensaje de confirmación
      $insertCount = count($data);
      return "Se introdujeron $insertCount registros en la base de datos.";
    }

    catch (Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función para actualizar registros existentes en la base de datos.
   *
   * @param string         $table      Nombre de la tabla en la que se creará el registro.
   * @param array|stdClass $putData    Información sobre el registro a actualizar.
   * @param boolean        $ignoreNull (default: TRUE). Flag para ignorar valores con valor NULL.
   *
   * @return obj Objeto con mensaje de confirmación
   * @throws Exception
   */
  public function updateData($table, $putData, bool $ignoreNull = TRUE) {
    try {
      self::$logger::log();

      $this->checkIfInReadOnlyMode();

      $data = is_object($putData) ? (array)$putData : $putData;

      $cols0 = $this->getTableColumns($table);
      $keyColumn = $cols0->key;

      $bindBuilder = new PDOBindBuilder();

      // Buscamos en la base de datos el valor actual de las columnas de la
      // fila a actualizar
      $where = [];
      $whereQuerySet = [];
      foreach ($keyColumn as $kCol) {
        if (!isset($data[$kCol])) {
          $noKeyMsg = "No se encontró un valor clave para la columna '$kCol' "
            . "en el registro a actualizar.";
          throw new Exception($noKeyMsg, 400);
        }

        $keyValue = isset($data['oldKey'])
          ? $data['oldKey'][$kCol]
          : $data[$kCol];

        $where[$kCol] = $keyValue;
        $kValuePlaceholder = $bindBuilder->addValueWithPrefix($keyValue, $kCol);
        $whereQuerySet[] = "{$kCol} = {$kValuePlaceholder}";
      }

      $dbData = (array)$this->getDataRow($table, $where);

      // Verificamos qué columnas se van a actualizar (tienen cambios) y
      // generamos la sección 'SET col = val,..' de la consulta UPDATE
      $updateQuerySet = [];
      foreach ($cols0->all as $col) {
        // Si la columna es una clave y no se ha cambiado su valor original,
        // la ignoramos
        if (in_array($col, $keyColumn) && !isset($data['oldKey'][$col])) {
          continue;
        }

        // Si el valor de la columna no ha cambiado, la ignoramos
        if (
          array_key_exists($col, $data)
          && $dbData[$col] === $data[$col]
        ) {
          continue;
        }

        /**
         * Datos a cambiar
         */
        if (
          // Existen datos para la columna...
          isset($data[$col])
          // ó...
          || (
            // Está inactivo el flag $ignoreNull (se mantienen los valores NULL)...
            !$ignoreNull
            // ...y la clave esté presente en $data aunque sea NULL
            && array_key_exists($col, $data)
          )
        ) {
          $this->checkValue($data[$col], $col);
          $placeholder = $bindBuilder->addValueWithPrefix($data[$col], $col);
          $updateQuerySet[] = "`{$col}` = {$placeholder}";
          continue;
        }
      }

      // Sólo si hay columnas por actualizar...
      if (count($updateQuerySet) === 0) {
        throw new Exception('No cambió ningún dato.', 400);
      }

      // Nos aseguramos que exista un valor para la cláusula WHERE
      if (count($whereQuerySet) === 0) {
        throw new Exception('No hay ningún valor para la cláusula WHERE.', 500);
      }

      $updateQuerySetString = implode(', ', $updateQuerySet);
      $whereQuerySetString = implode(' AND ', $whereQuerySet);

      $query = ''
        . "UPDATE $table "
        . "SET $updateQuerySetString "
        . "WHERE ($whereQuerySetString);";
      $sth = $this->dbh->prepare($query);

      // Hacemos bind de los parámetros
      $bindBuilder->bindToStatement($sth);

      self::$logger::log($query, '$query');
      self::$logger::log($bindBuilder->debugQuery($query), 'Query');

      // Ejecutamos el query
      $sth->execute();
      self::$logger::log('>>> OK');
    }

    catch (Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función para actualizar registros existentes en la base de datos si los
   * datos ya existen o crear un registro nuevo en caso de que no
   * @param string $table      Nombre de la tabla
   * @param array  $dataArr    Array con los datos en formato column => value
   * @param bool   $ignoreNull (default: TRUE). Flag para ignorar valores con valor NULL.
   *
   * @throws Exception
   */
  public function setUpdateData($table, array $dataArr, bool $ignoreNull = TRUE) {
    self::$logger::log();

    $this->checkIfInReadOnlyMode();

    $checkDataArr = [];

    // Verificamos si los datos ya existen
    $tblCols = $this->getTableColumns($table);
    foreach ($tblCols->key as $keyColName) {
      if (isset($dataArr[$keyColName])) {
        $checkDataArr[$keyColName] = isset($dataArr['oldKey'])
          ? $dataArr['oldKey'][$keyColName]
          : $dataArr[$keyColName];
      }
    }

    // Si ya existe un registro con el valor de las 'key columns'
    // suministradas, actualizamos datos...
    if (!empty($checkDataArr) && $this->checkIfExists($table, $checkDataArr)) {
      self::$logger::log(">>> Ya existe, actualizando registro");
      $this->updateData($table, $dataArr, $ignoreNull);
    }

    // ..si no existe, registramos nuevos datos
    else {
      self::$logger::log(">>> Creando registro");
      $this->setData($table, $dataArr);
    }
  }


  /**
   * Función para eliminar registros existentes en la base de datos.
   * @param string $table  Nombre de la tabla en la que se eliminarán registros.
   * @param mixed  $idData Si es una matriz con elementos: 'key_column' => 'value2filter', se
   *                       eliminarán los registros que correspondan con estas 'key_column's
   *                       siempre y cuando sean claves primarias.
   *                       Si es un valor simple, se eliminarán los registros cuya clave primaria
   *                       corresponda con el valor provisto. (Se devolverá error en caso de que la
   *                       tabla tengas varias claves primarias)
   * @return int Cantidad de registros eliminados
   * @throws Exception
   */
  public function deleteData($table, $idData) {
    try{
      self::$logger::log();

      $this->checkIfInReadOnlyMode();

      $bindBuilder = new PDOBindBuilder();

      $baseQuery = "DELETE FROM `$table` WHERE ";

      // En caso de que '$idData' sea una matriz con elementos: 'key_column' => 'value2filter'
      if(is_array($idData)) {
        // Generamos WHERE
        $where = [];
        foreach($idData as $col => $data) {
          $placeholder = $bindBuilder->addValueWithPrefix($data, $col);
          $where[] = "`{$col}` = {$placeholder}";
        }

        $query = $baseQuery . implode(' AND ', $where);
      }

      // Si '$idData' es un valor simple (bool, string, int, float, etc)
      else {
        $tableColumns = $this->getTableColumns($table);
        if(count($tableColumns->key) > 1) {
          $errorMsg = "No se puede borrar el registro de la tabla '$table' "
                      . "porque tiene más de una clave primaria";
          throw new Exception($errorMsg, 500);
        }

        $keyColumn = $tableColumns->key[0];
        $placeholder = $bindBuilder->addValueWithPrefix($idData, $keyColumn);
        $query = $baseQuery . "`{$keyColumn}` = {$placeholder}";
      }

      $sth = $this->dbh->prepare($query);
      self::$logger::log($query, '$query');
      self::$logger::log($bindBuilder->debugQuery($query), 'Query');

      // Hacemos bind de los parámetros
      $bindBuilder->bindToStatement($sth);

      // EJECUTAMOS EL QUERY Y GENERAMOS RESPUESTA
      $sth->execute();
      $eliminados = $sth->rowCount();
      self::$logger::log(">>> OK --({$eliminados} registros borrados)");

      // Devolvemos mensaje de confirmación
      return $eliminados;
    }

    catch (Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función de ayuda para obtener información sobre una tabla de la base de datos.
   * @param string          $table Nombre de la tabla.
   * @param string|string[] $info  Columnas a obtener de la base de datos. Default: *
   *
   * @return stdClass       Valor de autoincremento
   * @throws Exception
   */
  public function getSchemaInfo($table, $info = '*') {
    try {
      self::$logger::log();

      $columns = is_string($info)
        ? $info
        : implode(', ', $info);

      // Creamos la consulta y la ejecutamos
      $query = ""
        . "SELECT {$columns} FROM INFORMATION_SCHEMA.TABLES "
        . "WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table;";

      $sth = $this->dbh->prepare($query);
      $sth->bindValue(':db', PDOSingleton::getDBName());
      $sth->bindValue(':table', $table);

      $sth->execute();

      // Si se solicitó más de una columna
      if(count(explode(',', $columns)) > 1 || $columns === '*') {
        $dataArr = $sth->fetchAll(PDO::FETCH_OBJ);
        return $dataArr ? $dataArr[0] : NULL;
      }

      // Si se solicitó información específica
      else {
        return $sth->fetchColumn() ?: NULL;
      }
    }

    catch(Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Función para obtener el total de filas de una tabla de la base de datos
   * @param string $table Nombre de la tabla
   * @return int          Conteo de filas
   * @throws Exception
   */
  public function getRowCount($table) {
    try {
      self::$logger::log();

      // Creamos la consulta y la ejecutamos
      $query = "SELECT COUNT(*) FROM $table";
      $sth = $this->dbh->prepare($query);

      $sth->execute();
      return $sth->fetchColumn();
    }

    catch(Exception $e) {
      if (isset($query)) { self::$logger::log($query, '$query'); }
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * Método de ayuda para devolver el ID del último registro insertado en la
   * base de datos
   *
   * @return mixed ID del último registro insertado
   * @throws Exception
   */
  public function getLastInsertId() {
    self::$logger::log();

    $lastInsertId = $this->dbh->lastInsertId();
    self::$logger::log(">>> {$lastInsertId}");

    return $lastInsertId;
  }


  private function checkValue($value, $column = NULL) {
    self::$logger::log();

    // Verificamos tipos permitidos
    if (
      is_string($value)
      || is_numeric($value)
      || is_null($value)
      || is_bool($value)
    ) {
      return;
    }

    $valType = gettype($value);
    $e = 'El valor a escribir en la base de datos debe ser una cadena '
          . 'de caracteres o un número. El valor ingresado ';
    $e .= isset($column)
      ? "para '{$column}' "
      : '';
    $e .= "es de tipo '{$valType}'.";
    throw new Exception($e, 500);
  }


  public function checkIfInReadOnlyMode() {
    self::$logger::log();

    if (self::$isReadOnlyMode) {
      $msg = 'En este momento el sistema solo está habilitado para consultas.';
      throw new Exception($msg, 503);
    }
  }


  /**
   * Generar un string WHERE para una consulta SQL a partir de un array
   * @param PDOBindBuilder $bindBuilder Objeto para generar los binds de PDO
   * @param array          $where       Array con los parámetros de la consulta
   *
   * @return string String con la condición WHERE para la consulta
   */
  public function generateWhere(
    PDOBindBuilder $bindBuilder,
    array $where,
  ): string {
    try {
      self::$logger::log();

      $whereArr = [];
      foreach($where as $key => $val) {
        $_key = $this->quoteWrap($key);

        // Varias opciones para un sólo campo ==> OR
        if (is_array($val) && !array_key_exists('operator', $val)) {
          $whereArr[] = $this->generateWhereComplex($bindBuilder, $key, $val);
          continue;
        }

        // Única opción con operador
        if (is_array($val)) {
          $operator = $this->validateOperator($val['operator']);
          $value = is_null($val['value'])
            ? 'NULL'
            : $bindBuilder->addValue($val['value']);
          $whereArr[] = "{$_key} {$operator} {$value}";
          continue;
        }

        // Única opción simple
        $whereArr[] = is_null($val)
          ? "{$_key} IS NULL"
          : "{$_key} LIKE " . $bindBuilder->addValue($val);
      }

      return implode(' AND ', $whereArr);
    }

    catch(Exception $e) {
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  public function generateWhereComplex(
    PDOBindBuilder $bindBuilder,
    string $key,
    array $valueArr,
  ): string {
    self::$logger::log();

    $_key = $this->quoteWrap($key);

    $whereArr = [];

    $orClause = [];
    $andClause = [];

    foreach($valueArr as $valValue) {
      // Si el elemento no es otro array..
      if (!is_array($valValue)) {
        $orClause[] = is_null($valValue)
          ? "{$_key} IS NULL"
          : "{$_key} LIKE " . $bindBuilder->addValue($valValue);

        continue;
      }

      // ..si es otro array
      $operator = $this->validateOperator($valValue['operator']);
      $value = $valValue['value'];

      if (!is_null($value)) {
        $placeholderValue = $bindBuilder->addValue($value);
        $clause = "{$_key} {$operator} {$placeholderValue}";
        // Si se provee la propiedad 'and', se hará un query así:
        // (valor > 3 AND valor < 10)
        if (isset($valValue['and']) && $valValue['and']) {
          $andClause[] = $clause;
        }
        // Si NO se provee la propiedad 'and':
        // (valor > 3 OR valor < 10)
        else {
          $orClause[] = $clause;
        }
      }
    }

    if (count($orClause) > 0) {
      $orClauseStr = implode(' OR ', $orClause);
      $whereArr[] = "({$orClauseStr})";
    }

    if (count($andClause) > 0) {
      $andClauseStr = implode(' AND ', $andClause);
      $whereArr[] = "({$andClauseStr})";
    }

    return implode(' AND ', $whereArr);
  }


  /**
   * Método para manejar excepciones de la clase
   *
   * @param Exception $e
   * @param string    $method
   * @param array     $arguments
   *
   * @throws Exception
   */
  private function handleException(
    Exception $e,
    string $method,
    array $arguments,
  ) {
    if (!($e instanceof PDOException)) {
      throw $e;
    }

    // https://mariadb.com/kb/en/mariadb-error-codes/
    list($sqlState, $errorCode, $errorDesc) = $e->errorInfo;
    self::$logger::log("{$errorCode}: {$errorDesc}", $sqlState);

    $errorMsg = $e->getMessage();
    $defaultErrMsg = 'No se pudo hacer la operación con la base de datos.';

    switch ($sqlState) {
      // SQLSTATE[HY000]: General error: 2006 MySQL server has gone away
      case 'HY000':
        if (str_contains($errorMsg, 'gone away')) {
          // Reintentamos la conexión
          $log = ">>> La conexión con la base de datos se ha perdido. "
                . "Se intentará reconectar...";
          self::$logger::log([], $log);
          $this->resetDBH();
          return $this->$method(...$arguments);
        }
        break;

      // Duplicate entry
      case '23000':
        if (str_contains($errorMsg, 'Duplicate entry')) {
          throw new Exception(self::ERR_REPETIDO, 409, $e);
        }
        break;

      // "SQLSTATE[22001]: String data, right truncated: 1406
      case '22001':
        // "Data too long for column 'descripcion' at row 1"
        preg_match(
          "/Data too long for column '([^']+)' at row (\d)+/",
          $errorDesc,
          $matches
        );
        $m = "El campo '{$matches[1]}' contiene demasiada información.";
        throw new Exception($m, 400, $e);

      // Out of range
      case '22003':
        // "Out of range value for column '%s' at row %ld"
        preg_match(
          "/Out of range value for column '([^']+)' at row (\d)+/",
          $errorDesc,
          $matches
        );
        $m = "El campo '{$matches[1]}' contiene demasiada información.";
        throw new Exception($m, 400, $e);

      default:
        throw new Exception($defaultErrMsg, 500, $e);
    }

    throw new Exception($defaultErrMsg, 500, $e);
  }


  private function validateOperator($operator) {
    try {
      $validOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
      if (!in_array($operator, $validOperators)) {
        $msg = "El operador '{$operator}' no es válido.";
        throw new Exception($msg, 400);
      }

      return $operator;
    }

    catch(Exception $e) {
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  /**
   * @param string|string[] $cols
   *
   * @return string
   */
  private function parseColumns($cols): string {
    try {
      $_cols = is_string($cols)
        ? explode(',', $cols)
        : $cols;

      $colsArr = [];
      foreach($_cols as $col) {
        $colsArr[] = $this->quoteWrap(trim($col));
      }

      return implode(', ', $colsArr);
    }

    catch(Exception $e) {
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


  private function quoteWrap(string $column): string {
    try {
      // If column is * or a function, don't quote it
      if (preg_match('/\w+\(.*\)|\*/', $column)) {
        return $column;
      }

      return "`{$column}`";
    }

    catch(Exception $e) {
      return $this->handleException($e, __FUNCTION__, func_get_args());
    }
  }


}
