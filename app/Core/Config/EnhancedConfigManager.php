<?php

namespace App\Core\Config;

use App\Core\Application;
use App\Core\Exceptions\ConfigException;
use InvalidArgumentException;

/**
 * Enhanced Configuration Manager
 * 
 * Provides improved configuration management with better structure,
 * caching, validation, and PSR compliance.
 */
class EnhancedConfigManager
{
    /**
     * The application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * Configuration cache
     *
     * @var array
     */
    protected $config = [];

    /**
     * Environment variables cache
     *
     * @var array
     */
    protected $env = [];

    /**
     * Configuration file paths
     *
     * @var array
     */
    protected $configPaths = [];

    /**
     * Whether configuration has been loaded
     *
     * @var bool
     */
    protected $loaded = false;

    /**
     * Default configuration values
     *
     * @var array
     */
    protected $defaults = [
        'app.name' => 'CosmicHub',
        'app.env' => 'production',
        'app.debug' => false,
        'app.timezone' => 'UTC',
        'database.default' => 'mysql',
        'session.driver' => 'file',
        'logging.default' => 'daily',
    ];

    /**
     * Create a new configuration manager instance
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->configPaths = [
            $app->getBasePath() . '/config',
            $app->getBasePath() . '/app/Config',
        ];
    }

    /**
     * Load all configuration
     *
     * @return void
     * @throws ConfigException
     */
    public function load()
    {
        if ($this->loaded) {
            return;
        }

        $this->loadEnvironment();
        $this->loadConfigurationFiles();
        $this->validateConfiguration();
        $this->loaded = true;
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
        if (!$this->loaded) {
            $this->load();
        }

        return $this->getValue($this->config, $key, $default);
    }

    /**
     * Set a configuration value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->setValue($this->config, $key, $value);
    }

    /**
     * Check if a configuration key exists
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->hasValue($this->config, $key);
    }

    /**
     * Get all configuration
     *
     * @return array
     */
    public function all()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->config;
    }

    /**
     * Get an environment variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function env($key, $default = null)
    {
        if (isset($this->env[$key])) {
            return $this->env[$key];
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        // Convert string representations to proper types
        $value = $this->convertEnvValue($value);
        $this->env[$key] = $value;

        return $value;
    }

    /**
     * Load environment variables from .env file
     *
     * @return void
     * @throws ConfigException
     */
    protected function loadEnvironment()
    {
        $envFile = $this->app->getBasePath() . '/.env';

        if (!file_exists($envFile)) {
            throw new ConfigException("Environment file not found: {$envFile}");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Skip comments
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * Load configuration files
     *
     * @return void
     * @throws ConfigException
     */
    protected function loadConfigurationFiles()
    {
        foreach ($this->configPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '/*.php');

            foreach ($files as $file) {
                $key = basename($file, '.php');
                $config = require $file;

                if (!is_array($config)) {
                    throw new ConfigException("Configuration file {$file} must return an array");
                }

                $this->config[$key] = $config;
            }
        }

        // Apply defaults for missing values
        foreach ($this->defaults as $key => $value) {
            if (!$this->hasValue($this->config, $key)) {
                $this->setValue($this->config, $key, $value);
            }
        }
    }

    /**
     * Validate configuration
     *
     * @return void
     * @throws ConfigException
     */
    protected function validateConfiguration()
    {
        $required = [
            'app.name',
            'app.env',
            'database.default',
        ];

        foreach ($required as $key) {
            if (!$this->hasValue($this->config, $key)) {
                throw new ConfigException("Required configuration key missing: {$key}");
            }
        }

        // Validate environment
        $validEnvs = ['local', 'development', 'testing', 'staging', 'production'];
        $env = $this->getValue($this->config, 'app.env');
        
        if (!in_array($env, $validEnvs)) {
            throw new ConfigException("Invalid environment: {$env}. Must be one of: " . implode(', ', $validEnvs));
        }
    }

    /**
     * Get a value from array using dot notation
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getValue(array $array, $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set a value in array using dot notation
     *
     * @param array &$array
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setValue(array &$array, $key, $value)
    {
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
     * Check if a value exists in array using dot notation
     *
     * @param array $array
     * @param string $key
     * @return bool
     */
    protected function hasValue(array $array, $key)
    {
        if (isset($array[$key])) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Convert environment variable value to proper type
     *
     * @param string $value
     * @return mixed
     */
    protected function convertEnvValue($value)
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
     * Reload configuration
     *
     * @return void
     */
    public function reload()
    {
        $this->config = [];
        $this->env = [];
        $this->loaded = false;
        $this->load();
    }

    /**
     * Get configuration for a specific group
     *
     * @param string $group
     * @return array
     */
    public function getGroup($group)
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->config[$group] ?? [];
    }

    /**
     * Check if the application is in debug mode
     *
     * @return bool
     */
    public function isDebug()
    {
        return (bool) $this->get('app.debug', false);
    }

    /**
     * Get the application environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->get('app.env', 'production');
    }

    /**
     * Check if the application is in a specific environment
     *
     * @param string|array $environments
     * @return bool
     */
    public function isEnvironment($environments)
    {
        $environments = is_array($environments) ? $environments : [$environments];
        return in_array($this->getEnvironment(), $environments);
    }
}