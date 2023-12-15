<?php

use Tribal2\DbHandler\Factories\WhereFactory;
use Tribal2\DbHandler\Queries\Where;

describe('WhereFactory', function () {

  it('should return a Where object', function () {
    $whereFactory = new WhereFactory();
    $where = $whereFactory->make('id', 1);
    expect($where)->toBeInstanceOf(Where::class);
  });

});
