<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Helpers\StoredProcedureArgument;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Interfaces\QueryInterface;
use Tribal2\DbHandler\Interfaces\StoredProcedureArgumentInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\Traits\QueryBeforeExecuteDoNothingTrait;

class StoredProcedure extends QueryAbstract implements QueryInterface {
  use QueryBeforeExecuteDoNothingTrait;

  // Properties
  public string $name;

  /**
   * @var StoredProcedureArgumentInterface[]
   */
  private array $params;


  public static function call(
    string $name,
    PDOWrapperInterface $pdo,
    ?array $params = NULL,
    ?CommonInterface $common = NULL,
  ): self {
    return new self($name, $pdo, $params, $common);
  }


  /**
   * StoredProcedure constructor.
   *
   * @param string                                  $name
   * @param PDOWrapperInterface                     $pdo
   * @param StoredProcedureArgumentInterface[]|null $params
   * @param CommonInterface|null                    $common
   */
  public function __construct(
    string $name,
    PDOWrapperInterface $pdo,
    ?array $params = NULL,
    ?CommonInterface $common = NULL,
  ) {
    parent::__construct($pdo, $common);

    $this->name = $name;

    if (is_null($params)) {
      $schema = new Schema($this->_pdo, $this->_common);
      $params = StoredProcedureArgument::getAllFor($name, $schema);
    }

    $this->params = $params;
  }


  public function with(string $paramName, mixed $value): self {
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


  public function execute(?PDOBindBuilderInterface $bindBuilder = NULL): array {
    return parent::_execute($bindBuilder);
  }


}
