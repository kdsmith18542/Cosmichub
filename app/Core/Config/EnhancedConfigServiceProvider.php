<?php

namespace App\Core\Config;

use App\Core\ServiceProvider;
use App\Core\Application;
use App\Core\Container;
use App\Core\Config\Config;
use App\Core\Config\Exceptions\ConfigException;
use Psr\Log\LoggerInterface;

/**
 * Enhanced Configuration Service Provider
 * 
 * Registers the enhanced configuration manager in the container
 */
class EnhancedConfigServiceProvider extends ServiceProvider
{
    /**
     * The provided services
     *
     * @var array
     */
    protected $provides = [
        Config::class,
        'config',
        'config.enhanced',
        'config.manager'
    ];

    /**
     * The services to be registered as singletons
     *
     * @var array
     */
    protected $singletons = [
        Config::class
    ];

    /**
     * The service aliases
     *
     * @var array
     */
    protected $aliases = [
        'config' => Config::class,
        'config.enhanced' => Config::class,
        'config.manager' => Config::class
    ];

    /**
     * Register the service provider
     *
     * @return void
     */


    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function register()
    {
        $this->registerGlobalHelpers();
        $this->registerConfigManager();
        $this->registerAliases();
    }

    /**
     * Boot the service provider
     *
     * @return void
     */
    public function boot()
    {
        // Configuration is already loaded in bootstrap/app.php
        // Global helpers are already registered in register method
    }

    /**
     * Register the enhanced configuration manager
     *
     * @return void
     */
    protected function registerConfigManager()
    {
        $this->app->singleton(Config::class, function (Container $container) {
            // Get environment from app or default to production
            // Get environment from app or default to production
            $environment = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';
            
            // Create Config instance with environment support
            $config = new Config([], $environment);

            // Load configuration from directory if it exists
            $configDir = defined('CONFIG_DIR') ? CONFIG_DIR : $this->app->getBasePath() . '/config';
            if (is_dir($configDir)) {
                try {
                    $config->loadFromDirectory($configDir);
                } catch (ConfigException $e) {
                    // Log the exception or handle it as appropriate
                }
            } else {
                // Attempt to load individual config files if directory not found
                $this->loadIndividualConfigFiles($config, $configDir, $container);
            }
            
            return $config;
        });
    }

    /**
     * Register aliases for the configuration service
     *
     * @return void
     */
    protected function registerAliases()
    {
        foreach ($this->aliases as $alias => $abstract) {
            $this->container->alias($alias, $abstract);
        }
    }

    /**
     * Load individual config files as fallback
     *
     * @param Config $config
     * @param string $configDir
     * @return void
     */
    protected function loadIndividualConfigFiles(Config $config, $configDir, Container $container)
    {
        $files = glob($configDir . '/*.php');
        foreach ($files as $file) {
            try {
                $config->load($file);
            } catch (ConfigException $e) {
                $container->get(LoggerInterface::class)->error('Failed to load config file ' . $file . ': ' . $e->getMessage(), ['exception' => $e, 'file' => $file]);
            }
        }
    }

    /**
     * Register global helper functions
     *
     * @return void
     */
    protected function registerGlobalHelpers()
    {

        
        // Register resource_path helper if not already defined
        if (!function_exists('resource_path')) {
            /**
             * Get the path to the resources directory
             *
             * @param string $path
             * @return string
             */
            function resource_path($path = '')
            {
                try {
                    $app = \App\Core\Application::getInstance();
                    $basePath = $app->getBasePath();
                    return $basePath . '/resources' . ($path ? '/' . ltrim($path, '/') : '');
                } catch (\Exception $e) {
                    // Fallback to a reasonable default
                    return __DIR__ . '/../../../resources' . ($path ? '/' . ltrim($path, '/') : '');
                }
            }
        }
    }

    /**
     * Get the services provided by the provider
     *
     * @return array
     */
    public function provides()
    {
        return $this->provides;
    }
}