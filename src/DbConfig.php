<?php

namespace Tribal2\DbHandler;

class DbConfig {


  public function __construct(
    public string $host,
    public int $port,
    public string $encoding,
    public string $dbName,
    public string $user,
    public string $password,
  ) {}


  public function getConnString(): string {
    return "mysql:host={$this->host}; "
      . "port={$this->port}; "
      . "dbname={$this->dbName}; "
      . "charset={$this->encoding};";
  }


}
