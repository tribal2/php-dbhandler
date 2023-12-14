<?php

namespace Tribal2\DbHandler\Core;

use Tribal2\DbHandler\Interfaces\FetchPaginatedResultInterface;

class FetchPaginatedResult implements FetchPaginatedResultInterface {

  public int $totalPages;


  public function __construct(
    public array $data,
    public int $count,
    public int $page,
    public int $perPage,
  ) {
    $this->totalPages = (int)ceil($count / $perPage);
  }


}
