<?php

namespace Tribal2\DbHandler\Core;

use Tribal2\DbHandler\Interfaces\FetchResultInterface;

class FetchResult implements FetchResultInterface {

  public array $data;
  public int $count;


  public function __construct(array $data) {
    $this->data = $data;
    $this->count = count($data);
  }


}
