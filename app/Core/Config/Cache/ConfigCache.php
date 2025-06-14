<?php

namespace App\Core\Config\Cache;

use App\Core\Application;
use App\Core\Exceptions\ConfigException;

/**
 * Configuration Cache
 * 
 * Handles caching of configuration data for improved performance
 */
class ConfigCache
{
    /**
     * The application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * Cache file path
     *
     * @var string
     */
    protected $cachePath;

    /**
     * Cache file name
     *
     * @var string
     */
    protected $cacheFile = 'config.cache';

    /**
     * Create a new config cache instance
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->cachePath = $this->determineCachePath();
    }

    /**
     * Determine the cache path
     *
     * @return string
     */
    protected function determineCachePath()
    {
        $basePath = $this->app->getBasePath();
        
        // Try storage/cache first
        $storagePath = $basePath . '/storage/cache';
        if (is_dir($storagePath) && is_writable($storagePath)) {
            return $storagePath;
        }
        
        // Try storage directory
        $storageDir = $basePath . '/storage';
        if (is_dir($storageDir) && is_writable($storageDir)) {
            return $storageDir;
        }
        
        // Fallback to temp directory
        return sys_get_temp_dir();
    }

    /**
     * Get the full cache file path
     *
     * @return string
     */
    protected function getCacheFilePath()
    {
        return $this->cachePath . DIRECTORY_SEPARATOR . $this->cacheFile;
    }

    /**
     * Check if cache exists and is valid
     *
     * @return bool
     */
    public function exists()
    {
        $cacheFile = $this->getCacheFilePath();
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        // Check if cache is still valid (not expired)
        return $this->isValid();
    }

    /**
     * Check if cache is valid (not expired)
     *
     * @return bool
     */
    public function isValid()
    {
        $cacheFile = $this->getCacheFilePath();
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $cacheTime = filemtime($cacheFile);
        $configTime = $this->getConfigModificationTime();
        
        return $cacheTime >= $configTime;
    }

    /**
     * Get the latest modification time of configuration files
     *
     * @return int
     */
    protected function getConfigModificationTime()
    {
        $basePath = $this->app->getBasePath();
        $latestTime = 0;
        
        // Check main config directory
        $configDir = $basePath . '/config';
        if (is_dir($configDir)) {
            $latestTime = max($latestTime, $this->getDirectoryModificationTime($configDir));
        }
        
        // Check environment-specific config directories
        $envDir = $configDir . '/environments';
        if (is_dir($envDir)) {
            $latestTime = max($latestTime, $this->getDirectoryModificationTime($envDir));
        }
        
        // Check local config directory
        $localDir = $configDir . '/local';
        if (is_dir($localDir)) {
            $latestTime = max($latestTime, $this->getDirectoryModificationTime($localDir));
        }
        
        // Check .env files
        $envFiles = [
            $basePath . '/.env',
            $basePath . '/.env.local',
            $basePath . '/.env.' . ($_ENV['APP_ENV'] ?? 'production'),
        ];
        
        foreach ($envFiles as $envFile) {
            if (file_exists($envFile)) {
                $latestTime = max($latestTime, filemtime($envFile));
            }
        }
        
        return $latestTime;
    }

    /**
     * Get directory modification time (latest file modification)
     *
     * @param string $directory
     * @return int
     */
    protected function getDirectoryModificationTime($directory)
    {
        if (!is_dir($directory)) {
            return 0;
        }
        
        $latestTime = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $latestTime = max($latestTime, $file->getMTime());
            }
        }
        
        return $latestTime;
    }

    /**
     * Get cached configuration
     *
     * @return array
     * @throws ConfigException
     */
    public function get()
    {
        $cacheFile = $this->getCacheFilePath();
        
        if (!file_exists($cacheFile)) {
            throw new ConfigException("Configuration cache file not found: {$cacheFile}");
        }
        
        $content = file_get_contents($cacheFile);
        
        if ($content === false) {
            throw new ConfigException("Failed to read configuration cache file: {$cacheFile}");
        }
        
        $data = unserialize($content);
        
        if ($data === false) {
            throw new ConfigException("Failed to unserialize configuration cache: {$cacheFile}");
        }
        
        return $data;
    }

    /**
     * Store configuration in cache
     *
     * @param array $config
     * @return bool
     * @throws ConfigException
     */
    public function put(array $config)
    {
        $cacheFile = $this->getCacheFilePath();
        
        // Ensure cache directory exists
        $this->ensureCacheDirectoryExists();
        
        // Serialize configuration data
        $serialized = serialize($config);
        
        // Write to temporary file first for atomic operation
        $tempFile = $cacheFile . '.tmp';
        $result = file_put_contents($tempFile, $serialized, LOCK_EX);
        
        if ($result === false) {
            throw new ConfigException("Failed to write configuration cache: {$tempFile}");
        }
        
        // Atomically move temporary file to cache file
        if (!rename($tempFile, $cacheFile)) {
            unlink($tempFile);
            throw new ConfigException("Failed to move configuration cache: {$cacheFile}");
        }
        
        return true;
    }

    /**
     * Delete cached configuration
     *
     * @return bool
     */
    public function forget()
    {
        $cacheFile = $this->getCacheFilePath();
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }

    /**
     * Ensure cache directory exists
     *
     * @return void
     * @throws ConfigException
     */
    protected function ensureCacheDirectoryExists()
    {
        if (!is_dir($this->cachePath)) {
            if (!mkdir($this->cachePath, 0755, true)) {
                throw new ConfigException("Failed to create cache directory: {$this->cachePath}");
            }
        }
        
        if (!is_writable($this->cachePath)) {
            throw new ConfigException("Cache directory is not writable: {$this->cachePath}");
        }
    }

    /**
     * Get cache file size
     *
     * @return int
     */
    public function getSize()
    {
        $cacheFile = $this->getCacheFilePath();
        
        if (!file_exists($cacheFile)) {
            return 0;
        }
        
        return filesize($cacheFile);
    }

    /**
     * Get cache file modification time
     *
     * @return int
     */
    public function getModificationTime()
    {
        $cacheFile = $this->getCacheFilePath();
        
        if (!file_exists($cacheFile)) {
            return 0;
        }
        
        return filemtime($cacheFile);
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getStats()
    {
        $cacheFile = $this->getCacheFilePath();
        
        return [
            'exists' => file_exists($cacheFile),
            'valid' => $this->isValid(),
            'size' => $this->getSize(),
            'modified' => $this->getModificationTime(),
            'path' => $cacheFile,
        ];
    }

    /**
     * Clear all configuration cache
     *
     * @return bool
     */
    public function clear()
    {
        return $this->forget();
    }

    /**
     * Warm up the cache with given configuration
     *
     * @param array $config
     * @return bool
     */
    public function warmUp(array $config)
    {
        return $this->put($config);
    }
}