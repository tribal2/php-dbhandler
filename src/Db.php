<?php

namespace Tribal2\DbHandler;

use Psr\SimpleCache\CacheInterface;
use Tribal2\DbHandler\Core\Transaction;
use Tribal2\DbHandler\Interfaces\PDOWrapperInterface;
use Tribal2\DbHandler\Queries\Delete;
use Tribal2\DbHandler\Queries\Insert;
use Tribal2\DbHandler\Queries\Schema;
use Tribal2\DbHandler\Queries\Select;
use Tribal2\DbHandler\Queries\StoredProcedure;
use Tribal2\DbHandler\Queries\Update;

class Db {

  public Transaction $transaction;


  public function __construct(
    private PDOWrapperInterface $pdo,
    private ?CacheInterface $cache = NULL,
  ) {
    $this->transaction = new Transaction($this->pdo);
  }


  public function schema(): Schema {
    return new Schema($this->pdo);
  }


  public function select(): Select {
    $select = new Select($this->pdo);

    if ($this->cache) {
      $select->setCache($this->cache);
    }

    return $select;
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


  public function storedProcedure(): StoredProcedure {
    return new StoredProcedure($this->pdo);
  }


}
