<?php

namespace Tribal2\DbHandler\Interfaces;


interface CommonInterface {


  public function quoteWrap(string $column): string;


  public function parseColumns(string|array $cols): string;


  public function checkValue(
    mixed $value,
    ?string $column = NULL,
    array $expectedType = [],
  ): int;


}
