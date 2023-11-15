<?php

namespace Tribal2;

class DbConfig {


  public function __construct(
    public string $host,
    public int $port,
    public string $encoding,
    public string $dbName,
    public string $user,
    public string $password,
  ) {}


}
