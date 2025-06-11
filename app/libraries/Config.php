<?php

namespace App\Libraries;

class Config
{
    /**
     * @var array Configuration cache
     */
    private static $config = [];
    
    /**
     * Load configuration from a file
     * 
     * @param string $file Path to the config file
     * @param string $key Optional key to load specific section
     * @return array|mixed
     */
    public static function load($file, $key = null)
    {
        if (!file_exists($file)) {
            throw new \RuntimeException("Configuration file not found: {$file}");
        }
        
        // Load the config file
        $config = require $file;
        
        if ($key) {
            return $config[$key] ?? [];
        }
        
        return $config;
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key Dot notation key (e.g., 'app.name')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (empty(self::$config)) {
            self::loadConfig();
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
     * Set a configuration value
     * 
     * @param string $key Dot notation key
     * @param mixed $value Value to set
     */
    public static function set($key, $value)
    {
        $keys = explode('.', $key);
        $config = &self::$config;
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($config[$key]) || !is_array($config[$key])) {
                $config[$key] = [];
            }
            
            $config = &$config[$key];
        }
        
        $config[array_shift($keys)] = $value;
    }
    
    /**
     * Load all configuration files
     */
    protected static function loadConfig()
    {
        $configPath = dirname(__DIR__, 2) . '/config';
        
        // Load app config
        self::$config = self::load($configPath . '/app.php');
        
        // Load database config if exists
        if (file_exists($configPath . '/database.php')) {
            self::$config['database'] = self::load($configPath . '/database.php');
        }
        
        // Load mail config if exists
        if (file_exists($configPath . '/mail.php')) {
            self::$config['mail'] = self::load($configPath . '/mail.php');
        }
    }
}
