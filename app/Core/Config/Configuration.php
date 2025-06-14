<?php

namespace App\Core\Config;

use App\Core\Exceptions\ServiceException;
use Psr\Log\LoggerInterface;

/**
 * Configuration Class
 * 
 * Manages application configuration with support for multiple sources,
 * environment-specific configs, and runtime configuration changes
 */
class Configuration
{
    /**
     * @var array Configuration data
     */
    protected $config = [];
    
    /**
     * @var array Configuration sources
     */
    protected $sources = [];
    
    /**
     * @var string Current environment
     */
    protected $environment = 'production';
    
    /**
     * @var array Environment variables cache
     */
    protected $envCache = [];
    
    /**
     * @var bool Whether to cache configuration
     */
    protected $cacheEnabled = true;
    
    /**
     * @var string Configuration cache file
     */
    protected $cacheFile;
    
    /**
     * @var array Watchers for configuration changes
     */
    protected $watchers = [];
    
    /**
     * @var array Default configuration values
     */
    protected $defaults = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * Create a new configuration instance
     * 
     * @param array $config
     * @param string $environment
     * @param LoggerInterface $logger
     */
    public function __construct(array $config = [], $environment = 'production', LoggerInterface $logger)
    {
        $this->config = $config;
        $this->environment = $environment;
        $this->cacheFile = sys_get_temp_dir() . '/app_config_cache.php';
        $this->logger = $logger;
        $this->loadEnvironmentVariables();
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        // Check for environment variable override
        $envKey = $this->getEnvironmentKey($key);
        if (isset($this->envCache[$envKey])) {
            return $this->castValue($this->envCache[$envKey]);
        }
        
        // Get from configuration array
        $value = $this->getNestedValue($this->config, $key);
        
        if ($value !== null) {
            return $value;
        }
        
        // Check defaults
        $defaultValue = $this->getNestedValue($this->defaults, $key);
        
        return $defaultValue !== null ? $defaultValue : $default;
    }
    
    /**
     * Set a configuration value
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->setNestedValue($this->config, $key, $value);
        $this->notifyWatchers($key, $value);
        return $this;
    }
    
    /**
     * Check if a configuration key exists
     * 
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $envKey = $this->getEnvironmentKey($key);
        if (isset($this->envCache[$envKey])) {
            return true;
        }
        
        return $this->getNestedValue($this->config, $key) !== null ||
               $this->getNestedValue($this->defaults, $key) !== null;
    }
    
    /**
     * Remove a configuration key
     * 
     * @param string $key
     * @return $this
     */
    public function remove($key)
    {
        $this->unsetNestedValue($this->config, $key);
        $this->notifyWatchers($key, null);
        return $this;
    }
    
    /**
     * Get all configuration data
     * 
     * @return array
     */
    public function all()
    {
        return $this->config;
    }
    
    /**
     * Merge configuration data
     * 
     * @param array $config
     * @return $this
     */
    public function merge(array $config)
    {
        $this->config = array_merge_recursive($this->config, $config);
        return $this;
    }
    
    /**
     * Replace configuration data
     * 
     * @param array $config
     * @return $this
     */
    public function replace(array $config)
    {
        $this->config = $config;
        return $this;
    }
    
    /**
     * Load configuration from file
     * 
     * @param string $file
     * @param string|null $key
     * @return $this
     * @throws ServiceException
     */
    public function loadFromFile($file, $key = null)
    {
        if (!file_exists($file)) {
            throw new ServiceException("Configuration file not found: {$file}");
        }
        
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'php':
                $data = require $file;
                break;
            case 'json':
                $data = json_decode(file_get_contents($file), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new ServiceException("Invalid JSON in config file: {$file}");
                }
                break;
            case 'ini':
                $data = parse_ini_file($file, true);
                if ($data === false) {
                    throw new ServiceException("Failed to parse INI file: {$file}");
                }
                break;
            default:
                throw new ServiceException("Unsupported config file format: {$extension}");
        }
        
        if (!is_array($data)) {
            throw new ServiceException("Configuration file must return an array: {$file}");
        }
        
        if ($key) {
            $this->set($key, $data);
        } else {
            $this->merge($data);
        }
        
        $this->sources[] = $file;
        
        return $this;
    }
    
    /**
     * Load configuration from directory
     * 
     * @param string $directory
     * @return $this
     * @throws ServiceException
     */
    public function loadFromDirectory($directory)
    {
        if (!is_dir($directory)) {
            throw new ServiceException("Configuration directory not found: {$directory}");
        }
        
        $files = glob($directory . '/*.{php,json,ini}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $this->loadFromFile($file, $key);
        }
        
        // Load environment-specific configs
        $envDir = $directory . '/' . $this->environment;
        if (is_dir($envDir)) {
            $envFiles = glob($envDir . '/*.{php,json,ini}', GLOB_BRACE);
            foreach ($envFiles as $file) {
                $key = pathinfo($file, PATHINFO_FILENAME);
                $this->loadFromFile($file, $key);
            }
        }
        
        return $this;
    }
    
    /**
     * Save configuration to cache
     * 
     * @return bool
     */
    public function saveCache()
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        $content = "<?php\nreturn " . var_export($this->config, true) . ";\n";
        
        return file_put_contents($this->cacheFile, $content, LOCK_EX) !== false;
    }
    
    /**
     * Load configuration from cache
     * 
     * @return bool
     */
    public function loadCache()
    {
        if (!$this->cacheEnabled || !file_exists($this->cacheFile)) {
            return false;
        }
        
        $cached = require $this->cacheFile;
        
        if (is_array($cached)) {
            $this->config = $cached;
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear configuration cache
     * 
     * @return bool
     */
    public function clearCache()
    {
        if (file_exists($this->cacheFile)) {
            return unlink($this->cacheFile);
        }
        
        return true;
    }
    
    /**
     * Watch for configuration changes
     * 
     * @param string $key
     * @param callable $callback
     * @return $this
     */
    public function watch($key, callable $callback)
    {
        if (!isset($this->watchers[$key])) {
            $this->watchers[$key] = [];
        }
        
        $this->watchers[$key][] = $callback;
        
        return $this;
    }
    
    /**
     * Set default configuration values
     * 
     * @param array $defaults
     * @return $this
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
        return $this;
    }
    
    /**
     * Get current environment
     * 
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
    
    /**
     * Set current environment
     * 
     * @param string $environment
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        $this->loadEnvironmentVariables();
        return $this;
    }
    
    /**
     * Enable or disable caching
     * 
     * @param bool $enabled
     * @return $this
     */
    public function setCacheEnabled($enabled)
    {
        $this->cacheEnabled = $enabled;
        return $this;
    }
    
    /**
     * Set cache file path
     * 
     * @param string $file
     * @return $this
     */
    public function setCacheFile($file)
    {
        $this->cacheFile = $file;
        return $this;
    }
    
    /**
     * Get configuration sources
     * 
     * @return array
     */
    public function getSources()
    {
        return $this->sources;
    }
    
    /**
     * Get nested value from array using dot notation
     * 
     * @param array $array
     * @param string $key
     * @return mixed
     */
    protected function getNestedValue(array $array, $key)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        if (!str_contains($key, '.')) {
            return null;
        }
        
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
    
    /**
     * Set nested value in array using dot notation
     * 
     * @param array &$array
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setNestedValue(array &$array, $key, $value)
    {
        if (!str_contains($key, '.')) {
            $array[$key] = $value;
            return;
        }
        
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
        
        $current = $value;
    }
    
    /**
     * Unset nested value in array using dot notation
     * 
     * @param array &$array
     * @param string $key
     * @return void
     */
    protected function unsetNestedValue(array &$array, $key)
    {
        if (!str_contains($key, '.')) {
            unset($array[$key]);
            return;
        }
        
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$array;
        
        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                return;
            }
            $current = &$current[$segment];
        }
        
        unset($current[$lastKey]);
    }
    
    /**
     * Get environment variable key
     * 
     * @param string $key
     * @return string
     */
    protected function getEnvironmentKey($key)
    {
        return strtoupper(str_replace('.', '_', $key));
    }
    
    /**
     * Load environment variables
     * 
     * @return void
     */
    protected function loadEnvironmentVariables()
    {
        $this->envCache = [];
        
        foreach ($_ENV as $key => $value) {
            $this->envCache[$key] = $value;
        }
        
        // Also check $_SERVER for environment variables
        foreach ($_SERVER as $key => $value) {
            if (!isset($this->envCache[$key])) {
                $this->envCache[$key] = $value;
            }
        }
    }
    
    /**
     * Cast string value to appropriate type
     * 
     * @param string $value
     * @return mixed
     */
    protected function castValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        
        // Boolean values
        if (in_array(strtolower($value), ['true', 'false'])) {
            return strtolower($value) === 'true';
        }
        
        // Null value
        if (strtolower($value) === 'null') {
            return null;
        }
        
        // Numeric values
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        
        // JSON values
        if (str_starts_with($value, '{') || str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return $value;
    }
    
    /**
     * Notify watchers of configuration changes
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function notifyWatchers($key, $value)
    {
        if (isset($this->watchers[$key])) {
            foreach ($this->watchers[$key] as $callback) {
                try {
                    call_user_func($callback, $key, $value, $this);
                } catch (\Exception $e) {
                    // Log error but don't stop execution
                    $this->logger->error("Configuration watcher error: {$e->getMessage()}");
                }
            }
        }
    }
    
    /**
     * Create configuration from array
     * 
     * @param array $config
     * @param string $environment
     * @return static
     */
    public static function fromArray(array $config, $environment = 'production')
    {
        return new static($config, $environment);
    }
    
    /**
     * Create configuration from file
     * 
     * @param string $file
     * @param string $environment
     * @return static
     */
    public static function fromFile($file, $environment = 'production')
    {
        $instance = new static([], $environment);
        $instance->loadFromFile($file);
        return $instance;
    }
    
    /**
     * Create configuration from directory
     * 
     * @param string $directory
     * @param string $environment
     * @return static
     */
    public static function fromDirectory($directory, $environment = 'production')
    {
        $instance = new static([], $environment);
        $instance->loadFromDirectory($directory);
        return $instance;
    }
}