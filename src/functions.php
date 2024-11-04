<?php

function withCachedQuery(
	\Psr\SimpleCache\CacheInterface $cache, 
	\DateTime|int $expires = 300, 
	string $type = 'post') : \Closure
{
    return function (array $queryArgs) use ($cache, $expires, $type) {
        $cacheKey = md5(serialize($queryArgs));

        if ($cachedResult = $cache->get($cacheKey)) {
            return unserialize($cachedResult);
        }

        if ($expires instanceof \DateTime) {
        	$now = new \DateTime();
        	$interval = $now->diff($expires);
	    	$expires = $interval->y * 31536000 + $interval->m * 2592000 + $interval->d * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
        }

        $query = match ($type) {
        	'post' => new \WP_Query($queryArgs),
        	'term' => new \WP_Term_Query($queryArgs),
        	'user' => serialize(get_users($queryArgs)),
        	'comment' => new \WP_Comment_Query($queryArgs),
        	default => null,
        };

        if ($type === 'user') {
        	$cache->set($cacheKey, $query, $expires);
        	return unserialize($cachedResult);
        }

        switch (get_class($query)) {
		    case 'WP_Query':
		        $cachedResult = serialize($query->get_posts());
		        break;
		    case 'WP_Term_Query':
		        $cachedResult = serialize($query->get_terms());
		        break;
		    case 'WP_Comment_Query':
		        $cachedResult = serialize($query->get_comments());
		        break;
		    default:
		        return false;
		        break;
		}

        $cache->set($cacheKey, $cachedResult, $expires);

        return unserialize($cachedResult);
    };
}

function withCache(
    \Psr\SimpleCache\CacheInterface $cache, 
    \DateTime|int $expires = 300) : \Closure
{
    return function (\Closure|array|string $process, ...$args) use ($cache, $expires) {

        if ($expires instanceof \DateTime) {
            $now = new \DateTime();
            $interval = $now->diff($expires);
            $expires = $interval->y * 31536000 + $interval->m * 2592000 + $interval->d * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
        }

        if ($process instanceof \Closure) {
            $cacheKey = md5(serialize((new \ReflectionFunction($process))->__toString()));
            if ($cachedResult = $cache->get($cacheKey)) {
                return unserialize($cachedResult);
            }
            $cachedResult = $process(...$args);
        }

        if (is_string($process)) {
            $cacheKey = md5(serialize((new \ReflectionFunction($process))->__toString() . $args));
            if ($cachedResult = $cache->get($cacheKey)) {
                return unserialize($cachedResult);
            }
            $cachedResult = call_user_func($process, ...$args);
        }

        if (is_array($process)) {
            $cacheKey = md5(serialize((new \ReflectionMethod(...$process))->__toString()) . serialize($args));
            if ($cachedResult = $cache->get($cacheKey)) {
                return unserialize($cachedResult);
            }
            $cachedResult = call_user_func_array($process, ...$args); 
        }

        $cache->set($cacheKey, serialize($cachedResult), $expires);

        return unserialize($cachedResult);
    };
}
