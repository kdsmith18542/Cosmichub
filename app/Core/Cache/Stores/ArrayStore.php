<?php

namespace App\Core\Cache\Stores;

use App\Core\Cache\Contracts\CacheStoreInterface;

/**
 * Array Cache Store
 * 
 * Implements in-memory caching using PHP arrays with LRU eviction policy.
 */
class ArrayStore implements CacheStoreInterface
{
    /**
     * @var array Cache data storage
     */
    protected $storage = [];
    
    /**
     * @var string Cache key prefix
     */
    protected $prefix;
    
    /**
     * @var int Maximum number of items to store
     */
    protected $maxItems;
    
    /**
     * @var array Access order for LRU eviction
     */
    protected $accessOrder = [];
    
    /**
     * @var array Cache statistics
     */
    protected $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0,
        'evictions' => 0
    ];
    
    /**
     * Constructor
     * 
     * @param string $prefix Cache key prefix
     * @param int $maxItems Maximum number of items to store
     */
    public function __construct(string $prefix = '', int $maxItems = 1000)
    {
        $this->prefix = $prefix;
        $this->maxItems = $maxItems;
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
        $key = $this->prefixKey($key);
        
        if (!isset($this->storage[$key])) {
            $this->stats['misses']++;
            return $default;
        }
        
        $item = $this->storage[$key];
        
        // Check if expired
        if ($item['expires'] !== null && $item['expires'] < time()) {
            $this->forgetInternal($key);
            $this->stats['misses']++;
            return $default;
        }
        
        // Update access order for LRU
        $this->updateAccessOrder($key);
        
        $this->stats['hits']++;
        return $item['value'];
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
        $key = $this->prefixKey($key);
        $expires = $ttl ? time() + $ttl : null;
        
        // Check if we need to evict items
        if (!isset($this->storage[$key]) && count($this->storage) >= $this->maxItems) {
            $this->evictLeastRecentlyUsed();
        }
        
        $this->storage[$key] = [
            'value' => $value,
            'expires' => $expires,
            'created' => time()
        ];
        
        $this->updateAccessOrder($key);
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
        if ($this->has($key)) {
            return false;
        }
        
        return $this->put($key, $value, $ttl);
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
        return $this->put($key, $value, null);
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
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->put($key, $value, $ttl);
        
        return $value;
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
        $key = $this->prefixKey($key);
        return $this->forgetInternal($key);
    }
    
    /**
     * Remove an item from the cache (internal method)
     * 
     * @param string $key Prefixed key
     * @return bool
     */
    protected function forgetInternal(string $key): bool
    {
        if (isset($this->storage[$key])) {
            unset($this->storage[$key]);
            $this->removeFromAccessOrder($key);
            $this->stats['deletes']++;
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove all items from the cache
     * 
     * @return bool
     */
    public function flush(): bool
    {
        $count = count($this->storage);
        $this->storage = [];
        $this->accessOrder = [];
        $this->stats['deletes'] += $count;
        
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
        return $this->get($key) !== null;
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
        $current = $this->get($key, 0);
        
        if (!is_numeric($current)) {
            return false;
        }
        
        $new = $current + $value;
        $this->forever($key, $new);
        
        return $new;
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
        return $this->increment($key, -$value);
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
            $result[$key] = $this->get($key);
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
        foreach ($values as $key => $value) {
            $this->put($key, $value, $ttl);
        }
        
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
            'items' => count($this->storage),
            'max_items' => $this->maxItems,
            'memory_usage' => $this->getMemoryUsage()
        ]);
    }
    
    /**
     * Clear expired cache items
     * 
     * @return int Number of items cleared
     */
    public function clearExpired(): int
    {
        $cleared = 0;
        $now = time();
        
        foreach ($this->storage as $key => $item) {
            if ($item['expires'] !== null && $item['expires'] < $now) {
                $this->forgetInternal($key);
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Get all cache keys
     * 
     * @return array
     */
    public function getKeys(): array
    {
        $keys = [];
        $prefixLength = strlen($this->prefix);
        
        foreach (array_keys($this->storage) as $key) {
            if ($prefixLength > 0 && strpos($key, $this->prefix) === 0) {
                $keys[] = substr($key, $prefixLength);
            } else {
                $keys[] = $key;
            }
        }
        
        return $keys;
    }
    
    /**
     * Prefix a cache key
     * 
     * @param string $key
     * @return string
     */
    protected function prefixKey(string $key): string
    {
        return $this->prefix . $key;
    }
    
    /**
     * Update access order for LRU eviction
     * 
     * @param string $key
     * @return void
     */
    protected function updateAccessOrder(string $key): void
    {
        // Remove from current position
        $this->removeFromAccessOrder($key);
        
        // Add to end (most recently used)
        $this->accessOrder[] = $key;
    }
    
    /**
     * Remove key from access order
     * 
     * @param string $key
     * @return void
     */
    protected function removeFromAccessOrder(string $key): void
    {
        $index = array_search($key, $this->accessOrder);
        if ($index !== false) {
            array_splice($this->accessOrder, $index, 1);
        }
    }
    
    /**
     * Evict least recently used item
     * 
     * @return void
     */
    protected function evictLeastRecentlyUsed(): void
    {
        if (empty($this->accessOrder)) {
            return;
        }
        
        $lruKey = array_shift($this->accessOrder);
        unset($this->storage[$lruKey]);
        $this->stats['evictions']++;
    }
    
    /**
     * Get estimated memory usage
     * 
     * @return int Memory usage in bytes
     */
    protected function getMemoryUsage(): int
    {
        return strlen(serialize($this->storage));
    }
}