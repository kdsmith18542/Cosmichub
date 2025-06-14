<?php

namespace App\Core;

use Psr\Container\ContainerInterface;
use App\Core\Container\ContextualBindingBuilder;
use App\Core\Container\ContainerException;
use App\Core\Container\NotFoundException;

/**
 * Enhanced dependency injection container with PSR-11 compliance
 * 
 * This container has been enhanced following the refactoring plan to provide:
 * - Full PSR-11 compliance
 * - Contextual binding support
 * - Alias resolution
 * - Better error handling
 * - Method binding support
 * - Tagging system
 */
class Container implements ContainerInterface
{
    /**
     * @var Container|null Singleton instance
     */
    private static $instance = null;
    
    /**
     * @var array The container's bindings
     */
    private $bindings = [];
    
    /**
     * @var array The container's shared instances
     */
    private $instances = [];
    
    /**
     * @var array The container's aliases
     */
    private $aliases = [];
    
    /**
     * @var array The container's tags
     */
    private $tags = [];
    
    /**
     * @var array The container's contextual bindings
     */
    private $contextual = [];
    
    /**
     * @var array The container's method bindings
     */
    private $methodBindings = [];
    
    /**
     * @var array Stack of types currently being resolved
     */
    private $buildStack = [];
    
    /**
     * Get the container instance
     * 
     * @return Container
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Bind a value to the container
     * 
     * @param string $id
     * @param mixed $concrete
     * @param bool $shared
     * @return void
     */
    public function bind($id, $concrete = null, $shared = false)
    {
        // If no concrete value was passed, use the id as the concrete
        $concrete = $concrete ?: $id;
        
        $this->bindings[$id] = compact('concrete', 'shared');
    }
    
    /**
     * Register a shared binding in the container
     * 
     * @param string $id
     * @param mixed $concrete
     * @return void
     */
    public function singleton($id, $concrete = null)
    {
        $this->bind($id, $concrete, true);
    }
    
    /**
     * Register an existing instance as shared in the container
     * 
     * @param string $id
     * @param mixed $instance
     * @return mixed
     */
    public function instance($id, $instance)
    {
        $this->instances[$id] = $instance;
        
        return $instance;
    }
    
    /**
     * Alias a type to a different name
     * 
     * @param string $alias
     * @param string $abstract
     * @return void
     */
    public function alias($alias, $abstract)
    {
        $this->aliases[$alias] = $abstract;
    }
    
    /**
     * Tag a binding with a given tag
     * 
     * @param array|string $abstracts
     * @param array|mixed $tags
     * @return void
     */
    public function tag($abstracts, $tags)
    {
        $tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);
        
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }
            
            foreach ((array) $abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }
    
    /**
     * Resolve all of the bindings for a given tag
     * 
     * @param string $tag
     * @return array
     */
    public function tagged($tag)
    {
        $results = [];
        
        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $abstract) {
                $results[] = $this->make($abstract);
            }
        }
        
        return $results;
    }
    
    /**
     * Define a contextual binding
     * 
     * @param string $concrete
     * @return ContextualBindingBuilder
     */
    public function when($concrete)
    {
        return new ContextualBindingBuilder($this, $concrete);
    }
    
    /**
     * Add a contextual binding to the container
     * 
     * @param string $concrete
     * @param string $abstract
     * @param \Closure|string $implementation
     * @return void
     */
    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        $this->contextual[$concrete][$abstract] = $implementation;
    }
    
    /**
     * Resolve the given type from the container
     * 
     * @param string $id
     * @param array $parameters
     * @return mixed
     * 
     * @throws ContainerException
     */
    public function make($id, array $parameters = [])
    {
        return $this->resolve($id, $parameters);
    }
    
    /**
     * Determine if the given id is bound in the container
     * 
     * @param string $id
     * @return bool
     */
    public function bound($id)
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    /**
     * Returns true if the container can find a entry for the given identifier.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }
    
    /**
     * Get an entry from the container (PSR-11)
     *
     * @param string $id
     * @return mixed
     * 
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function get(string $id)
    {
        try {
            return $this->resolve($id);
        } catch (ContainerException $e) {
            if (strpos($e->getMessage(), 'does not exist') !== false || 
                strpos($e->getMessage(), 'not found') !== false) {
                throw new NotFoundException("No entry found for identifier: {$id}", 0, $e);
            }
            throw $e;
        }
    }
    
    /**
     * Returns true if the container can return an entry for the given identifier
     * Returns false otherwise
     *
     * @param string $id Identifier of the entry to look for
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->bound($id);
    }
    
    /**
     * Resolve the given type from the container
     *
     * @param string $id
     * @param array $parameters
     * @return mixed
     * 
     * @throws ContainerException
     */
    protected function resolve($id, array $parameters = [])
    {
        // Resolve aliases
        $id = $this->getAlias($id);
        
        // Check for circular dependencies
        if (in_array($id, $this->buildStack)) {
            throw new ContainerException("Circular dependency detected: " . implode(' -> ', $this->buildStack) . " -> {$id}");
        }
        
        // Check if we have an instance
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        
        // Check for contextual binding
        $concrete = $this->getContextualConcrete($id);
        
        if ($concrete !== null) {
            $object = $this->build($concrete, $parameters);
            return $object;
        }
        
        // Check if we have a binding
        if (isset($this->bindings[$id])) {
            $binding = $this->bindings[$id];
            $concrete = $binding['concrete'];
            
            if ($binding['shared'] && isset($this->instances[$id])) {
                return $this->instances[$id];
            }
            
            $object = $this->build($concrete, $parameters);
            
            if ($binding['shared']) {
                $this->instances[$id] = $object;
            }
            
            return $object;
        }
        
        // Try to resolve as a class
        return $this->build($id, $parameters);
    }
    
    /**
     * Get the alias for an abstract if available
     *
     * @param string $abstract
     * @return string
     */
    protected function getAlias($abstract)
    {
        return isset($this->aliases[$abstract]) ? $this->getAlias($this->aliases[$abstract]) : $abstract;
    }
    
    /**
     * Get the contextual concrete binding for the given abstract
     *
     * @param string $abstract
     * @return mixed|null
     */
    protected function getContextualConcrete($abstract)
    {
        if (!empty($this->buildStack)) {
            $last = end($this->buildStack);
            if (isset($this->contextual[$last][$abstract])) {
                return $this->contextual[$last][$abstract];
            }
        }
        
        return null;
    }
    
    /**
     * Get the concrete type for a given abstract
     *
     * @param string $abstract
     * @return mixed
     * 
     * @throws ContainerException
     */
    protected function getConcrete($abstract)
    {
        // Check if we have a binding for this abstract
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }
        
        // Check for aliases
        if (isset($this->aliases[$abstract])) {
            return $this->getConcrete($this->aliases[$abstract]);
        }
        
        // If no binding exists, return the abstract itself
        // This allows the container to attempt to build the class directly
        if (class_exists($abstract)) {
            return $abstract;
        }
        
        throw new ContainerException("Target [{$abstract}] is not instantiable or does not exist.");
    }
    
    /**
     * Determine if a given type is shared
     *
     * @param string $id
     * @return bool
     */
    protected function isShared($id)
    {
        return isset($this->instances[$id]) ||
               (isset($this->bindings[$id]) && $this->bindings[$id]['shared']);
    }
    
    /**
     * Build an instance of the given type
     *
     * @param string $concrete
     * @param array $parameters
     * @return mixed
     * 
     * @throws ContainerException
     */
    protected function build($concrete, array $parameters = [])
    {
        // If the concrete is a closure, call it
        if ($concrete instanceof \Closure) {
            return $concrete($this, $parameters);
        }
        
        // Add to build stack for circular dependency detection
        $this->buildStack[] = $concrete;
        
        try {
            $reflector = new \ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            array_pop($this->buildStack);
            throw new ContainerException("Class {$concrete} does not exist: " . $e->getMessage());
        }
        
        if (!$reflector->isInstantiable()) {
            array_pop($this->buildStack);
            throw new ContainerException("Class {$concrete} is not instantiable");
        }
        
        $constructor = $reflector->getConstructor();
        
        // If there's no constructor, just create the instance
        if (is_null($constructor)) {
            array_pop($this->buildStack);
            return new $concrete;
        }
        
        $dependencies = $constructor->getParameters();
        
        try {
            // Resolve all dependencies
            $instances = $this->resolveDependencies($dependencies, $parameters);
            
            $object = $reflector->newInstanceArgs($instances);
            
            array_pop($this->buildStack);
            
            return $object;
        } catch (\Exception $e) {
            array_pop($this->buildStack);
            throw new ContainerException("Error building {$concrete}: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Resolve all of the dependencies from the ReflectionParameters
     *
     * @param array $dependencies
     * @param array $parameters
     * @return array
     * 
     * @throws ContainerException
     */
    protected function resolveDependencies(array $dependencies, array $parameters = [])
    {
        $results = [];
        
        foreach ($dependencies as $dependency) {
            // If the dependency is in the parameters, use it
            $name = $dependency->getName();
            if (array_key_exists($name, $parameters)) {
                $results[] = $parameters[$name];
                continue;
            }
            
            // If the dependency has a type hint, try to resolve it
            $type = $dependency->getType();
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();
                try {
                    $results[] = $this->get($typeName);
                    continue;
                } catch (ContainerException $e) {
                    // If we can't resolve and there's no default, re-throw
                    if (!$dependency->isDefaultValueAvailable()) {
                        throw new ContainerException(
                            "Unable to resolve dependency [{$name}] of type [{$typeName}]: " . $e->getMessage(),
                            0,
                            $e
                        );
                    }
                }
            }
            
            // If the dependency has a default value, use it
            if ($dependency->isDefaultValueAvailable()) {
                $results[] = $dependency->getDefaultValue();
                continue;
            }
            
            // We couldn't resolve the dependency
            $context = !empty($this->buildStack) ? ' in ' . end($this->buildStack) : '';
            throw new ContainerException("Unresolvable dependency [{$name}]{$context}");
        }
        
        return $results;
    }
    
    /**
     * Prevent direct instantiation
     */
    private function __construct() {}
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {}
}