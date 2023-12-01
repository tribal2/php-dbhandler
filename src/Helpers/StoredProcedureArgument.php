<?php

namespace Tribal2\DbHandler\Helpers;

use PDO;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\Interfaces\StoredProcedureArgumentInterface;
use Tribal2\DbHandler\PDOBindBuilder;
use Tribal2\DbHandler\PDOSingleton;

class StoredProcedureArgument implements StoredProcedureArgumentInterface {

  public mixed $value;


  public function __construct(
    public int $position,
    public string $name,
    public string $type,
    public ?int $maxCharLength = NULL,
  ) {}


  public function addValue(mixed $value): self {
    $this->validateValue($value);

    $this->value = $value;

    return $this;
  }


  private function validateValue(mixed $value): void {
    $typeError = FALSE;
    $strLenError = FALSE;

    switch ($this->type) {
      case 'int':
        $typeError = !is_int($value);
        break;
      case 'varchar':
        $typeError = !is_string($value);
        $strLenError = strlen($value) > $this->maxCharLength;
        break;
      case 'text':
        $typeError = !is_string($value);
        break;
      case 'date':
        $typeError = !is_string($value);
        break;
      case 'datetime':
        $typeError = !is_string($value);
        break;
      case 'time':
        $typeError = !is_string($value);
        break;
      case 'decimal':
        $typeError = !is_float($value);
        break;
      case 'double':
        $typeError = !is_float($value);
        break;
      case 'float':
        $typeError = !is_float($value);
        break;
      case 'tinyint':
        $typeError = !is_bool($value);
        break;
      case 'bit':
        $typeError = !is_bool($value);
        break;
      case 'boolean':
        $typeError = !is_bool($value);
        break;
      case 'json':
        $typeError = !is_string($value);
        break;
      default:
        throw new \Exception(
          "Invalid type for argument {$this->name}.",
          500,
        );
    }

    if ($typeError) {
      throw new \Exception(
        "Invalid type for argument {$this->name}. Expected {$this->type}.",
        500,
      );
    }

    if ($strLenError) {
      throw new \Exception(
        "Invalid length for argument {$this->name}. Expected {$this->maxCharLength}.",
        500,
      );
    }
  }


  public static function getAllFor(
    string $dbName,
    string $procedureName,
    ?PDO $pdo = NULL,
    ?PDOBindBuilderInterface $bindBuilder = NULL,
  ): array {
    $_pdo = $pdo ?? PDOSingleton::get();
    $_bindBuilder = $bindBuilder ?? new PDOBindBuilder();

    $dbPlaceholder = $_bindBuilder->addValue($dbName);
    $namePlaceholder = $_bindBuilder->addValue($procedureName);

    $query = "
      SELECT
          ORDINAL_POSITION,
          PARAMETER_NAME,
          DATA_TYPE,
          CHARACTER_MAXIMUM_LENGTH
      FROM
          information_schema.PARAMETERS
      WHERE
          SPECIFIC_SCHEMA = {$dbPlaceholder}
          AND SPECIFIC_NAME = {$namePlaceholder};
    ";

    $sth = $_pdo->prepare($query);
    $_bindBuilder->bindToStatement($sth);

    $sth->execute();

    $result = $sth->fetchAll(PDO::FETCH_OBJ);

    if (empty($result)) {
      throw new \Exception(
        "No arguments found for stored procedure {$procedureName}.",
        500,
      );
    }

    $arguments = [];
    foreach ($result as $argument) {
      $arguments[$argument->PARAMETER_NAME] = new StoredProcedureArgument(
        $argument->ORDINAL_POSITION,
        $argument->PARAMETER_NAME,
        $argument->DATA_TYPE,
        $argument->CHARACTER_MAXIMUM_LENGTH,
      );
    }

    return $arguments;
  }


}
