<?php

namespace Tribal2\DbHandler;

use PDO, PDOStatement, Exception;
use PdoDebugger;


class PDOBindBuilder {

  private array $data = [];
  private array $prefixCounter = [];
  private string $phPrefix = ":placeholder___";


  /**
   * Añadir un valor y generar automáticamente un placeholder.
   *
   * @param mixed $value El valor a añadir.
   * @param int   $type  El tipo de dato.
   *
   * @return string El placeholder generado.
   */
  public function addValue($value, int $type = PDO::PARAM_STR) {
    $this->checkType($type);

    $placeholder = $this->phPrefix . (count($this->data) + 1);

    $this->data[$placeholder] = [
      'value' => $value,
      'type' => $type,
    ];

    return $placeholder;
  }


  /**
   * Añadir un valor y generar automáticamente un placeholder usando el prefijo
   * suministrado.
   *
   * @param mixed  $value  El valor a añadir.
   * @param string $prefix El prefijo del placeholder.
   * @param int    $type   El tipo de dato.
   *
   * @return string El placeholder generado.
   */
  public function addValueWithPrefix(
    $value,
    string $prefix,
    int $type = PDO::PARAM_STR,
  ) {
    $this->checkType($type);

    if (!isset($this->prefixCounter[$prefix])) {
      $this->prefixCounter[$prefix] = 0;
    }

    $this->prefixCounter[$prefix]++;
    $placeholder = ":{$prefix}___{$this->prefixCounter[$prefix]}";

    $this->data[$placeholder] = [
      'value' => $value,
      'type' => $type,
    ];

    return $placeholder;
  }


  /**
   * Devuelve los valores añadidos
   *
   * @return array
   */
  public function getValues(): array {
    return $this->data;
  }


  /**
   * Bind de los valores al statement de PDO.
   *
   * @param PDOStatement $stmt El statement de PDO.
   */
  public function bindToStatement(PDOStatement $stmt) {
    foreach ($this->data as $placeholder => $valueCfg) {
        $stmt->bindValue(
          $placeholder,
          $valueCfg['value'],
          $valueCfg['type'],
        );
    }
  }


  /**
   * Devuelve el query con los valores añadidos en forma de string.
   *
   * @param string $query
   *
   * @return string
   */
  public function debugQuery(string $query): string {
    $values = [];

    foreach ($this->data as $key => $valueCfg) {
      $newKey = substr($key, 1);
      $values[$newKey] = $valueCfg['value'];
    }

    return PdoDebugger::show($query, $values);
  }


  /**
   * Comprueba que el tipo de dato sea válido.
   * https://www.php.net/manual/es/pdo.constants.php
   *
   * @param int $type
   *
   * @return void
   */
  private function checkType(int $type) {
    if (
      !in_array(
        $type,
        [
          PDO::PARAM_BOOL,
          PDO::PARAM_NULL,
          PDO::PARAM_INT,
          PDO::PARAM_STR,
          PDO::PARAM_LOB,
          PDO::PARAM_STMT,
          PDO::PARAM_INPUT_OUTPUT,
          PDO::PARAM_STR_CHAR,
          PDO::PARAM_STR_NATL,
        ],
      )
    ) {
      throw new Exception("Tipo de dato no válido");
    }
  }


}
