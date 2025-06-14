<?php

namespace App\Core\Cache\Stores;

use App\Core\Cache\Contracts\CacheStoreInterface;

/**
 * Null Cache Store
 * 
 * Implements a null cache that doesn't store anything.
 * Useful for disabling caching or testing scenarios.
 */
class NullStore implements CacheStoreInterface
{
    /**
     * @var string Cache key prefix
     */
    protected $prefix;
    
    /**
     * @var array Cache statistics
     */
    protected $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0
    ];
    
    /**
     * Constructor
     * 
     * @param string $prefix Cache key prefix
     */
    public function __construct(string $prefix = '')
    {
        $this->prefix = $prefix;
    }
    
    /**
     * Get an item from the cache
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $this->stats['misses']++;
        return $default;
    }
    
    /**
     * Store an item in the cache
     * 
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        $this->stats['writes']++;
        return true;
    }
    
    /**
     * Store an item in the cache if it doesn't exist
     * 
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function add(string $key, $value, ?int $ttl = null): bool
    {
        $this->stats['writes']++;
        return true;
    }
    
    /**
     * Store an item in the cache indefinitely
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever(string $key, $value): bool
    {
        $this->stats['writes']++;
        return true;
    }
    
    /**
     * Get an item from the cache, or execute the given Closure and store the result
     * 
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $this->stats['misses']++;
        $this->stats['writes']++;
        return $callback();
    }
    
    /**
     * Get an item from the cache, or execute the given Closure and store the result forever
     * 
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function rememberForever(string $key, callable $callback)
    {
        return $this->remember($key, $callback, null);
    }
    
    /**
     * Remove an item from the cache
     * 
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $this->stats['deletes']++;
        return true;
    }
    
    /**
     * Remove all items from the cache
     * 
     * @return bool
     */
    public function flush(): bool
    {
        $this->stats['deletes']++;
        return true;
    }
    
    /**
     * Check if an item exists in the cache
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->stats['misses']++;
        return false;
    }
    
    /**
     * Increment the value of an item in the cache
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment(string $key, int $value = 1)
    {
        $this->stats['writes']++;
        return $value;
    }
    
    /**
     * Decrement the value of an item in the cache
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function decrement(string $key, int $value = 1)
    {
        $this->stats['writes']++;
        return -$value;
    }
    
    /**
     * Get multiple items from the cache
     * 
     * @param array $keys
     * @return array
     */
    public function many(array $keys): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = null;
            $this->stats['misses']++;
        }
        
        return $result;
    }
    
    /**
     * Store multiple items in the cache
     * 
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public function putMany(array $values, ?int $ttl = null): bool
    {
        $this->stats['writes'] += count($values);
        return true;
    }
    
    /**
     * Get the cache key prefix
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
    
    /**
     * Set the cache key prefix
     * 
     * @param string $prefix
     * @return void
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        return array_merge($this->stats, [
            'items' => 0,
            'memory_usage' => 0
        ]);
    }
    
    /**
     * Clear expired cache items
     * 
     * @return int Number of items cleared (always 0 for null store)
     */
    public function clearExpired(): int
    {
        return 0;
    }
}