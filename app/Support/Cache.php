<?php

namespace App\Support;

/**
 * Cache helper class
 */
class Cache
{
    /**
     * The cache store
     *
     * @var array
     */
    protected static array $store = [];

    /**
     * The cache file directory
     *
     * @var string
     */
    protected static string $directory = '';

    /**
     * Set the cache directory
     *
     * @param string $directory
     * @return void
     */
    public static function setDirectory(string $directory): void
    {
        static::$directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        
        if (!is_dir(static::$directory)) {
            mkdir(static::$directory, 0755, true);
        }
    }

    /**
     * Store an item in the cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public static function put(string $key, $value, int $ttl = null): bool
    {
        $expiry = $ttl ? time() + $ttl : null;
        
        static::$store[$key] = [
            'value' => $value,
            'expiry' => $expiry
        ];

        if (static::$directory) {
            return static::writeToFile($key, $value, $expiry);
        }

        return true;
    }

    /**
     * Store an item in the cache forever
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function forever(string $key, $value): bool
    {
        return static::put($key, $value);
    }

    /**
     * Retrieve an item from the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // Check memory cache first
        if (isset(static::$store[$key])) {
            $item = static::$store[$key];
            
            if ($item['expiry'] === null || $item['expiry'] > time()) {
                return $item['value'];
            } else {
                unset(static::$store[$key]);
            }
        }

        // Check file cache
        if (static::$directory) {
            $value = static::readFromFile($key);
            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result
     *
     * @param string $key
     * @param \Closure $callback
     * @param int|null $ttl
     * @return mixed
     */
    public static function remember(string $key, \Closure $callback, int $ttl = null)
    {
        $value = static::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        static::put($key, $value, $ttl);

        return $value;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever
     *
     * @param string $key
     * @param \Closure $callback
     * @return mixed
     */
    public static function rememberForever(string $key, \Closure $callback)
    {
        return static::remember($key, $callback);
    }

    /**
     * Remove an item from the cache
     *
     * @param string $key
     * @return bool
     */
    public static function forget(string $key): bool
    {
        unset(static::$store[$key]);

        if (static::$directory) {
            $filename = static::getFilename($key);
            if (file_exists($filename)) {
                return unlink($filename);
            }
        }

        return true;
    }

    /**
     * Remove all items from the cache
     *
     * @return bool
     */
    public static function flush(): bool
    {
        static::$store = [];

        if (static::$directory && is_dir(static::$directory)) {
            $files = glob(static::$directory . '*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Determine if an item exists in the cache
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return static::get($key) !== null;
    }

    /**
     * Increment the value of an item in the cache
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public static function increment(string $key, int $value = 1)
    {
        $current = static::get($key, 0);
        $new = (int) $current + $value;
        
        if (static::put($key, $new)) {
            return $new;
        }

        return false;
    }

    /**
     * Decrement the value of an item in the cache
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public static function decrement(string $key, int $value = 1)
    {
        return static::increment($key, -$value);
    }

    /**
     * Store multiple items in the cache
     *
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public static function putMany(array $values, int $ttl = null): bool
    {
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!static::put($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Retrieve multiple items from the cache
     *
     * @param array $keys
     * @return array
     */
    public static function many(array $keys): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = static::get($key);
        }

        return $result;
    }

    /**
     * Store an item in the cache if the key doesn't exist
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public static function add(string $key, $value, int $ttl = null): bool
    {
        if (static::has($key)) {
            return false;
        }

        return static::put($key, $value, $ttl);
    }

    /**
     * Get and remove an item from the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function pull(string $key, $default = null)
    {
        $value = static::get($key, $default);
        static::forget($key);
        return $value;
    }

    /**
     * Get all cached items
     *
     * @return array
     */
    public static function all(): array
    {
        $result = [];
        
        // Get from memory cache
        foreach (static::$store as $key => $item) {
            if ($item['expiry'] === null || $item['expiry'] > time()) {
                $result[$key] = $item['value'];
            }
        }

        // Get from file cache
        if (static::$directory && is_dir(static::$directory)) {
            $files = glob(static::$directory . '*.cache');
            foreach ($files as $file) {
                $key = basename($file, '.cache');
                $key = static::decodeKey($key);
                
                if (!isset($result[$key])) {
                    $value = static::readFromFile($key);
                    if ($value !== null) {
                        $result[$key] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public static function stats(): array
    {
        $memoryCount = count(static::$store);
        $fileCount = 0;
        $totalSize = 0;

        if (static::$directory && is_dir(static::$directory)) {
            $files = glob(static::$directory . '*.cache');
            $fileCount = count($files);
            
            foreach ($files as $file) {
                $totalSize += filesize($file);
            }
        }

        return [
            'memory_items' => $memoryCount,
            'file_items' => $fileCount,
            'total_size' => $totalSize,
            'directory' => static::$directory
        ];
    }

    /**
     * Clean up expired cache items
     *
     * @return int Number of items cleaned
     */
    public static function cleanup(): int
    {
        $cleaned = 0;
        $now = time();

        // Clean memory cache
        foreach (static::$store as $key => $item) {
            if ($item['expiry'] !== null && $item['expiry'] <= $now) {
                unset(static::$store[$key]);
                $cleaned++;
            }
        }

        // Clean file cache
        if (static::$directory && is_dir(static::$directory)) {
            $files = glob(static::$directory . '*.cache');
            foreach ($files as $file) {
                $data = unserialize(file_get_contents($file));
                if (isset($data['expiry']) && $data['expiry'] !== null && $data['expiry'] <= $now) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Write cache data to file
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $expiry
     * @return bool
     */
    protected static function writeToFile(string $key, $value, int $expiry = null): bool
    {
        $filename = static::getFilename($key);
        $data = [
            'value' => $value,
            'expiry' => $expiry,
            'created' => time()
        ];

        return file_put_contents($filename, serialize($data), LOCK_EX) !== false;
    }

    /**
     * Read cache data from file
     *
     * @param string $key
     * @return mixed|null
     */
    protected static function readFromFile(string $key)
    {
        $filename = static::getFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }

        $data = unserialize(file_get_contents($filename));
        
        if (!is_array($data) || !isset($data['value'])) {
            return null;
        }

        if ($data['expiry'] !== null && $data['expiry'] <= time()) {
            unlink($filename);
            return null;
        }

        // Update memory cache
        static::$store[$key] = [
            'value' => $data['value'],
            'expiry' => $data['expiry']
        ];

        return $data['value'];
    }

    /**
     * Get the filename for a cache key
     *
     * @param string $key
     * @return string
     */
    protected static function getFilename(string $key): string
    {
        $encoded = static::encodeKey($key);
        return static::$directory . $encoded . '.cache';
    }

    /**
     * Encode a cache key for safe filename usage
     *
     * @param string $key
     * @return string
     */
    protected static function encodeKey(string $key): string
    {
        return base64_encode($key);
    }

    /**
     * Decode a cache key from filename
     *
     * @param string $encoded
     * @return string
     */
    protected static function decodeKey(string $encoded): string
    {
        return base64_decode($encoded);
    }
}