<?php

namespace Tribal2\DbHandler\Traits;

use DateInterval;
use Exception;
use PDO;
use Psr\SimpleCache\CacheInterface;
use Tribal2\DbHandler\Interfaces\PDOBindBuilderInterface;
use Tribal2\DbHandler\PDOBindBuilder;

/**
 * Basic Implementation of CacheAwareInterface.
 */
trait CacheAwareTrait {

  /**
   * The cache instance.
   */
  protected ?CacheInterface $cache = NULL;


  /**
   * Whether to use the cache.
   */
  protected bool $useCache = FALSE;


  /**
   * The default value to return if the cache is not set.
   */
  protected mixed $cacheDefault = NULL;


  /**
   * The cache ttl.
   */
  protected null|int|DateInterval $cacheTtl = NULL;


  /**
   * Sets a cache.
   *
   * @param CacheInterface $cache
   */
  public function setCache(CacheInterface $cache): void {
    $this->cache = $cache;
  }


  /**
   * Prepares the cache settings
   *
   * @param mixed                 $default The default value to return if the cache is not set.
   * @param null|int|DateInterval $ttl     The cache ttl.
   *
   * @return self
   */
  public function withCache(
    mixed $default,
    null|int|DateInterval $ttl = NULL,
  ): self {
    if ($this->cache === NULL) {
      throw new Exception('Cache is not set. Call setCache() first.');
    }

    $this->useCache = TRUE;
    $this->cacheDefault = $default;
    $this->cacheTtl = $ttl;

    return $this;
  }


  protected function _execute(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
    ?int $fetchMode = PDO::FETCH_OBJ,
  ): array|int {
    // Before execute hook
    $this->beforeExecute();

    // Generate query
    $bindBuilder = $bindBuilder ?? new PDOBindBuilder();
    $query = $this->getSql($bindBuilder);

    // Check cache
    if ($this->useCache) {
      $cacheKey = md5($bindBuilder->debugQuery($query));

      if ($this->cache->has($cacheKey)) {
        return $this->cache->get($cacheKey);
      }
    }

    // Execute query
    $queryResult = $this->_pdo->execute($query, $bindBuilder, $fetchMode);

    // Set cache
    if ($this->useCache) {
      $this->cache->set($cacheKey, $queryResult, $this->cacheTtl);
    }

    return $queryResult;
  }


}
