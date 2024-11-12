<?php

namespace Oopress\Cache;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

trait Helpers
{
    public function isValidKey(string $key): void
    {
        if (!preg_match('/^[A-Za-z0-9_\-\.]+$/', $key)) {
            throw new InvalidArgumentException("Invalid key: $key");
        }
    }
}
