<?php

namespace Tribal2\DbHandler\Interfaces;


interface CommonInterface {


  public function quoteWrap(string $column): string;


  public function parseColumns($cols): string;


  public function checkValue(
    $value,
    ?string $column = NULL,
    array $expectedType = [],
  ): int;


}
