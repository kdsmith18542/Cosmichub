<?php

namespace App\Core\Contracts;

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Container Interface
 * 
 * Extends PSR-11 ContainerInterface with additional functionality
 * for dependency injection and service management
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Bind a service to the container
     * 
     * @param string $abstract
     * @param mixed $concrete
     * @param bool $shared
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false);
    
    /**
     * Bind a singleton service to the container
     * 
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null);
    
    /**
     * Bind an existing instance to the container
     * 
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function instance($abstract, $instance);
    
    /**
     * Create an alias for a service
     * 
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias($abstract, $alias);
    
    /**
     * Tag services for group resolution
     * 
     * @param array|string $abstracts
     * @param array|mixed $tags
     * @return void
     */
    public function tag($abstracts, $tags);
    
    /**
     * Resolve all services tagged with a given tag
     * 
     * @param string $tag
     * @return array
     */
    public function tagged($tag);
    
    /**
     * Check if a service is bound
     * 
     * @param string $abstract
     * @return bool
     */
    public function bound($abstract);
    
    /**
     * Check if a service is a singleton
     * 
     * @param string $abstract
     * @return bool
     */
    public function isShared($abstract);
    
    /**
     * Check if a service is an alias
     * 
     * @param string $name
     * @return bool
     */
    public function isAlias($name);
    
    /**
     * Resolve a service from the container
     * 
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = []);
    
    /**
     * Call a method and resolve its dependencies
     * 
     * @param callable|string $callback
     * @param array $parameters
     * @param string|null $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null);
    
    /**
     * Resolve a class and its dependencies
     * 
     * @param string $concrete
     * @param array $parameters
     * @return mixed
     */
    public function build($concrete, array $parameters = []);
    
    /**
     * Remove a service from the container
     * 
     * @param string $abstract
     * @return void
     */
    public function forget($abstract);
    
    /**
     * Flush all bindings and resolved instances
     * 
     * @return void
     */
    public function flush();
    
    /**
     * Get all bindings
     * 
     * @return array
     */
    public function getBindings();
    
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
     * Get all resolved instances
     * 
     * @return array
     */
    public function getInstances();
    
    /**
     * Register a binding if it hasn't already been registered
     * 
     * @param string $abstract
     * @param mixed $concrete
     * @param bool $shared
     * @return void
     */
    public function bindIf($abstract, $concrete = null, $shared = false);
    
    /**
     * Register a singleton if it hasn't already been registered
     * 
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    public function singletonIf($abstract, $concrete = null);
    
    /**
     * Extend a service binding
     * 
     * @param string $abstract
     * @param callable $closure
     * @return void
     */
    public function extend($abstract, callable $closure);
    
    /**
     * Register a callback to be called when a service is resolved
     * 
     * @param string $abstract
     * @param callable $callback
     * @return void
     */
    public function resolving($abstract, callable $callback = null);
    
    /**
     * Register a callback to be called after a service is resolved
     * 
     * @param string $abstract
     * @param callable $callback
     * @return void
     */
    public function afterResolving($abstract, callable $callback = null);
    
    /**
     * Get the container instance
     * 
     * @return static
     */
    public static function getInstance();
    
    /**
     * Set the container instance
     * 
     * @param ContainerInterface|null $container
     * @return static
     */
    public static function setInstance(ContainerInterface $container = null);
}