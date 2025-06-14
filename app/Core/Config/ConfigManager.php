<?php

namespace App\Core\Config;

use RuntimeException;

/**
 * Enhanced Configuration Manager
 *
 * This class provides improved configuration management with environment variable support,
 * validation, and caching capabilities following the refactoring plan.
 */
class ConfigManager
{
    /**
     * @var array The loaded configuration
     */
    private static $config = [];
    
    /**
     * @var bool Whether configuration has been loaded
     */
    private static $loaded = false;
    
    /**
     * @var string The environment file path
     */
    private static $envPath;
    
    /**
     * @var string The config directory path
     */
    private static $configPath;
    
    /**
     * Initialize the configuration manager
     *
     * @param string $envPath Path to the .env file
     * @param string $configPath Path to the config directory
     * @return void
     */
    public static function initialize($envPath, $configPath)
    {
        self::$envPath = $envPath;
        self::$configPath = $configPath;
        self::loadEnvironment();
        self::loadConfiguration();
        self::$loaded = true;
    }
    
    /**
     * Load environment variables from .env file
     *
     * @return void
     */
    private static function loadEnvironment()
    {
        if (!file_exists(self::$envPath)) {
            return; // .env file is optional
        }
        
        $envVars = parse_ini_file(self::$envPath);
        if ($envVars === false) {
            throw new RuntimeException('Failed to parse .env file: ' . self::$envPath);
        }
        
        foreach ($envVars as $key => $value) {
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
    
    /**
     * Load configuration files
     *
     * @return void
     */
    private static function loadConfiguration()
    {
        $configFile = self::$configPath . '/config.php';
        if (!file_exists($configFile)) {
            throw new RuntimeException('Configuration file not found: ' . $configFile);
        }
        
        $config = require $configFile;
        if (!is_array($config)) {
            throw new RuntimeException('Configuration file must return an array');
        }
        
        // Process environment variables in configuration
        self::$config = self::processEnvironmentVariables($config);
        
        // Load additional configuration files
        self::loadAdditionalConfigs();
    }
    
    /**
     * Load additional configuration files from the config directory
     *
     * @return void
     */
    private static function loadAdditionalConfigs()
    {
        $configFiles = glob(self::$configPath . '/*.php');
        
        foreach ($configFiles as $file) {
            $filename = basename($file, '.php');
            
            // Skip the main config file
            if ($filename === 'config') {
                continue;
            }
            
            $fileConfig = require $file;
            if (is_array($fileConfig)) {
                self::$config[$filename] = self::processEnvironmentVariables($fileConfig);
            }
        }
    }
    
    /**
     * Process environment variables in configuration arrays
     *
     * @param array $config
     * @return array
     */
    private static function processEnvironmentVariables(array $config)
    {
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = self::processEnvironmentVariables($value);
            } elseif (is_string($value) && strpos($value, 'env(') === 0) {
                // Handle env() function calls
                $config[$key] = self::parseEnvFunction($value);
            }
        }
        
        return $config;
    }
    
    /**
     * Parse env() function calls in configuration values
     *
     * @param string $value
     * @return mixed
     */
    private static function parseEnvFunction($value)
    {
        // Match env('KEY', 'default') or env('KEY')
        if (preg_match('/env\([\'"]([^\'"]*)[\'"](,\s*[\'"]([^\'"]*)[\'"]*)?\)/', $value, $matches)) {
            $envKey = $matches[1];
            $default = isset($matches[3]) ? $matches[3] : null;
            
            $envValue = getenv($envKey);
            if ($envValue !== false) {
                return self::castEnvValue($envValue);
            }
            
            return $default;
        }
        
        return $value;
    }
    
    /**
     * Cast environment values to appropriate types
     *
     * @param string $value
     * @return mixed
     */
    private static function castEnvValue($value)
    {
        $lower = strtolower($value);
        
        if ($lower === 'true') {
            return true;
        }
        
        if ($lower === 'false') {
            return false;
        }
        
        if ($lower === 'null') {
            return null;
        }
        
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }
        
        return $value;
    }
    
    /**
     * Get a configuration value using dot notation
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (!self::$loaded) {
            throw new RuntimeException('Configuration manager not initialized');
        }
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            
            $value = $value[$segment];
        }
        
        return $value;
    }
    
    /**
     * Set a configuration value using dot notation
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set($key, $value)
    {
        if (!self::$loaded) {
            throw new RuntimeException('Configuration manager not initialized');
        }
        
        $keys = explode('.', $key);
        $config = &self::$config;
        
        foreach ($keys as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            
            $config = &$config[$segment];
        }
        
        $config = $value;
    }
    
    /**
     * Check if a configuration key exists
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        if (!self::$loaded) {
            return false;
        }
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            
            $value = $value[$segment];
        }
        
        return true;
    }
    
    /**
     * Get all configuration
     *
     * @return array
     */
    public static function all()
    {
        if (!self::$loaded) {
            throw new RuntimeException('Configuration manager not initialized');
        }
        
        return self::$config;
    }
    
    /**
     * Get an environment variable with type casting
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function env($key, $default = null)
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        return self::castEnvValue($value);
    }
    
    /**
     * Validate required configuration keys
     *
     * @param array $requiredKeys
     * @throws RuntimeException
     * @return void
     */
    public static function validateRequired(array $requiredKeys)
    {
        $missing = [];
        
        foreach ($requiredKeys as $key) {
            if (!self::has($key)) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new RuntimeException('Missing required configuration keys: ' . implode(', ', $missing));
        }
    }
    
    /**
     * Clear the configuration cache
     *
     * @return void
     */
    public static function clear()
    {
        self::$config = [];
        self::$loaded = false;
    }
}