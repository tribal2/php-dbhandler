<?php

namespace Tribal2\DbHandler\Helpers;

use Tribal2\DbHandler\Interfaces\SchemaInterface;
use Tribal2\DbHandler\Interfaces\StoredProcedureArgumentInterface;

class StoredProcedureArgument implements StoredProcedureArgumentInterface {

  public mixed $value;
  private bool $isValueSet = FALSE;


  public function __construct(
    public int $position,
    public string $name,
    public string $type,
    public ?int $maxCharLength = NULL,
  ) {}


  public function addValue(mixed $value): self {
    $this->validateValue($value);

    $this->value = $value;
    $this->isValueSet = TRUE;

    return $this;
  }


  public function hasValue(): bool {
    return $this->isValueSet;
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
    string $procedureName,
    SchemaInterface $schema,
  ): array {
    $dbArguments = $schema->getStoredProcedureArguments($procedureName);

    if (empty($dbArguments)) {
      throw new \Exception(
        "No arguments found for stored procedure {$procedureName}.",
        500,
      );
    }

    $arguments = [];
    foreach ($dbArguments as $argument) {
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
