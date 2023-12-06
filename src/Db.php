<?php

namespace Tribal2\DbHandler;

use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Queries\Delete;
use Tribal2\DbHandler\Queries\Insert;
use Tribal2\DbHandler\Queries\Select;
use Tribal2\DbHandler\Queries\Update;

class Db {


  public function __construct(
    private PDOWrapperInterface $pdo,
  ) {}


  public function select(): Select {
    return new Select($this->pdo);
  }


  public function insert(): Insert {
    return new Insert($this->pdo);
  }


  public function update(): Update {
    return new Update($this->pdo);
  }


  public function delete(): Delete {
    return new Delete($this->pdo);
  }


}
