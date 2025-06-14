<?php

namespace App\Core\Contracts;

/**
 * Service Registry Interface
 * 
 * Defines the contract for service registration, discovery, and lifecycle management
 * Provides methods for organizing services by tags, groups, and metadata
 */
interface ServiceRegistryInterface
{
    /**
     * Register a service
     * 
     * @param string $name
     * @param mixed $service
     * @param array $options
     * @return void
     */
    public function register($name, $service, array $options = []);
    
    /**
     * Register a singleton service
     * 
     * @param string $name
     * @param mixed $service
     * @param array $options
     * @return void
     */
    public function singleton($name, $service, array $options = []);
    
    /**
     * Register a transient service
     * 
     * @param string $name
     * @param mixed $service
     * @param array $options
     * @return void
     */
    public function transient($name, $service, array $options = []);
    
    /**
     * Register an existing instance
     * 
     * @param string $name
     * @param mixed $instance
     * @param array $options
     * @return void
     */
    public function instance($name, $instance, array $options = []);
    
    /**
     * Register a factory for creating services
     * 
     * @param string $name
     * @param callable $factory
     * @param array $options
     * @return void
     */
    public function factory($name, callable $factory, array $options = []);
    
    /**
     * Create an alias for a service
     * 
     * @param string $alias
     * @param string $service
     * @return void
     */
    public function alias($alias, $service);
    
    /**
     * Tag services for group resolution
     * 
     * @param array|string $services
     * @param array|string $tags
     * @return void
     */
    public function tag($services, $tags);
    
    /**
     * Group services together
     * 
     * @param string $group
     * @param array|string $services
     * @return void
     */
    public function group($group, $services);
    
    /**
     * Add a decorator to a service
     * 
     * @param string $service
     * @param callable $decorator
     * @param int $priority
     * @return void
     */
    public function decorate($service, callable $decorator, $priority = 0);
    
    /**
     * Add middleware to a service
     * 
     * @param string $service
     * @param callable $middleware
     * @param int $priority
     * @return void
     */
    public function middleware($service, callable $middleware, $priority = 0);
    
    /**
     * Add a lifecycle hook
     * 
     * @param string $event
     * @param callable $callback
     * @param int $priority
     * @return void
     */
    public function hook($event, callable $callback, $priority = 0);
    
    /**
     * Get a service instance
     * 
     * @param string $name
     * @param array $parameters
     * @return mixed
     */
    public function get($name, array $parameters = []);
    
    /**
     * Check if a service is registered
     * 
     * @param string $name
     * @return bool
     */
    public function has($name);
    
    /**
     * Check if a service is a singleton
     * 
     * @param string $name
     * @return bool
     */
    public function isSingleton($name);
    
    /**
     * Check if a service is an instance
     * 
     * @param string $name
     * @return bool
     */
    public function isInstance($name);
    
    /**
     * Destroy a service instance
     * 
     * @param string $name
     * @return void
     */
    public function destroy($name);
    
    /**
     * Unregister a service
     * 
     * @param string $name
     * @return void
     */
    public function unregister($name);
    
    /**
     * Get services by tag
     * 
     * @param string $tag
     * @return array
     */
    public function getByTag($tag);
    
    /**
     * Get services by group
     * 
     * @param string $group
     * @return array
     */
    public function getByGroup($group);
    
    /**
     * Get all registered services
     * 
     * @return array
     */
    public function getServices();
    
    /**
     * Get all aliases
     * 
     * @return array
     */
    public function getAliases();
    
    /**
     * Get all tags
     * 
     * @return array
     */
    public function getTags();
    
    /**
     * Get all groups
     * 
     * @return array
     */
    public function getGroups();
    
    /**
     * Get service metadata
     * 
     * @param string $name
     * @return array
     */
    public function getMetadata($name);
    
    /**
     * Set service metadata
     * 
     * @param string $name
     * @param array $metadata
     * @return void
     */
    public function setMetadata($name, array $metadata);
    
    /**
     * Get all metadata
     * 
     * @return array
     */
    public function getAllMetadata();
    
    /**
     * Get registry statistics
     * 
     * @return array
     */
    public function getStatistics();
    
    /**
     * Reset registry statistics
     * 
     * @return void
     */
    public function resetStatistics();
    
    /**
     * Lock the registry to prevent further modifications
     * 
     * @return void
     */
    public function lock();
    
    /**
     * Unlock the registry to allow modifications
     * 
     * @return void
     */
    public function unlock();
    
    /**
     * Check if the registry is locked
     * 
     * @return bool
     */
    public function isLocked();
    
    /**
     * Flush all services and instances
     * 
     * @return void
     */
    public function flush();
}