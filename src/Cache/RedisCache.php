<?php

namespace Oopress\Cache;

use Psr\SimpleCache\CacheInterface;
use Redis;

/**
 * Redis PSR-16 compliant cache driver.
 * For more info and docblock comments find the reference. 
 * @link https://www.php-fig.org/psr/psr-16/
 * @package RedisCache
 * 
 */

class RedisCache implements CacheInterface
{
    private Redis $redis;

    /**
     * Constructs a new RedisCache instance.
     *
     * @param string $host The hostname of the Redis server.
     * @param int $port The port number of the Redis server.
     * @param string|null $password The password for the Redis server (optional).
     * @param int $database The database number to use on the Redis server (optional).
     *
     * @throws MissingExtensionException If the Redis extension is not loaded.
     * @throws DriverException If there is a problem connecting to the Redis server.
     */
    public function __construct(string $host, int $port, string $password = null, int $database = 0)
    {
        try {
            if (!extension_loaded('redis')) {
                throw new MissingExtensionException('Redis extension is not loaded.');
            }

            $this->redis = new \Redis();
            $this->redis->connect($host, $port);
            if (!empty($password)) {
                $this->redis->auth($password);
            }
            $this->redis->select($database);
        } catch (\RedisException $e) {
            throw new DriverException('Failed to connect to Redis: ' . $e->getMessage());
        }

        $this->redis->connect($host, $port);
        if (!empty($password)) {
            $this->redis->auth($password);
        }
        $this->redis->select($database);
    }

    /**
     * Retrieves a value from the cache.
     *
     * @param string $key The cache key.
     * @param mixed $default The default value to return if the key is not found.
     * @return mixed The cached value, or the default value if not found.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Stores a value in the cache.
     *
     * @param string $key The cache key.
     * @param mixed $value The value to store.
     * @param null|int|\DateInterval $ttl The time to live for the cached value, in seconds.
     * @return bool True if the value was stored successfully, false otherwise.
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        if ($ttl !== null) {
            $ttl = $ttl instanceof \DateInterval ? $ttl->s : $ttl;
            return $this->redis->setex($key, $ttl, $value);
        } else {
            return $this->redis->set($key, $value);
        }
    }

    /**
     * Deletes a value from the cache.
     *
     * @param string $key The cache key.
     * @return bool True if the value was deleted successfully, false otherwise.
     */
    public function delete(string $key): bool
    {
        return $this->redis->del($key) > 0;
    }

    /**
     * Clears the entire cache.
     *
     * @return bool True if the cache was cleared successfully, false otherwise.
     */
    public function clear(): bool
    {
        return $this->redis->flushAll();
    }

    /**
     * Retrieves multiple values from the cache.
     *
     * @param iterable $keys The cache keys.
     * @param mixed $default The default value to return if a key is not found.
     * @return iterable An iterable containing the cached values, or the default value if not found.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = $this->redis->mget(array_values($keys));
        $results = [];
        foreach ($keys as $key => $originalKey) {
            $results[$originalKey] = isset($values[$key]) ? $values[$key] : $default;
        }
        return $results;
    }

    /**
     * Stores multiple values in the cache.
     *
     * @param iterable $values The values to store.
     * @param null|int|\DateInterval $ttl The time to live for the cached values, in seconds.
     * @return bool True if all values were stored successfully, false otherwise.
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $pipeline = $this->redis->pipeline();
        foreach ($values as $key => $value) {
            if ($ttl !== null) {
                $ttl = $ttl instanceof \DateInterval ? $ttl->s : $ttl;
                $pipeline->setex($key, $ttl, $value);
            } else {
                $pipeline->set($key, $value);
            }
        }
        return $pipeline->execute() === array_fill(0, count($values), true);
    }

    /**
     * Deletes multiple values from the cache.
     *
     * @param iterable $keys The cache keys.
     * @return bool True if all values were deleted successfully, false otherwise.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return $this->redis->del(array_values($keys)) > 0;
    }

    /**
     * Checks if a value exists in the cache.
     *
     * @param string $key The cache key.
     * @return bool True if the value exists, false otherwise.
     */
    public function has(string $key): bool
    {
        return $this->redis->exists($key);
    }
}
