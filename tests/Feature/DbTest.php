<?php

use Tribal2\DbHandler\Db;
use Tribal2\DbHandler\Core\Transaction;
use Tribal2\DbHandler\Helpers\Cache;
use Tribal2\DbHandler\Queries\Delete;
use Tribal2\DbHandler\Queries\Insert;
use Tribal2\DbHandler\Queries\Schema;
use Tribal2\DbHandler\Queries\Select;
use Tribal2\DbHandler\Queries\StoredProcedure;
use Tribal2\DbHandler\Queries\Update;

require_once __DIR__ . '/_DbTestSchema.php';

beforeAll(function () {
  DbTestSchema::up();
});

afterAll(function () {
  DbTestSchema::down();
});

describe('Db Class', function () {

  beforeEach(function () {
    $this->db = new Db(DbTestSchema::getPdoWrapper());
  });

  it('should return a Transaction instance', function () {
    expect($this->db->transaction())->toBeInstanceOf(Transaction::class);
  });

  it('should return a Schema instance', function () {
    expect($this->db->schema())->toBeInstanceOf(Schema::class);
  });

  it('should return a Select instance', function () {
    $select = $this->db->select();
    expect($select)->toBeInstanceOf(Select::class);

    expect(
      fn() => $select->withCache()
    )->toThrow(Exception::class, 'Cache is not set. Call setCache() first.');
  });

  it('should return a Select instance with caching enabled', function () {
    $db = new Db(
      DbTestSchema::getPdoWrapper(),
      new Cache(),
    );
    $selectInstance = $db->select();

    // Verify that protected CacheInterface $Cache is set
    $reflection = new ReflectionClass($selectInstance);
    $property = $reflection->getProperty('cache');
    $property->setAccessible(TRUE);

    expect($property->getValue($selectInstance))->not()->toBeNull();
  });

  it('should return an Insert instance', function () {
    expect($this->db->insert())->toBeInstanceOf(Insert::class);
  });

  it('should return an Update instance', function () {
    expect($this->db->update())->toBeInstanceOf(Update::class);
  });

  it('should return a Delete instance', function () {
    expect($this->db->delete())->toBeInstanceOf(Delete::class);
  });

  it('should return a StoredProcedure instance', function () {
    expect($this->db->storedProcedure())->toBeInstanceOf(StoredProcedure::class);
  });
});
