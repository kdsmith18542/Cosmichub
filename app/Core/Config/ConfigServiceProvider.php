<?php

namespace App\Core\Config;

use App\Core\ServiceProvider;
use App\Core\Config\ConfigurationManager;
use App\Core\Config\Contracts\ConfigurationInterface;
use App\Core\Config\Loaders\FileLoader;
use App\Core\Config\Loaders\EnvironmentLoader;
use App\Core\Config\Cache\ConfigCache;
use App\Core\Config\Validation\ConfigValidator;
use Psr\Log\LoggerInterface;

/**
 * Enhanced Configuration Service Provider
 *
 * This service provider provides comprehensive configuration management with:
 * - Environment-specific configurations
 * - Type-safe configuration access
 * - Configuration caching for performance
 * - Configuration validation
 * - Environment variable loading and parsing
 */
class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register the configuration services
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfigurationLoaders();
        $this->registerConfigurationCache();
        $this->registerConfigurationValidator();
        $this->registerConfigurationManager();
    }

    /**
     * Boot the configuration services
     *
     * @return void
     */
    public function boot()
    {
        /** @var ConfigurationManager $config */
        $config = $this->container->get('config');
        
        // Load configuration
        $config->load();
        
        // Validate required configuration
        $this->validateRequiredConfiguration($config);
        
        // Set application timezone if configured
        $this->setApplicationTimezone($config);
        
        // Set application locale if configured
        $this->setApplicationLocale($config);
    }

    /**
     * Register configuration loaders
     *
     * @return void
     */
    protected function registerConfigurationLoaders()
    {
        $this->container->singleton('config.file_loader', function ($container) {
            return new FileLoader();
        });

        $this->container->singleton('config.environment_loader', function ($container) {
            return new EnvironmentLoader();
        });

        $this->container->alias('config.file_loader', FileLoader::class);
        $this->container->alias('config.environment_loader', EnvironmentLoader::class);
    }

    /**
     * Register configuration cache
     *
     * @return void
     */
    protected function registerConfigurationCache()
    {
        $this->container->singleton('config.cache', function ($container) {
            $basePath = $container->get('app')->getBasePath();
            return new ConfigCache($basePath);
        });

        $this->container->alias('config.cache', ConfigCache::class);
    }

    /**
     * Register configuration validator
     *
     * @return void
     */
    protected function registerConfigurationValidator()
    {
        $this->container->singleton('config.validator', function ($container) {
            return new ConfigValidator();
        });

        $this->container->alias('config.validator', ConfigValidator::class);
    }

    /**
     * Register the main configuration manager
     *
     * @return void
     */
    protected function registerConfigurationManager()
    {
        $this->container->singleton('config', function ($container) {
            $app = $container->get('app');
            $basePath = $app->getBasePath();
            $environment = $app->getEnvironment() ?? 'production';
            
            return new ConfigurationManager(
                $basePath,
                $environment,
                $container->get('config.file_loader'),
                $container->get('config.environment_loader'),
                $container->get('config.cache'),
                $container->get('config.validator')
            );
        });

        $this->container->alias('config', ConfigurationManager::class);
        $this->container->alias('config', ConfigurationInterface::class);
    }

    /**
     * Validate required configuration keys
     *
     * @param ConfigurationManager $config
     * @return void
     */
    protected function validateRequiredConfiguration(ConfigurationManager $config)
    {
        $requiredKeys = [
            'app.name',
            'app.env',
            'app.key',
            'app.debug',
            'app.url',
            'app.timezone',
            'app.locale',
        ];

        try {
            $config->validateRequired($requiredKeys);
        } catch (\Exception $e) {
            // Log the validation error but don't halt the application
            if ($this->container->has(LoggerInterface::class)) {
                $this->container->get(LoggerInterface::class)->error('Configuration validation failed: ' . $e->getMessage(), ['exception' => $e]);
            }
        }
    }

    /**
     * Set application timezone from configuration
     *
     * @param ConfigurationManager $config
     * @return void
     */
    protected function setApplicationTimezone(ConfigurationManager $config)
    {
        $timezone = $config->get('app.timezone', 'UTC');
        
        if ($timezone && function_exists('date_default_timezone_set')) {
            date_default_timezone_set($timezone);
        }
    }

    /**
     * Set application locale from configuration
     *
     * @param ConfigurationManager $config
     * @return void
     */
    protected function setApplicationLocale(ConfigurationManager $config)
    {
        $locale = $config->get('app.locale', 'en');
        
        if ($locale && function_exists('setlocale')) {
            setlocale(LC_ALL, $locale);
        }
    }

    /**
     * Get the services provided by the provider
     *
     * @return array
     */
    public function provides()
    {
        return [
            'config',
            'config.file_loader',
            'config.environment_loader',
            'config.cache',
            'config.validator',
            ConfigurationManager::class,
            ConfigurationInterface::class,
            FileLoader::class,
            EnvironmentLoader::class,
            ConfigCache::class,
            ConfigValidator::class,
        ];
    }
}