<?php

namespace Tribal2\DbHandler\Helpers;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface {

  /**
   * Array de datos en cache
   * @var array
   */
  private array $cache = [];


  public function get(string $key, mixed $default = NULL): mixed {
    if ($this->has($key) === FALSE) {
      return $default;
    }

    $cacheItem = $this->cache[$key];

    return unserialize($cacheItem->value);
  }


  public function set(string $key, mixed $value, null|int|DateInterval $ttl = NULL): bool {
    $this->cache[$key] = (object)[
      'value' => serialize($value),
      'expiresAt' => $this->ttlToUnix($ttl),
    ];

    return TRUE;
  }


  public function delete(string $key): bool {
    unset($this->cache[$key]);
    return TRUE;
  }


  public function clear(): bool {
    $this->cache = [];
    return TRUE;
  }


  public function getMultiple(iterable $keys, mixed $default = NULL): iterable {
    $out = [];
    foreach ($keys as $key) {
      $out[$key] = $this->get($key, $default);
    }

    return $out;
  }


  public function setMultiple(iterable $values, null|int|DateInterval $ttl = NULL): bool {
    foreach ($values as $key => $value) {
      $this->set($key, $value, $ttl);
    }

    return TRUE;
  }


  public function deleteMultiple(iterable $keys): bool {
    foreach ($keys as $key) {
      $this->delete($key);
    }

    return TRUE;
  }


  public function has(string $key): bool {
    if (!isset($this->cache[$key])) {
      return FALSE;
    }

    $cacheItem = $this->cache[$key];

    $nowUnix = (new DateTime())->getTimestamp();
    if ($cacheItem->expiresAt < $nowUnix) {
      unset($this->cache[$key]);
      return FALSE;
    }

    return TRUE;
  }


  private function ttlToUnix(null|int|DateInterval  $ttl): int {
    if ($ttl === NULL) {
      $ttl = new DateInterval('PT1H');
    }

    if (is_int($ttl)) {
      $ttl = new DateInterval('PT' . $ttl . 'S');
    }

    return (new DateTime())->add($ttl)->getTimestamp();
  }


  private function __clone() {}


  public function __construct() {}


  public function __wakeup() {}


}
