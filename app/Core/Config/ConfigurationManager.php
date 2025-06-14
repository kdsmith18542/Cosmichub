<?php

namespace App\Core\Config;

use App\Core\Application;
use App\Core\Exceptions\ConfigException;
use App\Core\Config\Contracts\ConfigurationInterface;
use App\Core\Config\Loaders\FileLoader;
use App\Core\Config\Loaders\EnvironmentLoader;
use App\Core\Config\Cache\ConfigCache;
use App\Core\Config\Validators\ConfigValidator;
use InvalidArgumentException;

/**
 * Enhanced Configuration Manager
 * 
 * Provides comprehensive configuration management with:
 * - Environment-specific configurations
 * - Type-safe configuration classes
 * - Configuration caching
 * - Validation and schema support
 * - Hot reloading in development
 */
class ConfigurationManager implements ConfigurationInterface
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
     * Configuration file loader
     *
     * @var FileLoader
     */
    protected $fileLoader;

    /**
     * Environment loader
     *
     * @var EnvironmentLoader
     */
    protected $envLoader;

    /**
     * Configuration cache manager
     *
     * @var ConfigCache
     */
    protected $cache;

    /**
     * Configuration validator
     *
     * @var ConfigValidator
     */
    protected $validator;

    /**
     * Whether configuration has been loaded
     *
     * @var bool
     */
    protected $loaded = false;

    /**
     * Configuration schemas
     *
     * @var array
     */
    protected $schemas = [];

    /**
     * Environment-specific configuration paths
     *
     * @var array
     */
    protected $environmentPaths = [];

    /**
     * Create a new configuration manager instance
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->fileLoader = new FileLoader();
        $this->envLoader = new EnvironmentLoader();
        $this->cache = new ConfigCache($app);
        $this->validator = new ConfigValidator();
        
        $this->initializePaths();
    }

    /**
     * Initialize configuration paths
     *
     * @return void
     */
    protected function initializePaths()
    {
        $basePath = $this->app->getBasePath();
        
        $this->environmentPaths = [
            'base' => $basePath . '/config',
            'environments' => $basePath . '/config/environments',
            'local' => $basePath . '/config/local',
        ];
    }

    /**
     * Load all configuration
     *
     * @param bool $force Force reload even if already loaded
     * @return void
     * @throws ConfigException
     */
    public function load($force = false)
    {
        if ($this->loaded && !$force) {
            return;
        }

        // Try to load from cache first (in production)
        if ($this->shouldUseCache() && $this->cache->exists()) {
            $this->config = $this->cache->get();
            $this->loaded = true;
            return;
        }

        // Load environment variables first
        $this->loadEnvironment();
        
        // Load base configuration
        $this->loadBaseConfiguration();
        
        // Load environment-specific configuration
        $this->loadEnvironmentConfiguration();
        
        // Load local overrides
        $this->loadLocalConfiguration();
        
        // Validate configuration
        $this->validateConfiguration();
        
        // Cache the configuration (in production)
        if ($this->shouldUseCache()) {
            $this->cache->put($this->config);
        }
        
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
        
        // Invalidate cache when configuration changes
        if ($this->shouldUseCache()) {
            $this->cache->forget();
        }
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

        $value = $this->envLoader->get($key, $default);
        $this->env[$key] = $value;

        return $value;
    }

    /**
     * Load environment variables
     *
     * @return void
     * @throws ConfigException
     */
    protected function loadEnvironment()
    {
        $environment = $this->determineEnvironment();
        
        // Load base .env file
        $this->envLoader->load($this->app->getBasePath() . '/.env');
        
        // Load environment-specific .env file
        $envFile = $this->app->getBasePath() . "/.env.{$environment}";
        if (file_exists($envFile)) {
            $this->envLoader->load($envFile);
        }
        
        // Load local .env file (for local overrides)
        $localEnvFile = $this->app->getBasePath() . '/.env.local';
        if (file_exists($localEnvFile)) {
            $this->envLoader->load($localEnvFile);
        }
    }

    /**
     * Load base configuration files
     *
     * @return void
     */
    protected function loadBaseConfiguration()
    {
        $this->config = $this->fileLoader->loadDirectory($this->environmentPaths['base']);
    }

    /**
     * Load environment-specific configuration
     *
     * @return void
     */
    protected function loadEnvironmentConfiguration()
    {
        $environment = $this->env('APP_ENV', 'production');
        $envPath = $this->environmentPaths['environments'] . '/' . $environment;
        
        if (is_dir($envPath)) {
            $envConfig = $this->fileLoader->loadDirectory($envPath);
            $this->config = $this->mergeConfigurations($this->config, $envConfig);
        }
    }

    /**
     * Load local configuration overrides
     *
     * @return void
     */
    protected function loadLocalConfiguration()
    {
        $localPath = $this->environmentPaths['local'];
        
        if (is_dir($localPath)) {
            $localConfig = $this->fileLoader->loadDirectory($localPath);
            $this->config = $this->mergeConfigurations($this->config, $localConfig);
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
        foreach ($this->schemas as $group => $schema) {
            if (isset($this->config[$group])) {
                $this->validator->validate($this->config[$group], $schema, $group);
            }
        }
    }

    /**
     * Determine the current environment
     *
     * @return string
     */
    protected function determineEnvironment()
    {
        // Check command line argument first
        if (isset($_SERVER['argv'])) {
            foreach ($_SERVER['argv'] as $arg) {
                if (strpos($arg, '--env=') === 0) {
                    return substr($arg, 6);
                }
            }
        }
        
        // Check environment variable
        return $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
    }

    /**
     * Merge two configuration arrays
     *
     * @param array $base
     * @param array $override
     * @return array
     */
    protected function mergeConfigurations(array $base, array $override)
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeConfigurations($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        
        return $base;
    }

    /**
     * Check if configuration caching should be used
     *
     * @return bool
     */
    protected function shouldUseCache()
    {
        return $this->env('APP_ENV') === 'production' && $this->env('CONFIG_CACHE', true);
    }

    /**
     * Register a configuration schema
     *
     * @param string $group
     * @param array $schema
     * @return void
     */
    public function registerSchema($group, array $schema)
    {
        $this->schemas[$group] = $schema;
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
        $this->cache->forget();
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