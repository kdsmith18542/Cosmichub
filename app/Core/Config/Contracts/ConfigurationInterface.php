<?php

namespace App\Core\Config\Contracts;

/**
 * Configuration Interface
 * 
 * Defines the contract for configuration management systems
 */
interface ConfigurationInterface
{
    /**
     * Load all configuration
     *
     * @param bool $force Force reload even if already loaded
     * @return void
     */
    public function load($force = false);

    /**
     * Get a configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Set a configuration value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value);

    /**
     * Check if a configuration key exists
     *
     * @param string $key
     * @return bool
     */
    public function has($key);

    /**
     * Get all configuration
     *
     * @return array
     */
    public function all();

    /**
     * Get an environment variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function env($key, $default = null);

    /**
     * Reload configuration
     *
     * @return void
     */
    public function reload();

    /**
     * Get configuration for a specific group
     *
     * @param string $group
     * @return array
     */
    public function getGroup($group);

    /**
     * Check if the application is in debug mode
     *
     * @return bool
     */
    public function isDebug();

    /**
     * Get the application environment
     *
     * @return string
     */
    public function getEnvironment();

    /**
     * Check if the application is in a specific environment
     *
     * @param string|array $environments
     * @return bool
     */
    public function isEnvironment($environments);
}