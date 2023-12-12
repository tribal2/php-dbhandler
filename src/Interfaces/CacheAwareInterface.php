<?php

namespace Tribal2\DbHandler\Interfaces;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

/**
 * Describes a cache-aware instance.
 */
interface CacheAwareInterface {


  /**
   * Sets a cache instance on the object.
   *
   * @param CacheInterface $cache
   *
   * @return void
   */
  public function setCache(CacheInterface $cache): void;


  public function withCache(
    mixed $default,
    null|int|DateInterval $ttl = NULL,
  ): self;


}
