<?php

namespace Tribal2;


class Cache implements CacheInterface {

  /**
   * Array de datos en cache
   * @var array
   */
  private static $data = [];


  /**
   * Método para obtener datos del cache temporal para evitar consultas
   * repetidas de la misma información
   * @param string $group Nombre del grupo donde se almacenará la información
   *                      (normalmente nombre del método originado con __METHOD__)
   * @param mixed  $key   Nombre de la llave para identificar la información
   *                      dentro del grupo (normalmente argumentos del método
   *                      originado con func_get_args())
   *
   * @return mixed Datos almacenados en cache (NULL si no se encuentra nada)
   * @throws Exception
   */
  final public static function get(string $group, $key) {
    $cache = &self::$data;

    // Codificamos llave
    $argKey = json_encode($key);

    if (
      isset($cache[$group])
      && isset($cache[$group][$argKey])
    ) {
      $data = $cache[$group][$argKey];
      return is_object($data) ? clone $data : $data;
    }

    return NULL;
  }


  /**
   * Método para escribir datos en un cache temporal para evitar consultas
   * repetidas de la misma información
   *
   * @param string $group Nombre del grupo donde se almacenará la información
   *                      (normalmente nombre del método originado con __METHOD__)
   * @param mixed  $key   Nombre de la llave para identificar la información
   *                      dentro del grupo (normalmente argumentos del método
   *                      originado con func_get_args())
   * @param mixed  $data  Datos a incluir en el cache
   *
   * @throws Exception
   */
  final public static function set(string $group, $key, $data): void {
    $cache = &self::$data;

    // Creamos el grupo si no existe
    if (!isset($cache[$group])) {
      $cache[$group] = [];
    }

    // Codificamos key
    $argKey = json_encode($key);

    // Almacenamos datos
    $cache[$group][$argKey] = is_object($data) ? clone $data : $data;
  }


  // phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
  private function __construct() {}
  private function __clone() {}
  public function __wakeup() {}
  // phpcs:enable
}
