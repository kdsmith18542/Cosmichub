<?php

namespace App\Support;

/**
 * Configuration helper class
 */
class Config
{
    /**
     * All of the configuration items
     *
     * @var array
     */
    protected static array $items = [];

    /**
     * Load configuration from a file
     *
     * @param string $path
     * @return void
     */
    public static function load(string $path): void
    {
        if (file_exists($path)) {
            $config = require $path;
            if (is_array($config)) {
                static::$items = array_merge(static::$items, $config);
            }
        }
    }

    /**
     * Load all configuration files from a directory
     *
     * @param string $directory
     * @return void
     */
    public static function loadDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . '/*.php');
        
        foreach ($files as $file) {
            $key = basename($file, '.php');
            $config = require $file;
            
            if (is_array($config)) {
                static::$items[$key] = $config;
            }
        }
    }

    /**
     * Get the specified configuration value
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key = null, $default = null)
    {
        if (is_null($key)) {
            return static::$items;
        }

        return Arr::get(static::$items, $key, $default);
    }

    /**
     * Set a given configuration value
     *
     * @param array|string $key
     * @param mixed $value
     * @return void
     */
    public static function set($key, $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set(static::$items, $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function prepend(string $key, $value): void
    {
        $array = static::get($key, []);

        array_unshift($array, $value);

        static::set($key, $array);
    }

    /**
     * Push a value onto an array configuration value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function push(string $key, $value): void
    {
        $array = static::get($key, []);

        $array[] = $value;

        static::set($key, $array);
    }

    /**
     * Determine if the given configuration value exists
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return Arr::has(static::$items, $key);
    }

    /**
     * Get many configuration values
     *
     * @param array $keys
     * @return array
     */
    public static function getMany(array $keys): array
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $config[$key] = static::get($key, $default);
        }

        return $config;
    }

    /**
     * Get all of the configuration items
     *
     * @return array
     */
    public static function all(): array
    {
        return static::$items;
    }

    /**
     * Clear all configuration items
     *
     * @return void
     */
    public static function clear(): void
    {
        static::$items = [];
    }

    /**
     * Get an environment variable with optional default
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Load environment variables from a .env file
     *
     * @param string $path
     * @return void
     */
    public static function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    /**
     * Get a configuration value and cast it to a boolean
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }

        return (bool) $value;
    }

    /**
     * Get a configuration value and cast it to an integer
     *
     * @param string $key
     * @param int $default
     * @return int
     */
    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::get($key, $default);
    }

    /**
     * Get a configuration value and cast it to a float
     *
     * @param string $key
     * @param float $default
     * @return float
     */
    public static function getFloat(string $key, float $default = 0.0): float
    {
        return (float) static::get($key, $default);
    }

    /**
     * Get a configuration value and cast it to a string
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function getString(string $key, string $default = ''): string
    {
        return (string) static::get($key, $default);
    }

    /**
     * Get a configuration value and cast it to an array
     *
     * @param string $key
     * @param array $default
     * @return array
     */
    public static function getArray(string $key, array $default = []): array
    {
        $value = static::get($key, $default);

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return explode(',', $value);
        }

        return (array) $value;
    }

    /**
     * Merge configuration arrays recursively
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function merge(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = static::merge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Forget a configuration value
     *
     * @param string $key
     * @return void
     */
    public static function forget(string $key): void
    {
        Arr::forget(static::$items, $key);
    }

    /**
     * Get configuration for a specific environment
     *
     * @param string $environment
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getForEnvironment(string $environment, string $key, $default = null)
    {
        $envKey = $environment . '.' . $key;
        
        if (static::has($envKey)) {
            return static::get($envKey);
        }

        return static::get($key, $default);
    }
}