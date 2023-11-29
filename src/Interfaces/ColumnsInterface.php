<?php

namespace Tribal2\DbHandler\Interfaces;


interface ColumnsInterface {


  public static function for(string $table): ColumnsInterface;


  public function __construct(string $table);


  public function has(string $column): bool;


}
