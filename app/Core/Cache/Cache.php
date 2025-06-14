<?php

namespace App\Core\Cache;

use App\Core\Container\Container;
use App\Core\Logging\Logger;
use App\Core\Exceptions\ServiceException;

/**
 * Cache Class
 * 
 * Provides caching functionality for the application
 */
class Cache
{
    /**
     * @var Container The application container
     */
    protected $container;
    
    /**
     * @var Logger The logger instance
     */
    protected $logger;
    
    /**
     * @var CacheDriverInterface The cache driver
     */
    protected $driver;
    
    /**
     * @var string Default cache prefix
     */
    protected $prefix = 'app_cache:';
    
    /**
     * @var int Default TTL in seconds
     */
    protected $defaultTtl = 3600;
    
    /**
     * @var array Cache tags
     */
    protected $tags = [];
    
    /**
     * @var bool Whether caching is enabled
     */
    protected $enabled = true;
    
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
     * Create a new cache instance
     * 
     * @param Container $container
     * @param CacheDriverInterface|null $driver
     */
    public function __construct(Container $container, CacheDriverInterface $driver = null)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
        $this->driver = $driver ?: new FileDriver();
        
        // Load configuration
        $config = $container->get('config');
        $this->prefix = $config->get('cache.prefix', $this->prefix);
        $this->defaultTtl = $config->get('cache.default_ttl', $this->defaultTtl);
        $this->enabled = $config->get('cache.enabled', $this->enabled);
    }
    
    /**
     * Get an item from the cache
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!$this->enabled) {
            return $default;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            $value = $this->driver->get($fullKey);
            
            if ($value !== null) {
                $this->stats['hits']++;
                $this->logOperation('get', $key, true);
                return $this->unserialize($value);
            }
            
            $this->stats['misses']++;
            $this->logOperation('get', $key, false);
            return $default;
        } catch (\Exception $e) {
            $this->logger->error("Cache get failed for key '{$key}': {$e->getMessage()}");
            return $default;
        }
    }
    
    /**
     * Store an item in the cache
     * 
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put($key, $value, $ttl = null)
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            $ttl = $ttl ?: $this->defaultTtl;
            $serialized = $this->serialize($value);
            
            $result = $this->driver->put($fullKey, $serialized, $ttl);
            
            if ($result) {
                $this->stats['writes']++;
                $this->logOperation('put', $key, true);
                
                // Store tags if any
                if (!empty($this->tags)) {
                    $this->storeTags($key, $this->tags);
                    $this->tags = [];
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Cache put failed for key '{$key}': {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Store an item in the cache forever
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0); // 0 means no expiration
    }
    
    /**
     * Get an item from cache or store the result of a callback
     * 
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember($key, callable $callback, $ttl = null)
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
     * Get an item from cache or store the result of a callback forever
     * 
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function rememberForever($key, callable $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->forever($key, $value);
        
        return $value;
    }
    
    /**
     * Remove an item from the cache
     * 
     * @param string $key
     * @return bool
     */
    public function forget($key)
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullKey = $this->buildKey($key);
            $result = $this->driver->forget($fullKey);
            
            if ($result) {
                $this->stats['deletes']++;
                $this->logOperation('forget', $key, true);
                $this->forgetTags($key);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Cache forget failed for key '{$key}': {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Remove all items from the cache
     * 
     * @return bool
     */
    public function flush()
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $result = $this->driver->flush();
            
            if ($result) {
                $this->logOperation('flush', 'all', true);
                $this->stats = ['hits' => 0, 'misses' => 0, 'writes' => 0, 'deletes' => 0];
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Cache flush failed: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Check if an item exists in the cache
     * 
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Increment a cached value
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        if (!$this->enabled) {
            return false;
        }
        
        $current = $this->get($key, 0);
        $new = $current + $value;
        
        if ($this->put($key, $new)) {
            return $new;
        }
        
        return false;
    }
    
    /**
     * Decrement a cached value
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
     * Set cache tags for the next operation
     * 
     * @param array|string $tags
     * @return $this
     */
    public function tags($tags)
    {
        $this->tags = is_array($tags) ? $tags : [$tags];
        return $this;
    }
    
    /**
     * Flush cache items by tags
     * 
     * @param array|string $tags
     * @return bool
     */
    public function flushTags($tags)
    {
        if (!$this->enabled) {
            return false;
        }
        
        $tags = is_array($tags) ? $tags : [$tags];
        $success = true;
        
        foreach ($tags as $tag) {
            $keys = $this->getKeysByTag($tag);
            foreach ($keys as $key) {
                if (!$this->forget($key)) {
                    $success = false;
                }
            }
            $this->forgetTag($tag);
        }
        
        return $success;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getStats()
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0;
        
        return array_merge($this->stats, [
            'hit_rate' => round($hitRate, 2),
            'total_requests' => $total
        ]);
    }
    
    /**
     * Enable caching
     * 
     * @return $this
     */
    public function enable()
    {
        $this->enabled = true;
        return $this;
    }
    
    /**
     * Disable caching
     * 
     * @return $this
     */
    public function disable()
    {
        $this->enabled = false;
        return $this;
    }
    
    /**
     * Check if caching is enabled
     * 
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
    
    /**
     * Set the cache driver
     * 
     * @param CacheDriverInterface $driver
     * @return $this
     */
    public function setDriver(CacheDriverInterface $driver)
    {
        $this->driver = $driver;
        return $this;
    }
    
    /**
     * Get the cache driver
     * 
     * @return CacheDriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }
    
    /**
     * Set the cache prefix
     * 
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }
    
    /**
     * Get the cache prefix
     * 
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
    
    /**
     * Build the full cache key
     * 
     * @param string $key
     * @return string
     */
    protected function buildKey($key)
    {
        return $this->prefix . $key;
    }
    
    /**
     * Serialize a value for storage
     * 
     * @param mixed $value
     * @return string
     */
    protected function serialize($value)
    {
        return serialize($value);
    }
    
    /**
     * Unserialize a value from storage
     * 
     * @param string $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return unserialize($value);
    }
    
    /**
     * Store tags for a cache key
     * 
     * @param string $key
     * @param array $tags
     * @return void
     */
    protected function storeTags($key, array $tags)
    {
        foreach ($tags as $tag) {
            $tagKey = $this->buildKey("tag:{$tag}");
            $keys = $this->driver->get($tagKey);
            $keys = $keys ? unserialize($keys) : [];
            
            if (!in_array($key, $keys)) {
                $keys[] = $key;
                $this->driver->put($tagKey, serialize($keys), 0);
            }
        }
    }
    
    /**
     * Get keys by tag
     * 
     * @param string $tag
     * @return array
     */
    protected function getKeysByTag($tag)
    {
        $tagKey = $this->buildKey("tag:{$tag}");
        $keys = $this->driver->get($tagKey);
        return $keys ? unserialize($keys) : [];
    }
    
    /**
     * Remove tags for a cache key
     * 
     * @param string $key
     * @return void
     */
    protected function forgetTags($key)
    {
        // This is a simplified implementation
        // In a real scenario, you'd need to track which tags a key belongs to
    }
    
    /**
     * Remove a tag
     * 
     * @param string $tag
     * @return void
     */
    protected function forgetTag($tag)
    {
        $tagKey = $this->buildKey("tag:{$tag}");
        $this->driver->forget($tagKey);
    }
    
    /**
     * Log cache operation
     * 
     * @param string $operation
     * @param string $key
     * @param bool $success
     * @return void
     */
    protected function logOperation($operation, $key, $success)
    {
        $status = $success ? 'success' : 'failure';
        $this->logger->debug("Cache {$operation} {$status}: {$key}");
    }
    
    /**
     * Create a cache instance with specific driver
     * 
     * @param Container $container
     * @param string $driver
     * @return static
     */
    public static function driver(Container $container, $driver = 'file')
    {
        $driverClass = match($driver) {
            'file' => FileDriver::class,
            'memory' => MemoryDriver::class,
            'redis' => RedisDriver::class,
            default => throw new ServiceException("Unsupported cache driver: {$driver}")
        };
        
        $driverInstance = $container->make($driverClass);
        return new static($container, $driverInstance);
    }
}