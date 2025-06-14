<?php

namespace App\Core\Cache;

/**
 * Cache Driver Interface
 * 
 * Defines the contract for cache drivers
 */
interface CacheDriverInterface
{
    /**
     * Get an item from the cache
     * 
     * @param string $key
     * @return mixed|null
     */
    public function get($key);
    
    /**
     * Store an item in the cache
     * 
     * @param string $key
     * @param mixed $value
     * @param int $ttl Time to live in seconds (0 = no expiration)
     * @return bool
     */
    public function put($key, $value, $ttl = 3600);
    
    /**
     * Remove an item from the cache
     * 
     * @param string $key
     * @return bool
     */
    public function forget($key);
    
    /**
     * Remove all items from the cache
     * 
     * @return bool
     */
    public function flush();
    
    /**
     * Check if an item exists in the cache
     * 
     * @param string $key
     * @return bool
     */
    public function has($key);
    
    /**
     * Get multiple items from the cache
     * 
     * @param array $keys
     * @return array
     */
    public function many(array $keys);
    
    /**
     * Store multiple items in the cache
     * 
     * @param array $values
     * @param int $ttl
     * @return bool
     */
    public function putMany(array $values, $ttl = 3600);
    
    /**
     * Increment a cached value
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment($key, $value = 1);
    
    /**
     * Decrement a cached value
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function decrement($key, $value = 1);
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getStats();
}