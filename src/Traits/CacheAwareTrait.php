<?php

namespace Tribal2\DbHandler\Traits;

use DateInterval;
use Exception;
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
   * @return static
   */
  public function withCache(
    mixed $default = NULL,
    null|int|DateInterval $ttl = NULL,
  ): static {
    if ($this->cache === NULL) {
      throw new Exception('Cache is not set. Call setCache() first.');
    }

    $this->useCache = TRUE;
    $this->cacheDefault = $default;
    $this->cacheTtl = $ttl;

    return $this;
  }


  public function execute(
    ?PDOBindBuilderInterface $bindBuilder = NULL,
  ): array|int {
    // Check cache
    if ($this->useCache) {
      $cacheKey = $this->generateCacheKey();

      if ($this->cache->has($cacheKey)) {
        return $this->cache->get($cacheKey);
      }
    }

    $queryResult = parent::execute($bindBuilder);

    // Cache results
    if ($this->useCache) {
      $this->cache->set($cacheKey, $queryResult, $this->cacheTtl);
    }

    return $queryResult;
  }


  private function generateCacheKey(): string {
    // Generate query
    $bindBuilder = new PDOBindBuilder();
    $query = $this->getSql($bindBuilder);

    // Check cache
    return md5($bindBuilder->debugQuery($query));
  }


}
