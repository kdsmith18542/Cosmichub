<?php

namespace App\Core\Contracts;

/**
 * Service Builder Interface
 * 
 * Defines the contract for building services with a fluent interface
 * Provides methods for configuring services step by step
 */
interface ServiceBuilderInterface
{
    /**
     * Set the service class
     * 
     * @param string $class
     * @return $this
     */
    public function setClass($class);
    
    /**
     * Set service configuration
     * 
     * @param array $config
     * @return $this
     */
    public function withConfig(array $config);
    
    /**
     * Add a configuration value
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addConfig($key, $value);
    
    /**
     * Set service dependencies
     * 
     * @param array $dependencies
     * @return $this
     */
    public function withDependencies(array $dependencies);
    
    /**
     * Add a dependency
     * 
     * @param string $name
     * @param mixed $dependency
     * @return $this
     */
    public function addDependency($name, $dependency);
    
    /**
     * Set service as singleton
     * 
     * @param bool $singleton
     * @return $this
     */
    public function singleton($singleton = true);
    
    /**
     * Set service as transient
     * 
     * @return $this
     */
    public function transient();
    
    /**
     * Add service tags
     * 
     * @param array|string $tags
     * @return $this
     */
    public function withTags($tags);
    
    /**
     * Add a single tag
     * 
     * @param string $tag
     * @return $this
     */
    public function addTag($tag);
    
    /**
     * Set service group
     * 
     * @param string $group
     * @return $this
     */
    public function inGroup($group);
    
    /**
     * Add service metadata
     * 
     * @param array $metadata
     * @return $this
     */
    public function withMetadata(array $metadata);
    
    /**
     * Add a metadata value
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addMetadata($key, $value);
    
    /**
     * Add a decorator
     * 
     * @param callable $decorator
     * @param int $priority
     * @return $this
     */
    public function withDecorator(callable $decorator, $priority = 0);
    
    /**
     * Add middleware
     * 
     * @param callable $middleware
     * @param int $priority
     * @return $this
     */
    public function withMiddleware(callable $middleware, $priority = 0);
    
    /**
     * Add a lifecycle hook
     * 
     * @param string $event
     * @param callable $callback
     * @param int $priority
     * @return $this
     */
    public function withHook($event, callable $callback, $priority = 0);
    
    /**
     * Set service alias
     * 
     * @param string $alias
     * @return $this
     */
    public function alias($alias);
    
    /**
     * Set service aliases
     * 
     * @param array $aliases
     * @return $this
     */
    public function withAliases(array $aliases);
    
    /**
     * Enable lazy loading
     * 
     * @param bool $lazy
     * @return $this
     */
    public function lazy($lazy = true);
    
    /**
     * Set service priority
     * 
     * @param int $priority
     * @return $this
     */
    public function priority($priority);
    
    /**
     * Set service version
     * 
     * @param string $version
     * @return $this
     */
    public function version($version);
    
    /**
     * Set service description
     * 
     * @param string $description
     * @return $this
     */
    public function description($description);
    
    /**
     * Enable service logging
     * 
     * @param bool $enabled
     * @return $this
     */
    public function withLogging($enabled = true);
    
    /**
     * Enable service events
     * 
     * @param bool $enabled
     * @return $this
     */
    public function withEvents($enabled = true);
    
    /**
     * Enable service validation
     * 
     * @param bool $enabled
     * @return $this
     */
    public function withValidation($enabled = true);
    
    /**
     * Set validation rules
     * 
     * @param array $rules
     * @return $this
     */
    public function withValidationRules(array $rules);
    
    /**
     * Set validation messages
     * 
     * @param array $messages
     * @return $this
     */
    public function withValidationMessages(array $messages);
    
    /**
     * Use a template
     * 
     * @param string $template
     * @return $this
     */
    public function fromTemplate($template);
    
    /**
     * Set factory method
     * 
     * @param callable $factory
     * @return $this
     */
    public function withFactory(callable $factory);
    
    /**
     * Set initialization callback
     * 
     * @param callable $callback
     * @return $this
     */
    public function onInitialize(callable $callback);
    
    /**
     * Set boot callback
     * 
     * @param callable $callback
     * @return $this
     */
    public function onBoot(callable $callback);
    
    /**
     * Set destroy callback
     * 
     * @param callable $callback
     * @return $this
     */
    public function onDestroy(callable $callback);
    
    /**
     * Build and register the service
     * 
     * @param string|null $name
     * @return mixed
     */
    public function build($name = null);
    
    /**
     * Build and return the service without registering
     * 
     * @return mixed
     */
    public function create();
    
    /**
     * Get the built configuration
     * 
     * @return array
     */
    public function getConfig();
    
    /**
     * Get the service class
     * 
     * @return string
     */
    public function getClass();
    
    /**
     * Reset the builder to initial state
     * 
     * @return $this
     */
    public function reset();
    
    /**
     * Clone the builder with current configuration
     * 
     * @return static
     */
    public function clone();
}