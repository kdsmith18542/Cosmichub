<?php

namespace App\Core\Services;

use App\Core\Container\Container;
use App\Core\Exceptions\ServiceException;
use App\Core\Logging\Logger;
use App\Core\Events\EventDispatcher;

/**
 * Service Registry Class
 * 
 * Manages service registration, discovery, and lifecycle
 * Provides a centralized registry for all application services
 */
class ServiceRegistry
{
    /**
     * @var Container The container instance
     */
    protected $container;
    
    /**
     * @var Logger|null The logger instance
     */
    protected $logger;
    
    /**
     * @var EventDispatcher|null The event dispatcher
     */
    protected $events;
    
    /**
     * @var array Registered services
     */
    protected $services = [];
    
    /**
     * @var array Service aliases
     */
    protected $aliases = [];
    
    /**
     * @var array Service tags
     */
    protected $tags = [];
    
    /**
     * @var array Service groups
     */
    protected $groups = [];
    
    /**
     * @var array Service metadata
     */
    protected $metadata = [];
    
    /**
     * @var array Service instances
     */
    protected $instances = [];
    
    /**
     * @var array Service factories
     */
    protected $factories = [];
    
    /**
     * @var array Service decorators
     */
    protected $decorators = [];
    
    /**
     * @var array Service middleware
     */
    protected $middleware = [];
    
    /**
     * @var array Service lifecycle hooks
     */
    protected $hooks = [
        'before_create' => [],
        'after_create' => [],
        'before_destroy' => [],
        'after_destroy' => []
    ];
    
    /**
     * @var array Service statistics
     */
    protected $stats = [
        'registered' => 0,
        'created' => 0,
        'destroyed' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0
    ];
    
    /**
     * @var bool Whether the registry is locked
     */
    protected $locked = false;
    
    /**
     * Create a new service registry
     * 
     * @param Container $container
     * @param Logger|null $logger
     * @param EventDispatcher|null $events
     */
    public function __construct(Container $container, Logger $logger = null, EventDispatcher $events = null)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->events = $events;
    }
    
    /**
     * Register a service
     * 
     * @param string $name
     * @param string|callable $service
     * @param array $options
     * @return $this
     * @throws ServiceException
     */
    public function register($name, $service, array $options = [])
    {
        if ($this->locked) {
            throw ServiceException::operationFailed('Registry is locked');
        }
        
        if ($this->isRegistered($name)) {
            throw ServiceException::alreadyExists("Service '{$name}' is already registered");
        }
        
        $this->services[$name] = [
            'service' => $service,
            'options' => $options,
            'singleton' => $options['singleton'] ?? true,
            'lazy' => $options['lazy'] ?? true,
            'shared' => $options['shared'] ?? true,
            'abstract' => $options['abstract'] ?? false,
            'registered_at' => time()
        ];
        
        // Handle aliases
        if (isset($options['aliases'])) {
            foreach ((array) $options['aliases'] as $alias) {
                $this->alias($alias, $name);
            }
        }
        
        // Handle tags
        if (isset($options['tags'])) {
            foreach ((array) $options['tags'] as $tag) {
                $this->tag($name, $tag);
            }
        }
        
        // Handle groups
        if (isset($options['group'])) {
            $this->group($name, $options['group']);
        }
        
        // Store metadata
        if (isset($options['metadata'])) {
            $this->metadata[$name] = $options['metadata'];
        }
        
        // Register factory if provided
        if (isset($options['factory'])) {
            $this->factories[$name] = $options['factory'];
        }
        
        // Register decorators if provided
        if (isset($options['decorators'])) {
            $this->decorators[$name] = (array) $options['decorators'];
        }
        
        // Register middleware if provided
        if (isset($options['middleware'])) {
            $this->middleware[$name] = (array) $options['middleware'];
        }
        
        $this->stats['registered']++;
        
        $this->log('debug', "Service '{$name}' registered", ['options' => $options]);
        $this->dispatch('service.registered', ['name' => $name, 'options' => $options]);
        
        return $this;
    }
    
    /**
     * Register a singleton service
     * 
     * @param string $name
     * @param string|callable $service
     * @param array $options
     * @return $this
     */
    public function singleton($name, $service, array $options = [])
    {
        $options['singleton'] = true;
        return $this->register($name, $service, $options);
    }
    
    /**
     * Register a transient service
     * 
     * @param string $name
     * @param string|callable $service
     * @param array $options
     * @return $this
     */
    public function transient($name, $service, array $options = [])
    {
        $options['singleton'] = false;
        return $this->register($name, $service, $options);
    }
    
    /**
     * Register an existing instance
     * 
     * @param string $name
     * @param mixed $instance
     * @param array $options
     * @return $this
     */
    public function instance($name, $instance, array $options = [])
    {
        $this->instances[$name] = $instance;
        $options['singleton'] = true;
        $options['lazy'] = false;
        return $this->register($name, function() use ($instance) {
            return $instance;
        }, $options);
    }
    
    /**
     * Register a factory
     * 
     * @param string $name
     * @param callable $factory
     * @param array $options
     * @return $this
     */
    public function factory($name, callable $factory, array $options = [])
    {
        $this->factories[$name] = $factory;
        $options['singleton'] = false;
        return $this->register($name, $factory, $options);
    }
    
    /**
     * Create an alias for a service
     * 
     * @param string $alias
     * @param string $service
     * @return $this
     */
    public function alias($alias, $service)
    {
        $this->aliases[$alias] = $service;
        return $this;
    }
    
    /**
     * Tag a service
     * 
     * @param string $service
     * @param string $tag
     * @return $this
     */
    public function tag($service, $tag)
    {
        if (!isset($this->tags[$tag])) {
            $this->tags[$tag] = [];
        }
        
        if (!in_array($service, $this->tags[$tag])) {
            $this->tags[$tag][] = $service;
        }
        
        return $this;
    }
    
    /**
     * Add a service to a group
     * 
     * @param string $service
     * @param string $group
     * @return $this
     */
    public function group($service, $group)
    {
        if (!isset($this->groups[$group])) {
            $this->groups[$group] = [];
        }
        
        if (!in_array($service, $this->groups[$group])) {
            $this->groups[$group][] = $service;
        }
        
        return $this;
    }
    
    /**
     * Add a decorator for a service
     * 
     * @param string $service
     * @param callable $decorator
     * @return $this
     */
    public function decorate($service, callable $decorator)
    {
        if (!isset($this->decorators[$service])) {
            $this->decorators[$service] = [];
        }
        
        $this->decorators[$service][] = $decorator;
        return $this;
    }
    
    /**
     * Add middleware for a service
     * 
     * @param string $service
     * @param callable $middleware
     * @return $this
     */
    public function middleware($service, callable $middleware)
    {
        if (!isset($this->middleware[$service])) {
            $this->middleware[$service] = [];
        }
        
        $this->middleware[$service][] = $middleware;
        return $this;
    }
    
    /**
     * Add a lifecycle hook
     * 
     * @param string $event
     * @param callable $hook
     * @return $this
     */
    public function hook($event, callable $hook)
    {
        if (!isset($this->hooks[$event])) {
            $this->hooks[$event] = [];
        }
        
        $this->hooks[$event][] = $hook;
        return $this;
    }
    
    /**
     * Get a service instance
     * 
     * @param string $name
     * @param array $parameters
     * @return mixed
     * @throws ServiceException
     */
    public function get($name, array $parameters = [])
    {
        // Resolve alias
        $name = $this->resolveAlias($name);
        
        if (!$this->isRegistered($name)) {
            throw ServiceException::notFound("Service '{$name}' not found");
        }
        
        // Check if instance already exists for singletons
        if ($this->isSingleton($name) && isset($this->instances[$name])) {
            $this->stats['cache_hits']++;
            return $this->instances[$name];
        }
        
        $this->stats['cache_misses']++;
        
        // Execute before_create hooks
        $this->executeHooks('before_create', $name, $parameters);
        
        try {
            $instance = $this->createInstance($name, $parameters);
            
            // Apply decorators
            $instance = $this->applyDecorators($name, $instance);
            
            // Apply middleware
            $instance = $this->applyMiddleware($name, $instance);
            
            // Store instance if singleton
            if ($this->isSingleton($name)) {
                $this->instances[$name] = $instance;
            }
            
            $this->stats['created']++;
            
            // Execute after_create hooks
            $this->executeHooks('after_create', $name, $instance);
            
            $this->log('debug', "Service '{$name}' created");
            $this->dispatch('service.created', ['name' => $name, 'instance' => $instance]);
            
            return $instance;
        } catch (\Exception $e) {
            $this->log('error', "Failed to create service '{$name}'", ['error' => $e->getMessage()]);
            throw ServiceException::operationFailed("Failed to create service '{$name}'", $e);
        }
    }
    
    /**
     * Check if a service is registered
     * 
     * @param string $name
     * @return bool
     */
    public function isRegistered($name)
    {
        $name = $this->resolveAlias($name);
        return isset($this->services[$name]);
    }
    
    /**
     * Check if a service is a singleton
     * 
     * @param string $name
     * @return bool
     */
    public function isSingleton($name)
    {
        $name = $this->resolveAlias($name);
        return $this->services[$name]['singleton'] ?? true;
    }
    
    /**
     * Check if a service instance exists
     * 
     * @param string $name
     * @return bool
     */
    public function hasInstance($name)
    {
        $name = $this->resolveAlias($name);
        return isset($this->instances[$name]);
    }
    
    /**
     * Destroy a service instance
     * 
     * @param string $name
     * @return $this
     */
    public function destroy($name)
    {
        $name = $this->resolveAlias($name);
        
        if (isset($this->instances[$name])) {
            $instance = $this->instances[$name];
            
            // Execute before_destroy hooks
            $this->executeHooks('before_destroy', $name, $instance);
            
            // Call destroy method if exists
            if (method_exists($instance, 'destroy')) {
                $instance->destroy();
            }
            
            unset($this->instances[$name]);
            $this->stats['destroyed']++;
            
            // Execute after_destroy hooks
            $this->executeHooks('after_destroy', $name, null);
            
            $this->log('debug', "Service '{$name}' destroyed");
            $this->dispatch('service.destroyed', ['name' => $name]);
        }
        
        return $this;
    }
    
    /**
     * Unregister a service
     * 
     * @param string $name
     * @return $this
     */
    public function unregister($name)
    {
        if ($this->locked) {
            throw ServiceException::operationFailed('Registry is locked');
        }
        
        $name = $this->resolveAlias($name);
        
        // Destroy instance if exists
        $this->destroy($name);
        
        // Remove from services
        unset($this->services[$name]);
        
        // Remove from metadata
        unset($this->metadata[$name]);
        
        // Remove from factories
        unset($this->factories[$name]);
        
        // Remove from decorators
        unset($this->decorators[$name]);
        
        // Remove from middleware
        unset($this->middleware[$name]);
        
        // Remove from aliases
        $this->aliases = array_filter($this->aliases, function($service) use ($name) {
            return $service !== $name;
        });
        
        // Remove from tags
        foreach ($this->tags as $tag => $services) {
            $this->tags[$tag] = array_filter($services, function($service) use ($name) {
                return $service !== $name;
            });
            if (empty($this->tags[$tag])) {
                unset($this->tags[$tag]);
            }
        }
        
        // Remove from groups
        foreach ($this->groups as $group => $services) {
            $this->groups[$group] = array_filter($services, function($service) use ($name) {
                return $service !== $name;
            });
            if (empty($this->groups[$group])) {
                unset($this->groups[$group]);
            }
        }
        
        $this->stats['registered']--;
        
        $this->log('debug', "Service '{$name}' unregistered");
        $this->dispatch('service.unregistered', ['name' => $name]);
        
        return $this;
    }
    
    /**
     * Get services by tag
     * 
     * @param string $tag
     * @return array
     */
    public function tagged($tag)
    {
        $services = $this->tags[$tag] ?? [];
        $instances = [];
        
        foreach ($services as $service) {
            $instances[$service] = $this->get($service);
        }
        
        return $instances;
    }
    
    /**
     * Get services by group
     * 
     * @param string $group
     * @return array
     */
    public function grouped($group)
    {
        $services = $this->groups[$group] ?? [];
        $instances = [];
        
        foreach ($services as $service) {
            $instances[$service] = $this->get($service);
        }
        
        return $instances;
    }
    
    /**
     * Get all registered service names
     * 
     * @return array
     */
    public function getServices()
    {
        return array_keys($this->services);
    }
    
    /**
     * Get all aliases
     * 
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }
    
    /**
     * Get all tags
     * 
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }
    
    /**
     * Get all groups
     * 
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }
    
    /**
     * Get service metadata
     * 
     * @param string $name
     * @return array|null
     */
    public function getMetadata($name)
    {
        $name = $this->resolveAlias($name);
        return $this->metadata[$name] ?? null;
    }
    
    /**
     * Get service statistics
     * 
     * @return array
     */
    public function getStats()
    {
        return $this->stats;
    }
    
    /**
     * Reset statistics
     * 
     * @return $this
     */
    public function resetStats()
    {
        $this->stats = [
            'registered' => count($this->services),
            'created' => 0,
            'destroyed' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0
        ];
        
        return $this;
    }
    
    /**
     * Lock the registry
     * 
     * @return $this
     */
    public function lock()
    {
        $this->locked = true;
        return $this;
    }
    
    /**
     * Unlock the registry
     * 
     * @return $this
     */
    public function unlock()
    {
        $this->locked = false;
        return $this;
    }
    
    /**
     * Check if the registry is locked
     * 
     * @return bool
     */
    public function isLocked()
    {
        return $this->locked;
    }
    
    /**
     * Flush all services and instances
     * 
     * @return $this
     */
    public function flush()
    {
        if ($this->locked) {
            throw ServiceException::operationFailed('Registry is locked');
        }
        
        // Destroy all instances
        foreach (array_keys($this->instances) as $name) {
            $this->destroy($name);
        }
        
        // Clear all data
        $this->services = [];
        $this->aliases = [];
        $this->tags = [];
        $this->groups = [];
        $this->metadata = [];
        $this->instances = [];
        $this->factories = [];
        $this->decorators = [];
        $this->middleware = [];
        
        $this->resetStats();
        
        $this->log('info', 'Service registry flushed');
        $this->dispatch('registry.flushed');
        
        return $this;
    }
    
    /**
     * Resolve an alias to the actual service name
     * 
     * @param string $name
     * @return string
     */
    protected function resolveAlias($name)
    {
        while (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        
        return $name;
    }
    
    /**
     * Create a service instance
     * 
     * @param string $name
     * @param array $parameters
     * @return mixed
     */
    protected function createInstance($name, array $parameters = [])
    {
        $service = $this->services[$name]['service'];
        
        if (is_callable($service)) {
            return $service($this->container, $parameters);
        }
        
        if (is_string($service)) {
            return $this->container->get($service);
        }
        
        return $service;
    }
    
    /**
     * Apply decorators to a service instance
     * 
     * @param string $name
     * @param mixed $instance
     * @return mixed
     */
    protected function applyDecorators($name, $instance)
    {
        if (!isset($this->decorators[$name])) {
            return $instance;
        }
        
        foreach ($this->decorators[$name] as $decorator) {
            $instance = $decorator($instance, $this->container);
        }
        
        return $instance;
    }
    
    /**
     * Apply middleware to a service instance
     * 
     * @param string $name
     * @param mixed $instance
     * @return mixed
     */
    protected function applyMiddleware($name, $instance)
    {
        if (!isset($this->middleware[$name])) {
            return $instance;
        }
        
        foreach ($this->middleware[$name] as $middleware) {
            $instance = $middleware($instance, $this->container);
        }
        
        return $instance;
    }
    
    /**
     * Execute lifecycle hooks
     * 
     * @param string $event
     * @param string $name
     * @param mixed $data
     * @return void
     */
    protected function executeHooks($event, $name, $data = null)
    {
        if (!isset($this->hooks[$event])) {
            return;
        }
        
        foreach ($this->hooks[$event] as $hook) {
            $hook($name, $data, $this);
        }
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
     * @param string $event
     * @param array $data
     * @return void
     */
    protected function dispatch($event, array $data = [])
    {
        if ($this->events) {
            $this->events->dispatch($event, $data);
        }
    }
    
    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'services' => $this->services,
            'aliases' => $this->aliases,
            'tags' => $this->tags,
            'groups' => $this->groups,
            'metadata' => $this->metadata,
            'stats' => $this->stats,
            'locked' => $this->locked
        ];
    }
    
    /**
     * Convert to JSON
     * 
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
    
    /**
     * String representation
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}