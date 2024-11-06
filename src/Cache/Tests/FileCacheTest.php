<?php

namespace Oopress\Cache\Tests;

use Oopress\Cache\FileCache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

class FileCacheTest extends TestCase
{
    private FileCache $cache;
    private string $cacheDir;

    public function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/phpunit-cache';
        mkdir($this->cacheDir, 0777, true);
        $this->cache = new FileCache($this->cacheDir);
    }

    public function tearDown(): void
    {
        $this->clearCacheDirectory();
    }

    private function clearCacheDirectory(): void
    {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->cacheDir);
    }

    public function testGet_ExistingKey_ReturnsValue(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        $this->cache->set($key, $value, 3600);

        $this->assertEquals($value, $this->cache->get($key));
    }

    public function testGet_NonExistingKey_ReturnsDefault(): void
    {
        $key = 'non_existing_key';
        $defaultValue = 'default_value';

        $this->assertEquals($defaultValue, $this->cache->get($key, $defaultValue));
    }

    public function testGet_ExpiredKey_ReturnsDefault(): void
    {
        $key = 'expired_key';
        $value = 'expired_value';
        $this->cache->set($key, $value, -1); // Set to expire in the past

        $this->assertEquals(null, $this->cache->get($key));
    }

    public function testSet_SetsValue(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->assertTrue($this->cache->set($key, $value));

        $filePath = $this->cache->getFilePath($key);
        $this->assertFileExists($filePath);
        $this->assertEquals($value, file_get_contents($filePath));
    }

    public function testSet_SetsTtl(): void
    {
        $key = 'ttl_key';
        $value = 'ttl_value';
        $ttl = 3600;

        $this->cache->set($key, $value, $ttl);

        $filePath = $this->cache->getFilePath($key);
        $filemtime = fileatime($filePath);
        $this->assertGreaterThan(time(), $filemtime);
        $this->assertLessThanOrEqual($filemtime + $ttl, time() + $ttl);
    }

    public function testDelete_ExistingKey_RemovesFile(): void
    {
        $key = 'delete_key';
        $value = 'delete_value';
        $this->cache->set($key, $value);

        $this->assertTrue($this->cache->delete($key));

        $filePath = $this->cache->getFilePath($key);
        $this->assertFileDoesNotExist($filePath);
    }

    public function testClear_RemovesAllFiles(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        $this->cache->clear();

        $files = glob($this->cacheDir . '/*.cache');
        $this->assertEmpty($files);
    }

    public function testHas_ExistingKey_ReturnsTrue(): void
    {
        $key = 'has_key';
        $value = 'has_value';
        $this->cache->set($key, $value);

        $this->assertTrue($this->cache->has($key));
    }
}
