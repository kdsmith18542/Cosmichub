<?php

namespace App\Core\Cache;

use App\Core\Cache\Stores\FileStore;
use App\Core\Cache\Stores\ArrayStore;
use App\Core\Cache\Stores\NullStore;
use App\Core\Cache\Contracts\CacheStoreInterface;
use App\Core\Cache\Contracts\CacheManagerInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cache Manager
 * 
 * Provides centralized cache management with multiple stores and drivers.
 * Supports file-based, memory-based, and null caching strategies.
 */
class CacheManager implements CacheManagerInterface
{
    /**
     * @var array Cache store instances
     */
    protected $stores = [];
    
    /**
     * @var array Cache store configurations
     */
    protected $config = [];
    
    /**
     * @var string Default cache store
     */
    protected $defaultStore = 'file';
    
    /**
     * @var string Cache key prefix
     */
    protected $prefix = '';
    
    /**
     * Constructor
     * 
     * @param array $config Cache configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultStore = $config['default'] ?? 'file';
        $this->prefix = $config['prefix'] ?? '';
    }
    
    /**
     * Get a cache store instance
     * 
     * @param string|null $name Store name
     * @return CacheStoreInterface
     */
    public function store(?string $name = null): CacheStoreInterface
    {
        $name = $name ?: $this->defaultStore;
        
        if (!isset($this->stores[$name])) {
            $this->stores[$name] = $this->createStore($name);
        }
        
        return $this->stores[$name];
    }
    
    /**
     * Create a cache store instance
     * 
     * @param string $name Store name
     * @return CacheStoreInterface
     * @throws InvalidArgumentException
     */
    protected function createStore(string $name): CacheStoreInterface
    {
        $config = $this->config['stores'][$name] ?? null;
        
        if (!$config) {
            throw new InvalidArgumentException("Cache store [{$name}] not configured.");
        }
        
        $driver = $config['driver'] ?? 'file';
        
        switch ($driver) {
            case 'file':
                return $this->createFileStore($config);
            case 'array':
                return $this->createArrayStore($config);
            case 'null':
                return $this->createNullStore($config);
            default:
                throw new InvalidArgumentException("Cache driver [{$driver}] not supported.");
        }
    }
    
    /**
     * Create file cache store
     * 
     * @param array $config
     * @return FileStore
     */
    protected function createFileStore(array $config): FileStore
    {
        $path = $config['path'] ?? storage_path('cache');
        $permissions = $config['permissions'] ?? 0755;
        
        return new FileStore($path, $this->prefix, $permissions);
    }
    
    /**
     * Create array cache store
     * 
     * @param array $config
     * @return ArrayStore
     */
    protected function createArrayStore(array $config): ArrayStore
    {
        $maxItems = $config['max_items'] ?? 1000;
        
        return new ArrayStore($this->prefix, $maxItems);
    }
    
    /**
     * Create null cache store
     * 
     * @param array $config
     * @return NullStore
     */
    protected function createNullStore(array $config): NullStore
    {
        return new NullStore();
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
        return $this->store()->get($key, $default);
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
        return $this->store()->put($key, $value, $ttl);
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
        return $this->store()->add($key, $value, $ttl);
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
        return $this->store()->forever($key, $value);
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
        return $this->store()->remember($key, $callback, $ttl);
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
        return $this->store()->rememberForever($key, $callback);
    }
    
    /**
     * Remove an item from the cache
     * 
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return $this->store()->forget($key);
    }
    
    /**
     * Remove all items from the cache
     * 
     * @return bool
     */
    public function flush(): bool
    {
        return $this->store()->flush();
    }
    
    /**
     * Check if an item exists in the cache
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->store()->has($key);
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
        return $this->store()->increment($key, $value);
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
        return $this->store()->decrement($key, $value);
    }
    
    /**
     * Get multiple items from the cache
     * 
     * @param array $keys
     * @return array
     */
    public function many(array $keys): array
    {
        return $this->store()->many($keys);
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
        return $this->store()->putMany($values, $ttl);
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        $store = $this->store();
        
        if (method_exists($store, 'getStats')) {
            return $store->getStats();
        }
        
        return [];
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
        
        // Update existing stores
        foreach ($this->stores as $store) {
            if (method_exists($store, 'setPrefix')) {
                $store->setPrefix($prefix);
            }
        }
    }
    
    /**
     * Get all configured store names
     * 
     * @return array
     */
    public function getStoreNames(): array
    {
        return array_keys($this->config['stores'] ?? []);
    }
    
    /**
     * Clear expired items from all stores
     * 
     * @return void
     */
    public function clearExpired(): void
    {
        foreach ($this->stores as $store) {
            if (method_exists($store, 'clearExpired')) {
                $store->clearExpired();
            }
        }
    }
}