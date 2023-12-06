<?php

namespace Tribal2\DbHandler\Interfaces;

use Tribal2\DbHandler\Interfaces\ColumnsInterface;

interface ColumnsFactoryInterface {


  public function __construct(PDOWrapperInterface $pdo);


  public function make(string $table): ColumnsInterface;


}
