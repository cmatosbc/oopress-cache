Here's the Memcached cache driver following the same logic as the RedisCache class:
PHP
Data format:


<?php

namespace BC\Zenith\Cache;

use Psr\SimpleCache\CacheInterface;
use BC\Zenith\Exceptions\MissingExtensionException;
use BC\Zenith\Exceptions\DriverException;
use Memcached;

/**
 * Memcached PSR-16 compliant cache driver.
 * For more info and docblock comments find the reference. 
 * @link https://www.php-fig.org/psr/psr-16/
 * @package MemcachedCache
 * 
 */
class MemcachedCache implements CacheInterface
{
    private Memcached $memcached;

    public function __construct(string $host, int $port)
    {
        try {
            if (!extension_loaded('memcached')) {
                throw new MissingExtensionException('Memcached extension is not loaded.');
            }

            $this->memcached = new Memcached();
            if (!$this->memcached->addServer($host, $port)) {
                throw new DriverException('Failed to connect to Memcached server.');
            }
        } catch (\Exception $e) {
            throw new DriverException('Failed to connect to Memcached: ' . $e->getMessage());
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->memcached->get($key);
        return $value !== false ? $value : $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        if ($ttl !== null) {
            $ttl = $ttl instanceof \DateInterval ? $ttl->s : $ttl;
            return $this->memcached->set($key, $value, $ttl);
        } else {
            return $this->memcached->set($key, $value);
        }
    }

    public function delete(string $key): bool
    {
        return $this->memcached->delete($key);
    }

    public function clear(): bool
    {
        return $this->memcached->flush();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = $this->memcached->getMulti(array_values($keys));
        $results = [];
        foreach ($keys as $key => $originalKey) {
            $results[$originalKey] = isset($values[$key]) ? $values[$key] : $default;
        }
        return $results;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if ($ttl !== null) {
                $ttl = $ttl instanceof \DateInterval ? $ttl->s : $ttl;
                $success = $success && $this->memcached->set($key, $value, $ttl);
            } else {
                $success = $success && $this->memcached->set($key, $value);
            }
        }
        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->memcached->deleteMulti(array_values($keys));
    }

    public function has(string $key): bool
    {
        return $this->memcached->get($key) !== false;
    }
}
