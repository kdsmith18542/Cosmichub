<?php

namespace App\Core\Providers;

use App\Core\Container\Container;
use App\Core\Config\Configuration;
use App\Core\Events\EventDispatcher;
use App\Core\Logging\Logger;

/**
 * Base Service Provider Class
 * 
 * Provides a foundation for registering services, bindings, and configurations
 * in the application container with support for deferred loading and booting
 */
abstract class ServiceProvider
{
    /**
     * @var Container The container instance
     */
    protected $container;
    
    /**
     * @var Configuration The configuration instance
     */
    protected $config;
    
    /**
     * @var EventDispatcher The event dispatcher instance
     */
    protected $events;
    
    /**
     * @var Logger The logger instance
     */
    protected $logger;
    
    /**
     * @var array The services provided by this provider
     */
    protected $provides = [];
    
    /**
     * @var bool Indicates if loading of the provider is deferred
     */
    protected $defer = false;
    
    /**
     * @var array The event listeners to register
     */
    protected $listen = [];
    
    /**
     * @var array The middleware to register
     */
    protected $middleware = [];
    
    /**
     * @var array The commands to register
     */
    protected $commands = [];
    
    /**
     * @var array The configuration files to publish
     */
    protected $publishes = [];
    
    /**
     * @var array The view paths to register
     */
    protected $viewPaths = [];
    
    /**
     * @var array The translation paths to register
     */
    protected $translationPaths = [];
    
    /**
     * @var bool Whether the provider has been booted
     */
    protected $booted = false;
    
    /**
     * @var bool Whether the provider has been registered
     */
    protected $registered = false;
    
    /**
     * Create a new service provider instance
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        
        // Resolve common services if available
        if ($container->has(Configuration::class)) {
            $this->config = $container->get(Configuration::class);
        }
        
        if ($container->has(EventDispatcher::class)) {
            $this->events = $container->get(EventDispatcher::class);
        }
        
        if ($container->has(Logger::class)) {
            $this->logger = $container->get(Logger::class);
        }
    }
    
    /**
     * Register services in the container
     * 
     * @return void
     */
    abstract public function register();
    
    /**
     * Boot the service provider
     * 
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }
        
        $this->registerEventListeners();
        $this->registerMiddleware();
        $this->registerCommands();
        $this->registerViewPaths();
        $this->registerTranslationPaths();
        $this->publishResources();
        
        $this->booted = true;
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
    
    /**
     * Determine if the provider is deferred
     * 
     * @return bool
     */
    public function isDeferred()
    {
        return $this->defer;
    }
    
    /**
     * Check if the provider has been registered
     * 
     * @return bool
     */
    public function isRegistered()
    {
        return $this->registered;
    }
    
    /**
     * Check if the provider has been booted
     * 
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }
    
    /**
     * Mark the provider as registered
     * 
     * @return $this
     */
    public function markAsRegistered()
    {
        $this->registered = true;
        return $this;
    }
    
    /**
     * Register a binding with the container
     * 
     * @param string $abstract
     * @param mixed $concrete
     * @param bool $shared
     * @return void
     */
    protected function bind($abstract, $concrete = null, $shared = false)
    {
        $this->container->bind($abstract, $concrete, $shared);
    }
    
    /**
     * Register a singleton binding with the container
     * 
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    protected function singleton($abstract, $concrete = null)
    {
        $this->container->singleton($abstract, $concrete);
    }
    
    /**
     * Register an instance with the container
     * 
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    protected function instance($abstract, $instance)
    {
        return $this->container->instance($abstract, $instance);
    }
    
    /**
     * Register an alias with the container
     * 
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    protected function alias($abstract, $alias)
    {
        $this->container->alias($abstract, $alias);
    }
    
    /**
     * Register event listeners
     * 
     * @return void
     */
    protected function registerEventListeners()
    {
        if (!$this->events || empty($this->listen)) {
            return;
        }
        
        foreach ($this->listen as $event => $listeners) {
            foreach ((array) $listeners as $listener) {
                $this->events->listen($event, $listener);
            }
        }
    }
    
    /**
     * Register middleware
     * 
     * @return void
     */
    protected function registerMiddleware()
    {
        // Override in subclasses to register middleware
    }
    
    /**
     * Register commands
     * 
     * @return void
     */
    protected function registerCommands()
    {
        // Override in subclasses to register commands
    }
    
    /**
     * Register view paths
     * 
     * @return void
     */
    protected function registerViewPaths()
    {
        // Override in subclasses to register view paths
    }
    
    /**
     * Register translation paths
     * 
     * @return void
     */
    protected function registerTranslationPaths()
    {
        // Override in subclasses to register translation paths
    }
    
    /**
     * Publish resources
     * 
     * @return void
     */
    protected function publishResources()
    {
        // Override in subclasses to publish resources
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function config($key, $default = null)
    {
        return $this->config ? $this->config->get($key, $default) : $default;
    }
    
    /**
     * Log a message
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log($level, $message, array $context = [])
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
    
    /**
     * Dispatch an event
     * 
     * @param string|object $event
     * @param array $payload
     * @return mixed
     */
    protected function dispatch($event, array $payload = [])
    {
        if ($this->events) {
            return $this->events->dispatch($event, $payload);
        }
        
        return null;
    }
    
    /**
     * Get the container instance
     * 
     * @return Container
     */
    protected function getContainer()
    {
        return $this->container;
    }
    
    /**
     * Get the configuration instance
     * 
     * @return Configuration|null
     */
    protected function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Get the event dispatcher instance
     * 
     * @return EventDispatcher|null
     */
    protected function getEvents()
    {
        return $this->events;
    }
    
    /**
     * Get the logger instance
     * 
     * @return Logger|null
     */
    protected function getLogger()
    {
        return $this->logger;
    }
    
    /**
     * Merge configuration arrays
     * 
     * @param array $original
     * @param array $new
     * @return array
     */
    protected function mergeConfig(array $original, array $new)
    {
        return array_merge_recursive($original, $new);
    }
    
    /**
     * Load configuration from a file
     * 
     * @param string $path
     * @param string|null $key
     * @return array
     */
    protected function loadConfig($path, $key = null)
    {
        if (!file_exists($path)) {
            return [];
        }
        
        $config = require $path;
        
        if ($key && $this->config) {
            $this->config->set($key, $config);
        }
        
        return $config;
    }
    
    /**
     * Register a deferred service
     * 
     * @param string $service
     * @param \Closure $resolver
     * @return void
     */
    protected function defer($service, \Closure $resolver)
    {
        $this->container->bind($service, $resolver, true);
        $this->provides[] = $service;
    }
    
    /**
     * Call a method on the provider
     * 
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function callMethod($method, array $parameters = [])
    {
        if (method_exists($this, $method)) {
            return $this->container->call([$this, $method], $parameters);
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist on " . static::class);
    }
    
    /**
     * Get the provider class name
     * 
     * @return string
     */
    public function getProviderName()
    {
        return static::class;
    }
    
    /**
     * Convert the provider to an array
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'class' => static::class,
            'provides' => $this->provides(),
            'deferred' => $this->isDeferred(),
            'registered' => $this->isRegistered(),
            'booted' => $this->isBooted()
        ];
    }
    
    /**
     * Convert the provider to JSON
     * 
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * String representation of the provider
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getProviderName();
    }
}