<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\Abstracts\QueryAbstract;
use Tribal2\DbHandler\Helpers\StoredProcedureArgument;
use Tribal2\DbHandler\Interfaces\CommonInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\QueryInterface;
use Tribal2\DbHandler\Interfaces\StoredProcedureArgumentInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;

class StoredProcedure extends QueryAbstract implements QueryInterface {

  // Properties
  public string $name;

  /**
   * @var StoredProcedureArgumentInterface[]
   */
  private array $params;


  public static function call(
    string $name,
    ?string $dbName = NULL,
    ?array $params = NULL,
    ?PDO $pdo = NULL,
    ?CommonInterface $common = NULL,
  ): self {
    $dbName = $dbName ?? PDOSingleton::getDbName();
    return new self($name, $dbName, $params, $pdo, $common);
  }


  /**
   * StoredProcedure constructor.
   *
   * @param string                                  $name
   * @param string                                  $dbName
   * @param StoredProcedureArgumentInterface[]|null $params
   * @param PDO|null                                $pdo
   * @param CommonInterface|null                    $common
   */
  public function __construct(
    string $name,
    string $dbName,
    ?array $params = NULL,
    ?PDO $pdo = NULL,
    ?CommonInterface $common = NULL,
  ) {
    parent::__construct($pdo, $common);

    $this->table = $name;
    $this->name = $name;

    $this->params = $params
      ?? StoredProcedureArgument::getAllFor($dbName, $name);
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
      $params[$param->position - 1] = $bindBuilder->addValueWithPrefix(
        $param->hasValue() ? $param->value : NULL,
        $param->name,
      );
    }

    $procParams = implode(', ', $params);

    return "CALL {$this->name}({$procParams});";
  }


  public function execute(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
    ?PDO $pdo = NULL,
  ): array {
    $executedStatement = parent::_execute($bindBuilder, $pdo);

    return $executedStatement->fetchAll(PDO::FETCH_OBJ);
  }


}
