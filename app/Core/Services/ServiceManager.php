<?php

namespace App\Core\Services;

use App\Core\Container\Container;
use App\Core\Config\Configuration;
use App\Core\Events\EventDispatcher;
use App\Core\Logging\Logger;
use App\Core\Cache\Cache;
use App\Core\Validation\Validator;
use App\Core\Providers\ServiceProvider;
use App\Core\Exceptions\ServiceException;

/**
 * Service Manager Class
 * 
 * Central orchestrator for managing services, providers, and the service lifecycle
 * Provides service discovery, registration, booting, and dependency management
 */
class ServiceManager
{
    /**
     * @var Container The container instance
     */
    protected $container;
    
    /**
     * @var array Registered service providers
     */
    protected $providers = [];
    
    /**
     * @var array Booted service providers
     */
    protected $bootedProviders = [];
    
    /**
     * @var array Deferred services
     */
    protected $deferredServices = [];
    
    /**
     * @var array Service aliases
     */
    protected $aliases = [];
    
    /**
     * @var array Service tags
     */
    protected $tags = [];
    
    /**
     * @var array Service middleware
     */
    protected $middleware = [];
    
    /**
     * @var array Service decorators
     */
    protected $decorators = [];
    
    /**
     * @var array Service lifecycle hooks
     */
    protected $hooks = [
        'before_register' => [],
        'after_register' => [],
        'before_boot' => [],
        'after_boot' => [],
        'before_resolve' => [],
        'after_resolve' => []
    ];
    
    /**
     * @var bool Whether the manager has been booted
     */
    protected $booted = false;
    
    /**
     * @var array Manager statistics
     */
    protected $stats = [
        'providers_registered' => 0,
        'providers_booted' => 0,
        'services_resolved' => 0,
        'deferred_services' => 0
    ];
    
    /**
     * @var Logger Logger instance
     */
    protected $logger;
    
    /**
     * @var EventDispatcher Event dispatcher instance
     */
    protected $events;
    
    /**
     * Create a new service manager instance
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        
        // Register core services
        $this->registerCoreServices();
        
        // Get logger and events if available
        if ($container->has(Logger::class)) {
            $this->logger = $container->get(Logger::class);
        }
        
        if ($container->has(EventDispatcher::class)) {
            $this->events = $container->get(EventDispatcher::class);
        }
    }
    
    /**
     * Register a service provider
     * 
     * @param string|ServiceProvider $provider
     * @param bool $force
     * @return ServiceProvider
     * @throws ServiceException
     */
    public function register($provider, $force = false)
    {
        if (is_string($provider)) {
            $provider = $this->createProvider($provider);
        }
        
        if (!$provider instanceof ServiceProvider) {
            throw ServiceException::invalidArgument('Provider must be a ServiceProvider instance or class name');
        }
        
        $providerName = get_class($provider);
        
        // Check if already registered
        if (isset($this->providers[$providerName]) && !$force) {
            return $this->providers[$providerName];
        }
        
        $this->executeHooks('before_register', $provider);
        
        try {
            // Register the provider
            $provider->register();
            $provider->markAsRegistered();
            
            $this->providers[$providerName] = $provider;
            $this->stats['providers_registered']++;
            
            // Handle deferred services
            if ($provider->isDeferred()) {
                $this->registerDeferredServices($provider);
            }
            
            $this->executeHooks('after_register', $provider);
            
            $this->log('info', "Service provider registered: {$providerName}");
            
            // Boot immediately if manager is already booted and provider is not deferred
            if ($this->booted && !$provider->isDeferred()) {
                $this->bootProvider($provider);
            }
            
            return $provider;
        } catch (\Exception $e) {
            throw ServiceException::operationFailed("Failed to register provider {$providerName}", $e);
        }
    }
    
    /**
     * Boot all registered service providers
     * 
     * @return $this
     */
    public function boot()
    {
        if ($this->booted) {
            return $this;
        }
        
        foreach ($this->providers as $provider) {
            if (!$provider->isDeferred()) {
                $this->bootProvider($provider);
            }
        }
        
        $this->booted = true;
        
        $this->log('info', 'Service manager booted');
        
        return $this;
    }
    
    /**
     * Boot a specific service provider
     * 
     * @param ServiceProvider $provider
     * @return void
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        $providerName = get_class($provider);
        
        if (isset($this->bootedProviders[$providerName])) {
            return;
        }
        
        $this->executeHooks('before_boot', $provider);
        
        try {
            $provider->boot();
            $this->bootedProviders[$providerName] = $provider;
            $this->stats['providers_booted']++;
            
            $this->executeHooks('after_boot', $provider);
            
            $this->log('debug', "Service provider booted: {$providerName}");
        } catch (\Exception $e) {
            $this->log('error', "Failed to boot provider {$providerName}: {$e->getMessage()}");
            throw ServiceException::operationFailed("Failed to boot provider {$providerName}", $e);
        }
    }
    
    /**
     * Resolve a service from the container
     * 
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function resolve($abstract, array $parameters = [])
    {
        $this->executeHooks('before_resolve', $abstract);
        
        // Check if it's a deferred service
        if (isset($this->deferredServices[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }
        
        try {
            $service = $this->container->get($abstract);
            $this->stats['services_resolved']++;
            
            $this->executeHooks('after_resolve', $service);
            
            return $service;
        } catch (\Exception $e) {
            throw ServiceException::operationFailed("Failed to resolve service {$abstract}", $e);
        }
    }
    
    /**
     * Register core services
     * 
     * @return void
     */
    protected function registerCoreServices()
    {
        // Register the service manager itself
        $this->container->instance(static::class, $this);
        $this->container->alias('services', static::class);
        
        // Register core service bindings
        $this->container->singleton(Configuration::class, function ($container) {
            return new Configuration();
        });
        
        $this->container->singleton(EventDispatcher::class, function ($container) {
            return new EventDispatcher($container);
        });
        
        $this->container->singleton(Logger::class, function ($container) {
            return Logger::createFileLogger();
        });
        
        $this->container->singleton(Cache::class, function ($container) {
            return new Cache($container);
        });
        
        $this->container->singleton(Validator::class, function ($container) {
            return new Validator();
        });
        
        // Register aliases
        $this->container->alias('config', Configuration::class);
        $this->container->alias('events', EventDispatcher::class);
        $this->container->alias('log', Logger::class);
        $this->container->alias('cache', Cache::class);
        $this->container->alias('validator', Validator::class);
    }
    
    /**
     * Create a service provider instance
     * 
     * @param string $providerClass
     * @return ServiceProvider
     * @throws ServiceException
     */
    protected function createProvider($providerClass)
    {
        if (!class_exists($providerClass)) {
            throw ServiceException::notFound("Provider class {$providerClass} not found");
        }
        
        if (!is_subclass_of($providerClass, ServiceProvider::class)) {
            throw ServiceException::invalidArgument("Class {$providerClass} must extend ServiceProvider");
        }
        
        return new $providerClass($this->container);
    }
    
    /**
     * Register deferred services
     * 
     * @param ServiceProvider $provider
     * @return void
     */
    protected function registerDeferredServices(ServiceProvider $provider)
    {
        $providerName = get_class($provider);
        
        foreach ($provider->provides() as $service) {
            $this->deferredServices[$service] = $providerName;
            $this->stats['deferred_services']++;
        }
    }
    
    /**
     * Load a deferred service provider
     * 
     * @param string $service
     * @return void
     */
    protected function loadDeferredProvider($service)
    {
        if (!isset($this->deferredServices[$service])) {
            return;
        }
        
        $providerName = $this->deferredServices[$service];
        
        if (isset($this->bootedProviders[$providerName])) {
            return;
        }
        
        if (isset($this->providers[$providerName])) {
            $this->bootProvider($this->providers[$providerName]);
        }
        
        unset($this->deferredServices[$service]);
    }
    
    /**
     * Add a lifecycle hook
     * 
     * @param string $event
     * @param callable $callback
     * @return $this
     */
    public function addHook($event, callable $callback)
    {
        if (!isset($this->hooks[$event])) {
            $this->hooks[$event] = [];
        }
        
        $this->hooks[$event][] = $callback;
        
        return $this;
    }
    
    /**
     * Execute lifecycle hooks
     * 
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    protected function executeHooks($event, $payload = null)
    {
        if (!isset($this->hooks[$event])) {
            return;
        }
        
        foreach ($this->hooks[$event] as $callback) {
            try {
                call_user_func($callback, $payload, $this);
            } catch (\Exception $e) {
                $this->log('error', "Hook execution failed for {$event}: {$e->getMessage()}");
            }
        }
    }
    
    /**
     * Get all registered providers
     * 
     * @return array
     */
    public function getProviders()
    {
        return $this->providers;
    }
    
    /**
     * Get all booted providers
     * 
     * @return array
     */
    public function getBootedProviders()
    {
        return $this->bootedProviders;
    }
    
    /**
     * Get deferred services
     * 
     * @return array
     */
    public function getDeferredServices()
    {
        return $this->deferredServices;
    }
    
    /**
     * Check if the manager has been booted
     * 
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }
    
    /**
     * Get manager statistics
     * 
     * @return array
     */
    public function getStats()
    {
        return array_merge($this->stats, [
            'total_providers' => count($this->providers),
            'total_booted' => count($this->bootedProviders),
            'total_deferred' => count($this->deferredServices),
            'container_stats' => $this->container->getStats()
        ]);
    }
    
    /**
     * Reset manager statistics
     * 
     * @return $this
     */
    public function resetStats()
    {
        $this->stats = [
            'providers_registered' => 0,
            'providers_booted' => 0,
            'services_resolved' => 0,
            'deferred_services' => 0
        ];
        
        return $this;
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
     * Check if a service exists
     * 
     * @param string $abstract
     * @return bool
     */
    public function has($abstract)
    {
        return $this->container->has($abstract) || isset($this->deferredServices[$abstract]);
    }
    
    /**
     * Get a service (alias for resolve)
     * 
     * @param string $abstract
     * @return mixed
     */
    public function get($abstract)
    {
        return $this->resolve($abstract);
    }
    
    /**
     * Register multiple providers
     * 
     * @param array $providers
     * @return $this
     */
    public function registerProviders(array $providers)
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }
        
        return $this;
    }
    
    /**
     * Flush all providers and services
     * 
     * @return $this
     */
    public function flush()
    {
        $this->providers = [];
        $this->bootedProviders = [];
        $this->deferredServices = [];
        $this->booted = false;
        
        $this->container->flush();
        $this->registerCoreServices();
        
        return $this;
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
     * Convert the manager to an array
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'booted' => $this->booted,
            'providers' => array_keys($this->providers),
            'booted_providers' => array_keys($this->bootedProviders),
            'deferred_services' => $this->deferredServices,
            'stats' => $this->getStats()
        ];
    }
    
    /**
     * Convert the manager to JSON
     * 
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}