<?php

use Tribal2\DbHandler\Traits\QueryBeforeExecuteCheckTableTrait;

class ClassWithTrait {
  use QueryBeforeExecuteCheckTableTrait;

  public function checkMethod() {
    $this->checkTable();
  }


}

describe('QueryBeforeExecuteCheckTableTrait', function () {

  test('instance should be created', function () {
    $this->trait = new ClassWithTrait();
    expect($this->trait)->toBeInstanceOf(ClassWithTrait::class);
  });

  it('checkMethod() should throw an exception if table name is not set', function () {
    $instanceWithTrait = new ClassWithTrait();
    $instanceWithTrait->checkMethod();
  })->throws(Exception::class, 'Table name is not set');

});
