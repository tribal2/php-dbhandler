<?php

namespace Tribal2\DbHandler\Interfaces;


interface CacheInterface {

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
   */
  public function get(string $group, $key);


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
   */
  public function set(string $group, $key, $data): void;


}
