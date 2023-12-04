<?php

namespace Tribal2\DbHandler\Queries;

use Exception;
use PDO;
use Tribal2\DbHandler\Helpers\StoredProcedureArgument;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\QueryInterface;
use Tribal2\DbHandler\Interfaces\StoredProcedureArgumentInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;

class StoredProcedure implements QueryInterface {

  // Properties
  public string $name;

  /**
   * @var StoredProcedureArgumentInterface[]
   */
  private array $params;


  public static function call(string $name): self {
    $dbName = PDOSingleton::getDbName();
    return new self($name, $dbName);
  }


  /**
   * StoredProcedure constructor.
   *
   * @param string                                  $name
   * @param string                                  $dbName
   * @param StoredProcedureArgumentInterface[]|null $params
   */
  public function __construct(
    string $name,
    string $dbName,
    ?array $params = NULL,
  ) {
    $this->name = $name;
    $this->params = $params
      ?? StoredProcedureArgument::getAllFor($dbName, $name);
  }


  public function with(string $name, mixed $value): self {
    // Throw if there is no parameter with the given name in $this->params
    if (!array_key_exists($name, $this->params)) {
      throw new Exception(
        "No parameter with name '{$name}' exists for stored procedure '{$this->name}'",
        500
      );
    }

    $this->params[$name]->addValue($value);

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
    ?PDO $pdo = NULL,
    ?PDOBindBuilderInterface $bindBuilder = NULL,
  ): array {
    $_pdo = $pdo ?? PDOSingleton::get();
    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $query = $this->getSql($bindBuilder);

    $pdoStatement = $_pdo->prepare($query);
    $bindBuilder->bindToStatement($pdoStatement);

    $pdoStatement->execute();
    return $pdoStatement->fetchAll(PDO::FETCH_OBJ);
  }


}
