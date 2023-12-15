<?php

use Tribal2\DbHandler\Helpers\Cache;


describe('Cache', function () {

  it('can be instantiated', function () {
    $cache = new Cache();
    expect($cache)->toBeInstanceOf(Cache::class);
  });

  test('Cache set and get works correctly', function () {
    $cache = new Cache();
    $key = 'testKey';
    $value = 'testValue';

    $cache->set($key, $value);
    $retrievedValue = $cache->get($key);

    expect($retrievedValue)->toBe($value);
  });

  test('Cache delete removes the item', function () {
    $cache = new Cache();
    $key = 'testKey';
    $cache->set($key, 'value');
    $cache->delete($key);

    expect($cache->has($key))->toBeFalse();
  });

  test('Cache clear removes all items', function () {
    $cache = new Cache();
    $cache->set('key1', 'value1');
    $cache->set('key2', 'value2');
    $cache->clear();

    expect($cache->has('key1'))->toBeFalse();
    expect($cache->has('key2'))->toBeFalse();
  });

  test('Cache has returns correct boolean', function () {
    $cache = new Cache();
    $key = 'testKey';
    $cache->set($key, 'value');

    expect($cache->has($key))->toBeTrue();
    expect($cache->has('nonExistingKey'))->toBeFalse();
  });

  test('Cache setMultiple and getMultiple work correctly', function () {
    $cache = new Cache();
    $values = ['key1' => 'value1', 'key2' => 'value2'];
    $cache->setMultiple($values);

    $retrievedValues = $cache->getMultiple(array_keys($values));
    expect($retrievedValues)->toBe($values);
  });

  test('Cache deleteMultiple removes specified items', function () {
    $cache = new Cache();
    $cache->set('key1', 'value1');
    $cache->set('key2', 'value2');
    $cache->deleteMultiple(['key1', 'key2']);

    expect($cache->has('key1'))->toBeFalse();
    expect($cache->has('key2'))->toBeFalse();
  });

  test('Cache respects TTL', function () {
    $cache = new Cache();
    $key = 'testKey';
    $cache->set($key, 'value', 1); // 1 second TTL
    sleep(2); // Waiting more than the TTL

    expect($cache->has($key))->toBeFalse();
  });
});
