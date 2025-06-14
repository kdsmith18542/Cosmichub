<?php

namespace App\Core;

use App\Core\Container;

/**
 * Enhanced ServiceProvider base class for all service providers
 * 
 * This class has been enhanced following the refactoring plan to improve
 * dependency injection, service registration patterns, and provide better
 * configuration management capabilities.
 */
abstract class ServiceProvider
{
    /**
     * @var Application The application instance
     */
    protected $app;
    
    /**
     * @var Container The container instance
     */
    protected $container;
    
    /**
     * @var bool Whether the provider has been registered
     */
    protected $registered = false;
    
    /**
     * @var bool Whether the provider has been booted
     */
    protected $booted = false;
    
    /**
     * @var array Services provided by this provider
     */
    protected $provides = [];
    
    /**
     * @var array Aliases for services
     */
    protected $aliases = [];
    
    /**
     * @var array Singletons to register
     */
    protected $singletons = [];
    
    /**
     * @var array Bindings to register
     */
    protected $bindings = [];
    
    /**
     * Create a new service provider instance
     * 
     * @param Application $app The application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->container = $app->getContainer();
    }
    
    /**
     * Register any application services
     * 
     * @return void
     */
    public function register()
    {
        if ($this->registered) {
            return;
        }
        
        // Register bindings
        $this->registerBindings();
        
        // Register singletons
        $this->registerSingletons();
        
        // Register aliases
        $this->registerAliases();
        
        // Call the provider-specific registration
        $this->registerServices();
        
        $this->registered = true;
    }
    
    /**
     * Boot any application services
     * 
     * @return void
     */
    public function boot()
    {
        if ($this->booted || !$this->registered) {
            return;
        }
        
        // Call the provider-specific boot method
        $this->bootServices();
        
        $this->booted = true;
    }
    
    /**
     * Register bindings defined in the $bindings property
     *
     * @return void
     */
    protected function registerBindings()
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->container->bind($abstract, $concrete);
        }
    }
    
    /**
     * Register singletons defined in the $singletons property
     *
     * @return void
     */
    protected function registerSingletons()
    {
        foreach ($this->singletons as $abstract => $concrete) {
            $this->container->singleton($abstract, $concrete);
        }
    }
    
    /**
     * Register aliases defined in the $aliases property
     *
     * @return void
     */
    protected function registerAliases()
    {
        foreach ($this->aliases as $alias => $abstract) {
            if (method_exists($this->container, 'alias')) {
                $this->container->alias($alias, $abstract);
            }
        }
    }
    
    /**
     * Register services specific to this provider
     * Override this method in concrete providers for custom registration logic
     * 
     * @return void
     */
    public function registerServices()
    {
        // Override in concrete providers
    }
    
    /**
     * Boot services specific to this provider
     * Override this method in concrete providers for custom boot logic
     * 
     * @return void
     */
    public function bootServices()
    {
        // Override in concrete providers
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
     * Get the events that trigger this service provider to register
     * 
     * @return array
     */
    public function when()
    {
        return [];
    }
    
    /**
     * Check if the provider is deferred
     *
     * @return bool
     */
    public function isDeferred()
    {
        return false;
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
     * Bind a service in the container
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
     * Register a singleton in the container
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
     * Register an instance in the container
     *
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    protected function instance($abstract, $instance)
    {
        $this->container->instance($abstract, $instance);
    }
    
    /**
     * Resolve a service from the container
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    protected function make($abstract, array $parameters = [])
    {
        return $this->container->make($abstract, $parameters);
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
        try {
            $config = $this->container->get('config');
        } catch (\Exception $e) {
            // Config service not available yet, return default
            return $default;
        }
        
        if (is_array($config)) {
            $keys = explode('.', $key);
            $value = $config;
            
            foreach ($keys as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    return $default;
                }
                
                $value = $value[$segment];
            }
            
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Get an environment variable with type casting
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function env($key, $default = null)
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Cast boolean values
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
        
        return $value;
    }
}