<?php

namespace App\Libraries\Core;

/**
 * Simple dependency injection container
 */
class Container
{
    /**
     * @var Container|null Singleton instance
     */
    private static $instance = null;
    
    /**
     * @var array The container's bindings
     */
    private $bindings = [];
    
    /**
     * Get the container instance
     * 
     * @return Container
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Bind a value to the container
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function bind($key, $value)
    {
        $this->bindings[$key] = $value;
    }
    
    /**
     * Get a value from the container
     * 
     * @param string $key
     * @return mixed
     * @throws \Exception If the key is not found
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \Exception("No binding found for {$key}");
        }
        
        $value = $this->bindings[$key];
        
        // If the value is a closure, resolve it
        if ($value instanceof \Closure) {
            return $value($this);
        }
        
        return $value;
    }
    
    /**
     * Check if a key exists in the container
     * 
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->bindings);
    }
    
    /**
     * Alias for bind() for compatibility with PSR-11
     * 
     * @param string $id
     * @param mixed $value
     * @return void
     */
    public function set($id, $value)
    {
        $this->bind($id, $value);
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
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
