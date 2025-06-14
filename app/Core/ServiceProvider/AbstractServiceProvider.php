<?php

namespace App\Core\ServiceProvider;

use App\Core\Application;
use App\Core\Container;

/**
 * Abstract Service Provider
 *
 * Enhanced base class for service providers following the refactoring plan
 * to improve dependency injection and service registration patterns.
 */
abstract class AbstractServiceProvider
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
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->container = $app->getContainer();
    }
    
    /**
     * Register services in the container
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
     * Boot services after all providers have been registered
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
            $this->container->alias($alias, $abstract);
        }
    }
    
    /**
     * Register services specific to this provider
     * Override this method in concrete providers
     *
     * @return void
     */
    protected function registerServices()
    {
        // Override in concrete providers
    }
    
    /**
     * Boot services specific to this provider
     * Override this method in concrete providers
     *
     * @return void
     */
    protected function bootServices()
    {
        // Override in concrete providers
    }
    
    /**
     * Get the services provided by this provider
     *
     * @return array
     */
    public function provides()
    {
        return $this->provides;
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
     * Get the application instance
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->app;
    }
    
    /**
     * Get the container instance
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
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
     * Register an alias in the container
     *
     * @param string $alias
     * @param string $abstract
     * @return void
     */
    protected function alias($alias, $abstract)
    {
        $this->container->alias($alias, $abstract);
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
        $config = $this->container->get('config');
        
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
     * Get an environment variable
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