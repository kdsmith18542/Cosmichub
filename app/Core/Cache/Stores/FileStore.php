<?php

namespace App\Core\Cache\Stores;

use App\Core\Cache\Contracts\CacheStoreInterface;
use RuntimeException;
use InvalidArgumentException;

/**
 * File Cache Store
 * 
 * Implements file-based caching with expiration support and atomic operations.
 */
class FileStore implements CacheStoreInterface
{
    /**
     * @var string Cache directory path
     */
    protected $path;
    
    /**
     * @var string Cache key prefix
     */
    protected $prefix;
    
    /**
     * @var int Directory permissions
     */
    protected $permissions;
    
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
     * @param string $path Cache directory path
     * @param string $prefix Cache key prefix
     * @param int $permissions Directory permissions
     */
    public function __construct(string $path, string $prefix = '', int $permissions = 0755)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->prefix = $prefix;
        $this->permissions = $permissions;
        
        $this->ensureDirectoryExists();
    }
    
    /**
     * Ensure the cache directory exists
     * 
     * @return void
     * @throws RuntimeException
     */
    protected function ensureDirectoryExists(): void
    {
        if (!is_dir($this->path)) {
            if (!mkdir($this->path, $this->permissions, true)) {
                throw new RuntimeException("Unable to create cache directory: {$this->path}");
            }
        }
        
        if (!is_writable($this->path)) {
            throw new RuntimeException("Cache directory is not writable: {$this->path}");
        }
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
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            $this->stats['misses']++;
            return $default;
        }
        
        $contents = file_get_contents($file);
        if ($contents === false) {
            $this->stats['misses']++;
            return $default;
        }
        
        $data = unserialize($contents);
        
        // Check if expired
        if ($data['expires'] !== null && $data['expires'] < time()) {
            $this->forget($key);
            $this->stats['misses']++;
            return $default;
        }
        
        $this->stats['hits']++;
        return $data['value'];
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
        $file = $this->getFilePath($key);
        $expires = $ttl ? time() + $ttl : null;
        
        $data = [
            'value' => $value,
            'expires' => $expires,
            'created' => time()
        ];
        
        $result = $this->writeFile($file, serialize($data));
        
        if ($result) {
            $this->stats['writes']++;
        }
        
        return $result;
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
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            $result = unlink($file);
            if ($result) {
                $this->stats['deletes']++;
            }
            return $result;
        }
        
        return true;
    }
    
    /**
     * Remove all items from the cache
     * 
     * @return bool
     */
    public function flush(): bool
    {
        $files = glob($this->path . DIRECTORY_SEPARATOR . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $this->stats['deletes']++;
            }
        }
        
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
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!$this->put($key, $value, $ttl)) {
                $success = false;
            }
        }
        
        return $success;
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
        return $this->stats;
    }
    
    /**
     * Clear expired cache items
     * 
     * @return int Number of items cleared
     */
    public function clearExpired(): int
    {
        $cleared = 0;
        $files = glob($this->path . DIRECTORY_SEPARATOR . '*');
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $contents = file_get_contents($file);
            if ($contents === false) {
                continue;
            }
            
            $data = unserialize($contents);
            
            if ($data['expires'] !== null && $data['expires'] < time()) {
                unlink($file);
                $cleared++;
                $this->stats['deletes']++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Get file path for a cache key
     * 
     * @param string $key
     * @return string
     */
    protected function getFilePath(string $key): string
    {
        $key = $this->prefix . $key;
        $hash = hash('sha256', $key);
        
        return $this->path . DIRECTORY_SEPARATOR . $hash;
    }
    
    /**
     * Write data to file atomically
     * 
     * @param string $file
     * @param string $data
     * @return bool
     */
    protected function writeFile(string $file, string $data): bool
    {
        $temp = $file . '.tmp.' . uniqid();
        
        if (file_put_contents($temp, $data, LOCK_EX) === false) {
            return false;
        }
        
        return rename($temp, $file);
    }
}