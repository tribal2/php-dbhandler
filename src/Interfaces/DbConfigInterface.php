<?php

namespace Tribal2\DbHandler\Interfaces;

interface DbConfigInterface {


  public static function create(string $dbName): self;


  public function __construct(
    string $dbName,
    ?string $user = NULL,
    ?string $password = NULL,
    string $host = 'localhost',
    int $port = 3306,
    string $encoding = 'utf8',
    bool $readOnly = FALSE,
  );


  public function withUser(string $user): self;


  public function withPassword(string $password): self;


  public function withHost(string $host): self;


  public function withPort(int|string $port): self;


  public function withCharset(string $encoding): self;


  public function withReadOnlyMode(): self;


  public function withReadOnlyModeOff(): self;


  public function getConnString(): string;


  public function getDbName(): string;


  public function getUser(): ?string;


  public function getPassword(): ?string;


  public function isReadOnly(): bool;


}
