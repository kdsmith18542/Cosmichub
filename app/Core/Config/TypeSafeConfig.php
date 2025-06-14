<?php

namespace App\Core\Config;

use InvalidArgumentException;
use RuntimeException;

/**
 * Type-Safe Configuration Class
 * 
 * Provides type-safe configuration access with validation and default values
 * as part of the enhanced configuration management system.
 */
class TypeSafeConfig
{
    /**
     * @var array The configuration data
     */
    protected $config;
    
    /**
     * @var array Configuration schema for validation
     */
    protected $schema;
    
    /**
     * Constructor
     * 
     * @param array $config
     * @param array $schema
     */
    public function __construct(array $config = [], array $schema = [])
    {
        $this->config = $config;
        $this->schema = $schema;
        
        if (!empty($schema)) {
            $this->validate();
        }
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->getValue($key, $default);
    }
    
    /**
     * Get a string configuration value
     * 
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->getValue($key, $default);
        
        if (!is_string($value)) {
            throw new InvalidArgumentException("Configuration key '{$key}' must be a string");
        }
        
        return $value;
    }
    
    /**
     * Get an integer configuration value
     * 
     * @param string $key
     * @param int $default
     * @return int
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->getValue($key, $default);
        
        if (!is_int($value) && !is_numeric($value)) {
            throw new InvalidArgumentException("Configuration key '{$key}' must be an integer");
        }
        
        return (int) $value;
    }
    
    /**
     * Get a float configuration value
     * 
     * @param string $key
     * @param float $default
     * @return float
     */
    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->getValue($key, $default);
        
        if (!is_float($value) && !is_numeric($value)) {
            throw new InvalidArgumentException("Configuration key '{$key}' must be a float");
        }
        
        return (float) $value;
    }
    
    /**
     * Get a boolean configuration value
     * 
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->getValue($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', '1', 'yes', 'on'])) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'off', ''])) {
                return false;
            }
        }
        
        if (is_numeric($value)) {
            return (bool) $value;
        }
        
        throw new InvalidArgumentException("Configuration key '{$key}' must be a boolean");
    }
    
    /**
     * Get an array configuration value
     * 
     * @param string $key
     * @param array $default
     * @return array
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->getValue($key, $default);
        
        if (!is_array($value)) {
            throw new InvalidArgumentException("Configuration key '{$key}' must be an array");
        }
        
        return $value;
    }
    
    /**
     * Check if a configuration key exists
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->keyExists($key);
    }
    
    /**
     * Set a configuration value
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->setValue($key, $value);
    }
    
    /**
     * Get all configuration data
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }
    
    /**
     * Merge additional configuration
     * 
     * @param array $config
     * @return void
     */
    public function merge(array $config): void
    {
        $this->config = array_merge_recursive($this->config, $config);
    }
    
    /**
     * Get a nested configuration value using dot notation
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getValue(string $key, $default = null)
    {
        if (strpos($key, '.') === false) {
            return $this->config[$key] ?? $default;
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
    
    /**
     * Set a nested configuration value using dot notation
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setValue(string $key, $value): void
    {
        if (strpos($key, '.') === false) {
            $this->config[$key] = $value;
            return;
        }
        
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $config[$segment] = $value;
            } else {
                if (!isset($config[$segment]) || !is_array($config[$segment])) {
                    $config[$segment] = [];
                }
                $config = &$config[$segment];
            }
        }
    }
    
    /**
     * Check if a key exists using dot notation
     * 
     * @param string $key
     * @return bool
     */
    protected function keyExists(string $key): bool
    {
        if (strpos($key, '.') === false) {
            return array_key_exists($key, $this->config);
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }
        
        return true;
    }
    
    /**
     * Validate configuration against schema
     * 
     * @return void
     * @throws RuntimeException
     */
    protected function validate(): void
    {
        foreach ($this->schema as $key => $rules) {
            if (!$this->has($key)) {
                if (isset($rules['required']) && $rules['required']) {
                    throw new RuntimeException("Required configuration key '{$key}' is missing");
                }
                continue;
            }
            
            $value = $this->get($key);
            
            // Type validation
            if (isset($rules['type'])) {
                $this->validateType($key, $value, $rules['type']);
            }
            
            // Value validation
            if (isset($rules['values']) && !in_array($value, $rules['values'])) {
                $allowed = implode(', ', $rules['values']);
                throw new RuntimeException("Configuration key '{$key}' must be one of: {$allowed}");
            }
            
            // Range validation for numeric values
            if (isset($rules['min']) && is_numeric($value) && $value < $rules['min']) {
                throw new RuntimeException("Configuration key '{$key}' must be at least {$rules['min']}");
            }
            
            if (isset($rules['max']) && is_numeric($value) && $value > $rules['max']) {
                throw new RuntimeException("Configuration key '{$key}' must be at most {$rules['max']}");
            }
        }
    }
    
    /**
     * Validate value type
     * 
     * @param string $key
     * @param mixed $value
     * @param string $expectedType
     * @return void
     * @throws RuntimeException
     */
    protected function validateType(string $key, $value, string $expectedType): void
    {
        $actualType = gettype($value);
        
        $typeMap = [
            'string' => 'string',
            'int' => 'integer',
            'integer' => 'integer',
            'float' => 'double',
            'double' => 'double',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'object',
        ];
        
        $expectedPhpType = $typeMap[$expectedType] ?? $expectedType;
        
        if ($actualType !== $expectedPhpType) {
            throw new RuntimeException(
                "Configuration key '{$key}' must be of type {$expectedType}, {$actualType} given"
            );
        }
    }
    
    /**
     * Create configuration from environment variables
     * 
     * @param array $mapping Key-value pairs mapping config keys to env var names
     * @return static
     */
    public static function fromEnvironment(array $mapping): self
    {
        $config = [];
        
        foreach ($mapping as $configKey => $envKey) {
            $value = getenv($envKey);
            if ($value !== false) {
                $config[$configKey] = $value;
            }
        }
        
        return new static($config);
    }
    
    /**
     * Create configuration with validation schema
     * 
     * @param array $config
     * @param array $schema
     * @return static
     */
    public static function withSchema(array $config, array $schema): self
    {
        return new static($config, $schema);
    }
}