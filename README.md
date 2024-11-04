# Oopress Cache
[![PHP Composer](https://github.com/cmatosbc/oopress-cache/actions/workflows/php.yml/badge.svg)](https://github.com/cmatosbc/oopress-cache/actions/workflows/php.yml)

Oopress PHP PSR-16 cache drivers. Comes with the basic PSR compliant OOP approach possibility, but can also be used functionaly, from caching "high order functions" to be explained below. For now, it comes with drivers to manage file cache, a MySQL custom table cache adjusted for Wordpress, a Memcached and a Redis drivers. The classes can be used separatedly in any implementation, but also inside two high order functions: ```withCache()``` and ```withCachedQuery()```.

![OOPress](https://raw.githubusercontent.com/cmatosbc/oopress-cache/refs/heads/main/img/two.jpg)

## ```withCachedQuery()``` Function

This function is designed to cache the results of specific WordPress queries. It takes a cache interface, an expiration time, and a query type as input. It returns a closure that, when called with query arguments, will:

* Check the Cache: It generates a cache key based on the query arguments and checks if the result is already cached.
* Execute the Query: If the result isn't cached, it executes the specified query (WP_Query, WP_Term_Query, WP_Comment_Query, or a custom user query).
* Cache the Result: The result is serialized and cached with the generated cache key and expiration time.
* Return the Result: The cached or freshly fetched result is returned.

### Key Points:

* Caching Strategy: Caching is based on query arguments, ensuring that identical queries are not executed multiple times.
* Query Types: The function supports caching for various query types: posts, terms, users, and comments.
* Expiration: The $expires parameter can be a DateTime object or an integer representing seconds.
* Serialization: Results are serialized before caching and deserialized when retrieved.

### Example

```php
// Instantiate a FileCache instance
$cacheDir = '/path/to/cache/directory';
$cache = new FileCache($cacheDir);

// Cache a post query for 1 hour
$expireTime = new DateTime('+1 hour');
$cachedPostQuery = withCachedQuery($cache, $expireTime, 'post');

// Use the cached query
$posts = $cachedPostQuery([
    'post_type' => 'post',
    'posts_per_page' => 10,
]);
```

## ```withCache()``` Function

This function is a more generic caching mechanism that can be used to cache the results of any callable function or method. It takes a cache interface and an expiration time as input. It returns a closure that, when called with a callable and its arguments, will:

* Check the Cache: It generates a cache key based on the callable's reflection and arguments.
* Execute the Callable: If the result isn't cached, it executes the provided callable with the given arguments.
* Cache the Result: The result is serialized and cached with the generated cache key and expiration time.
* Return the Result: The cached or freshly computed result is returned.

### Key Points:

* Generic Caching: This function can be used to cache the results of any PHP function or method.
* Flexible Caching: The $process parameter can be a closure, a string (function name), or an array (method call).
* Cache Key Generation: Cache keys are generated based on the callable's reflection and arguments, ensuring unique keys for different calls.

### Example

```php
// Cache a custom function for 5 minutes
$expireTime = new DateTime('+5 minutes');
$cachedFunction = withCache($cache, $expireTime);

// Use the cached function
$result = $cachedFunction(function () {
    // Some expensive operation
    return slow_calculation();
});

// Cache a static method for 1 day
$expireTime = new DateTime('+1 day');
$cachedStaticMethod = withCache($cache, $expireTime);

// Use the cached static method
$result = $cachedStaticMethod([MyClass::class, 'staticMethod'], 'arg1', 'arg2');
```

## Use Cases:

* Performance Optimization: Reduce database queries and improve page load times by caching frequently used data.
* API Rate Limiting: Cache API responses to avoid exceeding rate limits.
* Reducing Server Load: Offload processing to the cache, especially for computationally expensive tasks.

By using these functions, you can significantly improve the performance of your WordPress applications.

## JS like syntax:

A JS like syntax can also be used with PHP closures, so the next arguments can be passed directly to the Closure, like this.

```php
// Cache a custom function for 5 minutes
$expireTime = new DateTime('+5 minutes');
$result = withCache($cache, $expireTime)(function () {
    return 'Hello, world!';
});
```
