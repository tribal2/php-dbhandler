<?php

namespace Tribal2\DbHandler\Interfaces;

interface FetchPaginatedResultInterface {


  public function __construct(
    array $data,
    int $count,
    int $page,
    int $perPage,
  );


}
