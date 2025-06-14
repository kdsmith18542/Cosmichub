<?php

namespace App\Core\Config;

use ArrayAccess;
use InvalidArgumentException;

/**
 * Configuration Repository
 *
 * This class provides a type-safe, centralized configuration repository
 * following the refactoring plan to improve configuration management.
 */
class ConfigRepository implements ArrayAccess
{
    /**
     * @var array The configuration data
     */
    protected $items = [];

    /**
     * Create a new configuration repository
     *
     * @param array $items Initial configuration items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Get a configuration value using dot notation
     *
     * @param string $key The configuration key
     * @param mixed $default The default value
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->items[$key];
        }

        return $this->getNestedValue($key, $default);
    }

    /**
     * Set a configuration value using dot notation
     *
     * @param string $key The configuration key
     * @param mixed $value The value to set
     * @return void
     */
    public function set($key, $value)
    {
        if (strpos($key, '.') === false) {
            $this->items[$key] = $value;
            return;
        }

        $this->setNestedValue($key, $value);
    }

    /**
     * Check if a configuration key exists
     *
     * @param string $key The configuration key
     * @return bool
     */
    public function has($key)
    {
        if (array_key_exists($key, $this->items)) {
            return true;
        }

        return $this->hasNestedValue($key);
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
     * Get a nested configuration value
     *
     * @param string $key The dot-notated key
     * @param mixed $default The default value
     * @return mixed
     */
    protected function getNestedValue($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->items;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set a nested configuration value
     *
     * @param string $key The dot-notated key
     * @param mixed $value The value to set
     * @return void
     */
    protected function setNestedValue($key, $value)
    {
        $keys = explode('.', $key);
        $array = &$this->items;

        foreach ($keys as $segment) {
            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }
            $array = &$array[$segment];
        }

        $array = $value;
    }

    /**
     * Check if a nested configuration key exists
     *
     * @param string $key The dot-notated key
     * @return bool
     */
    protected function hasNestedValue($key)
    {
        $keys = explode('.', $key);
        $value = $this->items;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Get a required configuration value
     *
     * @param string $key The configuration key
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getRequired($key)
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException("Required configuration key [{$key}] not found.");
        }

        return $this->get($key);
    }

    /**
     * Get a configuration value as a string
     *
     * @param string $key The configuration key
     * @param string $default The default value
     * @return string
     */
    public function getString($key, $default = '')
    {
        return (string) $this->get($key, $default);
    }

    /**
     * Get a configuration value as an integer
     *
     * @param string $key The configuration key
     * @param int $default The default value
     * @return int
     */
    public function getInt($key, $default = 0)
    {
        return (int) $this->get($key, $default);
    }

    /**
     * Get a configuration value as a boolean
     *
     * @param string $key The configuration key
     * @param bool $default The default value
     * @return bool
     */
    public function getBool($key, $default = false)
    {
        $value = $this->get($key, $default);
        
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }
        
        return (bool) $value;
    }

    /**
     * Get a configuration value as an array
     *
     * @param string $key The configuration key
     * @param array $default The default value
     * @return array
     */
    public function getArray($key, $default = [])
    {
        $value = $this->get($key, $default);
        return is_array($value) ? $value : $default;
    }

    // ArrayAccess implementation
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
}