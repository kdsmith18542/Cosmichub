<?php

namespace App\Core;

/**
 * Configuration management class
 */
class Config
{
    /**
     * @var array The configuration items
     */
    protected $items = [];
    
    /**
     * @var Config|null Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get the config instance
     * 
     * @return Config
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load configuration from a file
     * 
     * @param string $path Path to the configuration file
     * @return $this
     * @throws \Exception If the file doesn't exist or isn't readable
     */
    public function load($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("Configuration file not found: {$path}");
        }
        
        if (!is_readable($path)) {
            throw new \Exception("Configuration file not readable: {$path}");
        }
        
        $config = require $path;
        
        if (!is_array($config)) {
            throw new \Exception("Configuration file must return an array: {$path}");
        }
        
        // Get the filename without extension to use as the config group
        $group = pathinfo($path, PATHINFO_FILENAME);
        
        $this->items[$group] = $config;
        
        return $this;
    }
    
    /**
     * Load all configuration files from a directory
     * 
     * @param string $directory Path to the configuration directory
     * @return $this
     * @throws \Exception If the directory doesn't exist or isn't readable
     */
    public function loadFromDirectory($directory)
    {
        if (!is_dir($directory)) {
            throw new \Exception("Configuration directory not found: {$directory}");
        }
        
        if (!is_readable($directory)) {
            throw new \Exception("Configuration directory not readable: {$directory}");
        }
        
        $files = glob($directory . '/*.php');
        
        foreach ($files as $file) {
            $this->load($file);
        }
        
        return $this;
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key The configuration key in dot notation (e.g., 'app.name')
     * @param mixed $default The default value to return if the key doesn't exist
     * @return mixed
     */
    public function get($key, $default = null)
    {
        // If the key doesn't contain a dot, return the entire group
        if (strpos($key, '.') === false) {
            return $this->items[$key] ?? $default;
        }
        
        // Split the key into group and item
        list($group, $item) = explode('.', $key, 2);
        
        // If the group doesn't exist, return the default
        if (!isset($this->items[$group])) {
            return $default;
        }
        
        // Get the value from the group
        return $this->getFromArray($this->items[$group], $item, $default);
    }
    
    /**
     * Set a configuration value
     * 
     * @param string $key The configuration key in dot notation (e.g., 'app.name')
     * @param mixed $value The value to set
     * @return $this
     */
    public function set($key, $value)
    {
        // If the key doesn't contain a dot, set the entire group
        if (strpos($key, '.') === false) {
            $this->items[$key] = $value;
            return $this;
        }
        
        // Split the key into group and item
        list($group, $item) = explode('.', $key, 2);
        
        // If the group doesn't exist, create it
        if (!isset($this->items[$group])) {
            $this->items[$group] = [];
        }
        
        // Set the value in the group
        $this->setInArray($this->items[$group], $item, $value);
        
        return $this;
    }
    
    /**
     * Check if a configuration value exists
     * 
     * @param string $key The configuration key in dot notation (e.g., 'app.name')
     * @return bool
     */
    public function has($key)
    {
        // If the key doesn't contain a dot, check if the group exists
        if (strpos($key, '.') === false) {
            return isset($this->items[$key]);
        }
        
        // Split the key into group and item
        list($group, $item) = explode('.', $key, 2);
        
        // If the group doesn't exist, return false
        if (!isset($this->items[$group])) {
            return false;
        }
        
        // Check if the item exists in the group
        return $this->hasInArray($this->items[$group], $item);
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
     * Get a value from an array using dot notation
     * 
     * @param array $array The array to get the value from
     * @param string $key The key in dot notation
     * @param mixed $default The default value to return if the key doesn't exist
     * @return mixed
     */
    protected function getFromArray(array $array, $key, $default = null)
    {
        // If the key doesn't contain a dot, return the item directly
        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }
        
        // Split the key into parts
        $parts = explode('.', $key);
        
        // Get the first part
        $first = array_shift($parts);
        
        // If the first part doesn't exist, return the default
        if (!isset($array[$first])) {
            return $default;
        }
        
        // If there are no more parts, return the value
        if (empty($parts)) {
            return $array[$first];
        }
        
        // If the value isn't an array, return the default
        if (!is_array($array[$first])) {
            return $default;
        }
        
        // Recursively get the value from the array
        return $this->getFromArray($array[$first], implode('.', $parts), $default);
    }
    
    /**
     * Set a value in an array using dot notation
     * 
     * @param array &$array The array to set the value in
     * @param string $key The key in dot notation
     * @param mixed $value The value to set
     * @return void
     */
    protected function setInArray(array &$array, $key, $value)
    {
        // If the key doesn't contain a dot, set the item directly
        if (strpos($key, '.') === false) {
            $array[$key] = $value;
            return;
        }
        
        // Split the key into parts
        $parts = explode('.', $key);
        
        // Get the first part
        $first = array_shift($parts);
        
        // If the first part doesn't exist or isn't an array, create it
        if (!isset($array[$first]) || !is_array($array[$first])) {
            $array[$first] = [];
        }
        
        // Recursively set the value in the array
        $this->setInArray($array[$first], implode('.', $parts), $value);
    }
    
    /**
     * Check if a key exists in an array using dot notation
     * 
     * @param array $array The array to check
     * @param string $key The key in dot notation
     * @return bool
     */
    protected function hasInArray(array $array, $key)
    {
        // If the key doesn't contain a dot, check if the item exists directly
        if (strpos($key, '.') === false) {
            return isset($array[$key]);
        }
        
        // Split the key into parts
        $parts = explode('.', $key);
        
        // Get the first part
        $first = array_shift($parts);
        
        // If the first part doesn't exist, return false
        if (!isset($array[$first])) {
            return false;
        }
        
        // If there are no more parts, return true
        if (empty($parts)) {
            return true;
        }
        
        // If the value isn't an array, return false
        if (!is_array($array[$first])) {
            return false;
        }
        
        // Recursively check if the key exists in the array
        return $this->hasInArray($array[$first], implode('.', $parts));
    }
    
    /**
     * Prevent direct instantiation
     */
    private function __construct() {}
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    private function __wakeup() {}
}