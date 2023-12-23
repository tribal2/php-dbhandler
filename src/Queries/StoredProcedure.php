<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Helpers\StoredProcedureArgument;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\SchemaInterface;
use Tribal2\DbHandler\Interfaces\StoredProcedureArgumentInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Traits\QueryBeforeExecuteCheckIfReadOnlyTrait;
use Tribal2\DbHandler\Traits\QueryFetchResultsTrait;

class StoredProcedure extends QueryAbstract {
  use QueryBeforeExecuteCheckIfReadOnlyTrait;
  use QueryFetchResultsTrait;

  // Dependen
  protected SchemaInterface $_schema;

  // Properties
  public string $name;

  /**
   * @var StoredProcedureArgumentInterface[]
   */
  private array $params;


  public function setSchema(?SchemaInterface $schema = NULL): void {
    $this->_schema = $schema ?? new Schema($this->_pdo, $this->_common);
  }


  protected function beforeExecute(): void {
    $this->checkIfReadOnly();

    $this->checkName();
    $this->checkParams();
  }


  public static function _call(
    string $name,
    PDOWrapperInterface $pdo,
    ?array $params = NULL,
    ?CommonInterface $common = NULL,
  ): self {
    $instance = new self($pdo, $common);
    $instance->call($name, $params);

    return $instance;
  }


  /**
   * Call a stored procedure
   *
   * @param string                              $name
   * @param ?StoredProcedureArgumentInterface[] $params
   */
  public function call(
    string $name,
    ?array $params = NULL,
  ): self {
    $this->name = $name;

    $this->params = $params ?? $this->getParams();

    return $this;
  }


  public function with(string $paramName, mixed $value): self {
    $this->checkName();

    // Throw if there is no parameter with the given name in $this->params
    if (!array_key_exists($paramName, $this->params)) {
      throw new Exception(
        "No parameter with name '{$paramName}' exists for stored procedure '{$this->name}'",
        500
      );
    }

    $this->params[$paramName]->addValue($value);

    return $this;
  }


  public function getArguments(): array {
    $setArguments = [];

    foreach ($this->params as $param) {
      if ($param->hasValue()) {
        $setArguments[$param->name] = $param->value;
      }
    }

    return $setArguments;
  }


  public function getSql(?PDOBindBuilderInterface $bindBuilder = NULL): string {
    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $params = [];
    foreach ($this->params as $param) {
      $paramValue = $param->hasValue() ? $param->value : NULL;
      $params[$param->position - 1] = $bindBuilder->addValueWithPrefix(
        $paramValue,
        $param->name,
        is_null($paramValue) ? PDO::PARAM_NULL : PDO::PARAM_STR,
      );
    }

    $procParams = implode(', ', $params);

    return "CALL {$this->name}({$procParams});";
  }


  private function getParams(): array {
    if (!isset($this->_schema)) {
      $this->setSchema();
    }

    return StoredProcedureArgument::getAllFor($this->name, $this->_schema);
  }


  private function checkName(): void {
    if (empty($this->name)) {
      throw new Exception(
        'No stored procedure name has been set. Use the call() method to set the name.',
        500
      );
    }
  }


  private function checkParams(): void {
    if (empty($this->params)) {
      throw new Exception(
        "No parameters have been set for stored procedure '{$this->name}'.",
        500
      );
    }
  }


}
