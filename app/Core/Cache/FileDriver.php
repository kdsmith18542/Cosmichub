<?php

namespace App\Core\Cache;

use App\Core\Exceptions\ServiceException;

/**
 * File Cache Driver
 * 
 * Implements file-based caching
 */
class FileDriver implements CacheDriverInterface
{
    /**
     * @var string Cache directory path
     */
    protected $path;
    
    /**
     * @var string File extension
     */
    protected $extension = '.cache';
    
    /**
     * @var int Default file permissions
     */
    protected $filePermissions = 0644;
    
    /**
     * @var int Default directory permissions
     */
    protected $dirPermissions = 0755;
    
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
     * Create a new file cache driver
     * 
     * @param string|null $path
     */
    public function __construct($path = null)
    {
        $this->path = $path ?: $this->getDefaultPath();
        $this->ensureDirectoryExists($this->path);
    }
    
    /**
     * Get an item from the cache
     * 
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            $this->stats['misses']++;
            return null;
        }
        
        $contents = file_get_contents($file);
        if ($contents === false) {
            $this->stats['misses']++;
            return null;
        }
        
        $data = unserialize($contents);
        
        // Check if expired
        if ($data['expires'] > 0 && time() > $data['expires']) {
            $this->forget($key);
            $this->stats['misses']++;
            return null;
        }
        
        $this->stats['hits']++;
        return $data['value'];
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
        $file = $this->getFilePath($key);
        $directory = dirname($file);
        
        $this->ensureDirectoryExists($directory);
        
        $expires = $ttl > 0 ? time() + $ttl : 0;
        
        $data = [
            'value' => $value,
            'expires' => $expires,
            'created' => time()
        ];
        
        $result = file_put_contents($file, serialize($data), LOCK_EX);
        
        if ($result !== false) {
            chmod($file, $this->filePermissions);
            $this->stats['writes']++;
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove an item from the cache
     * 
     * @param string $key
     * @return bool
     */
    public function forget($key)
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
    public function flush()
    {
        $success = true;
        $files = glob($this->path . DIRECTORY_SEPARATOR . '*' . $this->extension);
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        // Also remove subdirectories
        $this->removeDirectoryContents($this->path);
        
        if ($success) {
            $this->stats = ['hits' => 0, 'misses' => 0, 'writes' => 0, 'deletes' => 0];
        }
        
        return $success;
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
     * Get multiple items from the cache
     * 
     * @param array $keys
     * @return array
     */
    public function many(array $keys)
    {
        $results = [];
        
        foreach ($keys as $key) {
            $results[$key] = $this->get($key);
        }
        
        return $results;
    }
    
    /**
     * Store multiple items in the cache
     * 
     * @param array $values
     * @param int $ttl
     * @return bool
     */
    public function putMany(array $values, $ttl = 3600)
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
     * Increment a cached value
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        $current = $this->get($key);
        
        if ($current === null) {
            $current = 0;
        }
        
        if (!is_numeric($current)) {
            return false;
        }
        
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
            'total_requests' => $total,
            'cache_size' => $this->getCacheSize(),
            'file_count' => $this->getFileCount()
        ]);
    }
    
    /**
     * Get the file path for a cache key
     * 
     * @param string $key
     * @return string
     */
    protected function getFilePath($key)
    {
        $hash = hash('sha256', $key);
        
        // Create subdirectories based on first two characters of hash
        $subdir = substr($hash, 0, 2);
        $filename = substr($hash, 2) . $this->extension;
        
        return $this->path . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR . $filename;
    }
    
    /**
     * Get the default cache path
     * 
     * @return string
     */
    protected function getDefaultPath()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'app_cache';
    }
    
    /**
     * Ensure directory exists
     * 
     * @param string $directory
     * @return void
     * @throws ServiceException
     */
    protected function ensureDirectoryExists($directory)
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, $this->dirPermissions, true)) {
                throw new ServiceException("Failed to create cache directory: {$directory}");
            }
        }
        
        if (!is_writable($directory)) {
            throw new ServiceException("Cache directory is not writable: {$directory}");
        }
    }
    
    /**
     * Remove directory contents recursively
     * 
     * @param string $directory
     * @return void
     */
    protected function removeDirectoryContents($directory)
    {
        $files = array_diff(scandir($directory), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                $this->removeDirectoryContents($path);
                rmdir($path);
            } else {
                unlink($path);
            }
        }
    }
    
    /**
     * Get total cache size in bytes
     * 
     * @return int
     */
    protected function getCacheSize()
    {
        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), $this->extension)) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    /**
     * Get total number of cache files
     * 
     * @return int
     */
    protected function getFileCount()
    {
        $count = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), $this->extension)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Clean expired cache files
     * 
     * @return int Number of files cleaned
     */
    public function cleanExpired()
    {
        $cleaned = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), $this->extension)) {
                $contents = file_get_contents($file->getPathname());
                if ($contents !== false) {
                    $data = unserialize($contents);
                    
                    if ($data['expires'] > 0 && time() > $data['expires']) {
                        unlink($file->getPathname());
                        $cleaned++;
                    }
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Set cache directory path
     * 
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;
        $this->ensureDirectoryExists($this->path);
        return $this;
    }
    
    /**
     * Get cache directory path
     * 
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * Set file permissions
     * 
     * @param int $permissions
     * @return $this
     */
    public function setFilePermissions($permissions)
    {
        $this->filePermissions = $permissions;
        return $this;
    }
    
    /**
     * Set directory permissions
     * 
     * @param int $permissions
     * @return $this
     */
    public function setDirectoryPermissions($permissions)
    {
        $this->dirPermissions = $permissions;
        return $this;
    }
}