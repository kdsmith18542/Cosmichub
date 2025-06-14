<?php

namespace App\Core\Cache;

/**
 * Memory Driver
 * 
 * In-memory cache driver for temporary storage during request lifecycle
 */
class MemoryDriver implements CacheDriverInterface
{
    /**
     * @var array Cache storage
     */
    protected $cache = [];
    
    /**
     * @var array Cache expiration times
     */
    protected $expiration = [];
    
    /**
     * @var array Statistics
     */
    protected $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0
    ];
    
    /**
     * Get an item from the cache
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $this->cleanExpired();
        
        if (!$this->has($key)) {
            $this->stats['misses']++;
            return null;
        }
        
        $this->stats['hits']++;
        return $this->cache[$key];
    }
    
    /**
     * Store an item in the cache
     * 
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function put($key, $value, $ttl = 3600)
    {
        $this->cache[$key] = $value;
        
        if ($ttl > 0) {
            $this->expiration[$key] = time() + $ttl;
        } else {
            unset($this->expiration[$key]);
        }
        
        $this->stats['sets']++;
        return true;
    }
    
    /**
     * Remove an item from the cache
     * 
     * @param string $key
     * @return bool
     */
    public function forget($key)
    {
        $existed = isset($this->cache[$key]);
        
        unset($this->cache[$key]);
        unset($this->expiration[$key]);
        
        if ($existed) {
            $this->stats['deletes']++;
        }
        
        return $existed;
    }
    
    /**
     * Clear all items from the cache
     * 
     * @return bool
     */
    public function flush()
    {
        $this->cache = [];
        $this->expiration = [];
        return true;
    }
    
    /**
     * Check if an item exists in the cache
     * 
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        if (!isset($this->cache[$key])) {
            return false;
        }
        
        // Check if expired
        if (isset($this->expiration[$key]) && $this->expiration[$key] < time()) {
            $this->forget($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get multiple items from the cache
     * 
     * @param array $keys
     * @return array
     */
    public function getMany(array $keys)
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
     * @param array $items
     * @param int $ttl
     * @return bool
     */
    public function putMany(array $items, $ttl = 3600)
    {
        foreach ($items as $key => $value) {
            $this->put($key, $value, $ttl);
        }
        
        return true;
    }
    
    /**
     * Increment a numeric cache value
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        if (!$this->has($key)) {
            $this->put($key, $value);
            return $value;
        }
        
        $current = $this->get($key);
        
        if (!is_numeric($current)) {
            return false;
        }
        
        $newValue = $current + $value;
        $this->put($key, $newValue, $this->getRemainingTtl($key));
        
        return $newValue;
    }
    
    /**
     * Decrement a numeric cache value
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, -$value);
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getStats()
    {
        return array_merge($this->stats, [
            'size' => count($this->cache),
            'memory_usage' => $this->getMemoryUsage()
        ]);
    }
    
    /**
     * Get all cache keys
     * 
     * @return array
     */
    public function getKeys()
    {
        $this->cleanExpired();
        return array_keys($this->cache);
    }
    
    /**
     * Get cache size
     * 
     * @return int
     */
    public function getSize()
    {
        $this->cleanExpired();
        return count($this->cache);
    }
    
    /**
     * Get memory usage estimate
     * 
     * @return int
     */
    public function getMemoryUsage()
    {
        return strlen(serialize($this->cache)) + strlen(serialize($this->expiration));
    }
    
    /**
     * Get remaining TTL for a key
     * 
     * @param string $key
     * @return int
     */
    public function getRemainingTtl($key)
    {
        if (!isset($this->expiration[$key])) {
            return -1; // No expiration
        }
        
        $remaining = $this->expiration[$key] - time();
        return max(0, $remaining);
    }
    
    /**
     * Set expiration for a key
     * 
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function expire($key, $ttl)
    {
        if (!$this->has($key)) {
            return false;
        }
        
        if ($ttl > 0) {
            $this->expiration[$key] = time() + $ttl;
        } else {
            unset($this->expiration[$key]);
        }
        
        return true;
    }
    
    /**
     * Remove expiration from a key
     * 
     * @param string $key
     * @return bool
     */
    public function persist($key)
    {
        if (!$this->has($key)) {
            return false;
        }
        
        unset($this->expiration[$key]);
        return true;
    }
    
    /**
     * Get all cache data (for debugging)
     * 
     * @return array
     */
    public function getAllData()
    {
        $this->cleanExpired();
        return [
            'cache' => $this->cache,
            'expiration' => $this->expiration,
            'stats' => $this->getStats()
        ];
    }
    
    /**
     * Clear expired items
     * 
     * @return int Number of items cleared
     */
    public function cleanExpired()
    {
        $cleared = 0;
        $now = time();
        
        foreach ($this->expiration as $key => $expireTime) {
            if ($expireTime < $now) {
                $this->forget($key);
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Reset statistics
     * 
     * @return $this
     */
    public function resetStats()
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0
        ];
        
        return $this;
    }
    
    /**
     * Get hit ratio
     * 
     * @return float
     */
    public function getHitRatio()
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        
        if ($total === 0) {
            return 0.0;
        }
        
        return $this->stats['hits'] / $total;
    }
    
    /**
     * Check if cache is empty
     * 
     * @return bool
     */
    public function isEmpty()
    {
        $this->cleanExpired();
        return empty($this->cache);
    }
    
    /**
     * Get cache contents as array
     * 
     * @return array
     */
    public function toArray()
    {
        $this->cleanExpired();
        return $this->cache;
    }
    
    /**
     * Import cache data
     * 
     * @param array $data
     * @param bool $merge
     * @return $this
     */
    public function import(array $data, $merge = false)
    {
        if (!$merge) {
            $this->flush();
        }
        
        foreach ($data as $key => $value) {
            $this->put($key, $value);
        }
        
        return $this;
    }
    
    /**
     * Export cache data
     * 
     * @return array
     */
    public function export()
    {
        $this->cleanExpired();
        
        return [
            'cache' => $this->cache,
            'expiration' => $this->expiration,
            'timestamp' => time()
        ];
    }
}