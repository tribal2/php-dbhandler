<?php

namespace Tribal2\DbHandler\Interfaces;

interface QueryInterface {


  public function execute(?PDOBindBuilderInterface $bindBuilder = NULL): mixed;


  public function getSql(?PDOBindBuilderInterface $bindBuilder = NULL): string;


}
