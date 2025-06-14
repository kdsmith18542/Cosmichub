<?php

namespace App\Core\Cache\Contracts;

/**
 * Cache Manager Interface
 * 
 * Defines the contract for cache managers that handle multiple cache stores.
 */
interface CacheManagerInterface
{
    /**
     * Get a cache store instance
     * 
     * @param string|null $name Store name
     * @return CacheStoreInterface
     */
    public function store(?string $name = null): CacheStoreInterface;
    
    /**
     * Get an item from the cache
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);
    
    /**
     * Store an item in the cache
     * 
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function put(string $key, $value, ?int $ttl = null): bool;
    
    /**
     * Store an item in the cache if it doesn't exist
     * 
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function add(string $key, $value, ?int $ttl = null): bool;
    
    /**
     * Store an item in the cache indefinitely
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever(string $key, $value): bool;
    
    /**
     * Get an item from the cache, or execute the given Closure and store the result
     * 
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null);
    
    /**
     * Get an item from the cache, or execute the given Closure and store the result forever
     * 
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function rememberForever(string $key, callable $callback);
    
    /**
     * Remove an item from the cache
     * 
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool;
    
    /**
     * Remove all items from the cache
     * 
     * @return bool
     */
    public function flush(): bool;
    
    /**
     * Check if an item exists in the cache
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;
    
    /**
     * Increment the value of an item in the cache
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment(string $key, int $value = 1);
    
    /**
     * Decrement the value of an item in the cache
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function decrement(string $key, int $value = 1);
    
    /**
     * Get multiple items from the cache
     * 
     * @param array $keys
     * @return array
     */
    public function many(array $keys): array;
    
    /**
     * Store multiple items in the cache
     * 
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public function putMany(array $values, ?int $ttl = null): bool;
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getStats(): array;
    
    /**
     * Get the cache key prefix
     * 
     * @return string
     */
    public function getPrefix(): string;
    
    /**
     * Set the cache key prefix
     * 
     * @param string $prefix
     * @return void
     */
    public function setPrefix(string $prefix): void;
}