<?php

namespace Tribal2\DbHandler\Interfaces;


interface ColumnsInterface {


  public function __construct(PDOWrapperInterface $pdo);


  public function for(string $table): self;


  public function has(string $column): bool;


}
