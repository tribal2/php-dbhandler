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

    $typeError = match($this->type) {
      'int' => !is_int($value),
      'varchar',
      'text',
      'date',
      'datetime',
      'time',
      'json' => !is_string($value),
      'decimal',
      'double',
      'float' => !is_float($value),
      'tinyint',
      'bit',
      'boolean' => !is_bool($value),
      default => throw new \Exception(
        "Invalid type for argument {$this->name}.",
        500,
      ),
    };

    if ($this->type === 'varchar' && strlen($value) > $this->maxCharLength) {
      $strLenError = TRUE;
    }

    if ($typeError) {
      throw new \Exception(
        "Invalid type for argument '{$this->name}'. Expected type: {$this->type}.",
        500,
      );
    }

    if ($strLenError) {
      throw new \Exception(
        "Invalid length for argument '{$this->name}'. Expected: {$this->maxCharLength}.",
        500,
      );
    }
  }


  public static function getAllFor(
    string $procedureName,
    SchemaInterface $schema,
  ): array {
    $dbArguments = $schema->getStoredProcedureArguments($procedureName);

    // @todo 1 Does it make sense to throw an exception if there are no arguments?
    if (empty($dbArguments)) {
      throw new \Exception(
        "No arguments found for stored procedure '{$procedureName}'.",
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
