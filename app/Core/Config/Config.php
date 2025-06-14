<?php

namespace App\Core\Config;

use App\Core\Config\Exceptions\ConfigException;

/**
 * Configuration manager with support for dot notation access
 */
class Config
{
    /**
     * @var array The configuration items
     */
    protected $items = [];
    
    /**
     * @var string The current environment
     */
    protected $environment;
    
    /**
     * @var array The configuration loaders
     */
    protected $loaders = [];
    
    /**
     * @var bool Whether the config is cached
     */
    protected $cached = false;
    
    /**
     * Create a new config instance
     * 
     * @param array $items The configuration items
     * @param string $environment The environment
     */
    public function __construct(array $items = [], $environment = 'production')
    {
        $this->items = $items;
        $this->environment = $environment;
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key The key
     * @param mixed $default The default value
     * @return mixed
     */
    public function get($key, $default = null)
    {
        // If the key doesn't contain a dot, just return the value
        if (!str_contains($key, '.')) {
            return $this->items[$key] ?? $default;
        }
        
        // Split the key by dots
        $parts = explode('.', $key);
        $items = $this->items;
        
        // Loop through the parts
        foreach ($parts as $part) {
            // If the part doesn't exist, return the default
            if (!isset($items[$part])) {
                return $default;
            }
            
            $items = $items[$part];
        }
        
        return $items;
    }
    
    /**
     * Set a configuration value
     * 
     * @param string $key The key
     * @param mixed $value The value
     * @return void
     */
    public function set($key, $value)
    {
        // If the key doesn't contain a dot, just set the value
        if (!str_contains($key, '.')) {
            $this->items[$key] = $value;
            return;
        }
        
        // Split the key by dots
        $parts = explode('.', $key);
        $items = &$this->items;
        
        // Loop through the parts
        foreach ($parts as $i => $part) {
            // If this is the last part, set the value
            if ($i === count($parts) - 1) {
                $items[$part] = $value;
                break;
            }
            
            // If the part doesn't exist, create it
            if (!isset($items[$part]) || !is_array($items[$part])) {
                $items[$part] = [];
            }
            
            $items = &$items[$part];
        }
    }
    
    /**
     * Check if a configuration value exists
     * 
     * @param string $key The key
     * @return bool
     */
    public function has($key)
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Get all configuration items
     * 
     * @return array
     */
    public function all()
    {
        return $this->items;
    }
    
    /**
     * Load configuration from a file
     * 
     * @param string $path The file path
     * @param string $key The key to store the configuration under
     * @return $this
     * @throws ConfigException
     */
    public function load($path, $key = null)
    {
        if (!file_exists($path)) {
            throw new ConfigException("Configuration file not found: {$path}");
        }
        
        $items = require $path;
        
        if (!is_array($items)) {
            throw new ConfigException("Configuration file must return an array: {$path}");
        }
        
        if ($key) {
            $this->items[$key] = array_merge(
                $this->items[$key] ?? [],
                $items
            );
        } else {
            $this->items = array_merge($this->items, $items);
        }
        
        return $this;
    }
    
    /**
     * Load configuration from a directory
     * 
     * @param string $path The directory path
     * @return $this
     * @throws ConfigException
     */
    public function loadFromDirectory($path)
    {
        if (!is_dir($path)) {
            throw new ConfigException("Configuration directory not found: {$path}");
        }
        
        $files = glob($path . '/*.php');
        
        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $this->load($file, $key);
        }
        
        // Load environment-specific configurations
        $envPath = $path . '/' . $this->environment;
        if (is_dir($envPath)) {
            $envFiles = glob($envPath . '/*.php');
            
            foreach ($envFiles as $file) {
                $key = pathinfo($file, PATHINFO_FILENAME);
                $this->load($file, $key);
            }
        }
        
        return $this;
    }
    
    /**
     * Get the current environment
     * 
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
    
    /**
     * Set the environment
     * 
     * @param string $environment
     * @return void
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }
    
    /**
     * Check if the configuration is cached
     * 
     * @return bool
     */
    public function isCached()
    {
        return $this->cached;
    }
    
    /**
     * Set the cached status
     * 
     * @param bool $cached
     * @return void
     */
    public function setCached($cached)
    {
        $this->cached = $cached;
    }
}