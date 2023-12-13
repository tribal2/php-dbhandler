<?php

namespace Tribal2\DbHandler;

use Tribal2\DbHandler\Interfaces\DbConfigInterface;

class DbConfig implements DbConfigInterface {


  public static function create(string $dbName): self {
    return new self($dbName);
  }


  public function __construct(
    private string $dbName,
    private ?string $user = NULL,
    private ?string $password = NULL,
    private string $host = 'localhost',
    private int $port = 3306,
    private string $encoding = 'utf8',
    private bool $readOnly = FALSE,
  ) {}


  public function withUser(string $user): self {
    $this->user = $user;
    return $this;
  }


  public function withPassword(string $password): self {
    $this->password = $password;
    return $this;
  }


  public function withHost(string $host): self {
    $this->host = $host;
    return $this;
  }


  public function withPort(int|string $port): self {
    if (is_string($port) && !is_numeric($port)) {
      throw new \InvalidArgumentException('Port must be numeric');
    }

    $this->port = (int)$port;
    return $this;
  }


  public function withCharset(string $encoding): self {
    $this->encoding = $encoding;
    return $this;
  }


  public function withReadOnlyMode(): self {
    $this->readOnly = TRUE;
    return $this;
  }


  public function withReadOnlyModeOff(): self {
    $this->readOnly = FALSE;
    return $this;
  }


  public function getConnString(): string {
    return "mysql:host={$this->host}; "
      . "port={$this->port}; "
      . "dbname={$this->dbName}; "
      . "charset={$this->encoding};";
  }


  public function getDbName(): string {
    return $this->dbName;
  }


  public function getUser(): ?string {
    return $this->user;
  }


  public function getPassword(): ?string {
    return $this->password;
  }


  public function isReadOnly(): bool {
    return $this->readOnly;
  }


}
