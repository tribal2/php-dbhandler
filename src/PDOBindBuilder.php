<?php

namespace Tribal2\DbHandler;

use PDO, PDOStatement, Exception;
use PdoDebugger;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;

class PDOBindBuilder implements PDOBindBuilderInterface {

  private array $data = [];
  private array $prefixCounter = [];
  private string $defaultPrefix = "placeholder";


  /**
   * Add a value and generate a PDO named parameters
   *
   * @param mixed $value The value to add.
   * @param int   $type  The PDO data type.
   *
   * @return string The generated PDO named parameter.
   */
  public function addValue(mixed $value, int $type = PDO::PARAM_STR): string {
    return $this->addValueWithPrefix($value, $this->defaultPrefix, $type);
  }


  /**
   * Add a value and generate a PDO named parameters with a custom prefix
   *
   * @param mixed  $value  The value to add.
   * @param string $prefix The prefix to use.
   * @param int    $type   The PDO data type.
   *
   * @return string The generated PDO named parameter.
   */
  public function addValueWithPrefix(
    mixed $value,
    string $prefix,
    int $type = PDO::PARAM_STR,
  ): string {
    $this->checkType($type);
    $this->checkValueWithType($value, $type);

    // Remove non-alphanumeric characters from prefix
    $prefixAlpha = preg_replace('/[^a-zA-Z0-9_]/', '_', $prefix);

    if (!isset($this->prefixCounter[$prefixAlpha])) {
      $this->prefixCounter[$prefixAlpha] = 0;
    }

    $this->prefixCounter[$prefixAlpha]++;
    $placeholder = ":{$prefixAlpha}___{$this->prefixCounter[$prefixAlpha]}";

    $this->data[$placeholder] = [
      'value' => $value,
      'type' => $type,
    ];

    return $placeholder;
  }


  /**
   * Returns the key-value array of values. The key is the PDO named parameter.
   *
   * @return array The key-value array of values.
   */
  public function getValues(): array {
    return $this->data;
  }


  /**
   * Bind the values to a PDOStatement.
   *
   * @param PDOStatement $stmt The PDOStatement to bind the values to.
   *
   * @return void
   */
  public function bindToStatement(PDOStatement $stmt): void {
    foreach ($this->data as $placeholder => $valueCfg) {
        $stmt->bindValue(
          $placeholder,
          $valueCfg['value'],
          $valueCfg['type'],
        );
    }
  }


  /**
   * Generate a SQL query with the values replaced.
   *
   * @param string $query The SQL query.
   *
   * @return string The generated SQL query.
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
   * Check if the type is valid.
   * https://www.php.net/manual/en/pdo.constants.php
   *
   * @param int $type The PDO data type.
   *
   * @throws Exception If the type is not valid.
   *
   * @return void
   */
  private function checkType(int $type): void {
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
      throw new Exception("Invalid PDO data type: {$type}");
    }
  }


  private function checkValueWithType(
    mixed $value,
    int $type = PDO::PARAM_STR,
  ): void {
    $isInvalid = FALSE;

    switch ($type) {
      case PDO::PARAM_BOOL:
        $isInvalid = !is_bool($value);
        break;

      case PDO::PARAM_NULL:
        $isInvalid = !is_null($value);
        break;

      case PDO::PARAM_INT:
        $isInvalid = !is_int($value);
        break;

      case PDO::PARAM_STR_CHAR:
      case PDO::PARAM_STR_NATL:
      case PDO::PARAM_STR:
        $isInvalid = !(is_string($value) || is_numeric($value));
        break;

      default:
        $isInvalid = TRUE;
        break;
    }

    if ($isInvalid) {
      $humanPdoType = [
        PDO::PARAM_BOOL => 'boolean',
        PDO::PARAM_NULL => 'null',
        PDO::PARAM_INT => 'integer',
        PDO::PARAM_STR => 'string',
        PDO::PARAM_LOB => 'lob',
        PDO::PARAM_STMT => 'statement',
        PDO::PARAM_INPUT_OUTPUT => 'input/output',
        PDO::PARAM_STR_CHAR => 'string or character',
        PDO::PARAM_STR_NATL => 'string or national character',
      ];
      $valueType = gettype($value);
      $expectedPdoType = $humanPdoType[$type] ?? $type;
      throw new Exception(
        "Invalid value type for PDO data type: {$expectedPdoType} ({$valueType})",
      );
    }
  }


}
