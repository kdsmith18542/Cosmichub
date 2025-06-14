<?php

namespace App\Core\Container;


use App\Core\Container\ContainerAliasTrait;
use App\Core\Container\Exceptions\ContainerException;
use App\Core\Container\Exceptions\ContainerNotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Enhanced PSR-11 compliant dependency injection container
 */
class Container implements ContainerInterface
{
    use ContainerAliasTrait;
    
    /**
     * @var Container The container instance
     */
    private static $instance = null;
    
    /**
     * @var array The container's bindings
     */
    protected $bindings = [];
    
    /**
     * @var array The container's shared instances
     */
    protected $instances = [];
    
    /**
     * @var array The container's contextual bindings
     */
    protected $contextual = [];
    
    /**
     * @var array The container's tags
     */
    protected $tags = [];
    
    /**
     * @var array The container's method bindings
     */
    protected $methodBindings = [];
    
    /**
     * @var array The container's extension callbacks
     */
    protected $extensions = [];
    
    /**
     * @var array The container's resolving callbacks
     */
    protected $resolvingCallbacks = [];
    
    /**
     * @var array The container's resolved callbacks
     */
    protected $resolvedCallbacks = [];
    
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
     * Finds an entry of the container by its identifier and returns it
     *
     * @param string $id Identifier of the entry to look for
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier
     * @throws ContainerExceptionInterface Error while retrieving the entry
     *
     * @return mixed Entry
     */
    public function get(string $id)
    {
        try {
            // Check for aliases
            $id = $this->getAlias($id);
            
            return $this->resolve($id);
        } catch (ContainerNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ContainerException("Error resolving {$id}: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
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
     * @throws ContainerNotFoundException
     * @throws ContainerException
     */
    protected function resolve($id, array $parameters = [])
    {
        // If we have an instance of this type, return it
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        
        // Fire resolving callbacks
        $this->fireResolvingCallbacks($id, null);
        
        // If the type doesn't exist in the container, try to resolve it
        $concrete = $this->getConcrete($id);
        
        // If the concrete is the same as the id, we're trying to resolve a class
        if ($concrete === $id) {
            $object = $this->build($concrete, $parameters);
        } else {
            // If concrete is a Closure, call it directly
            if ($concrete instanceof \Closure) {
                $object = $concrete($this);
            } else {
                // Otherwise, we're resolving a binding
                $object = $this->make($concrete, $parameters);
            }
        }
        
        // Apply extensions
        $object = $this->applyExtensions($id, $object);
        
        // Fire resolved callbacks
        $this->fireResolvedCallbacks($id, $object);
        
        // If the type is shared, store the instance
        if ($this->isShared($id)) {
            $this->instances[$id] = $object;
        }
        
        return $object;
    }
    
    /**
     * Get the concrete type for a given id
     *
     * @param string $id
     * @return mixed
     */
    protected function getConcrete($id)
    {
        // Check for contextual bindings first
        $contextualConcrete = $this->getContextualConcrete($id, $this->getLastParameterOverride());
        if ($contextualConcrete !== null) {
            return $contextualConcrete;
        }
        
        // If we don't have a binding for this id, return the id
        if (!isset($this->bindings[$id])) {
            return $id;
        }
        
        return $this->bindings[$id]['concrete'];
    }
    
    /**
     * Get the last parameter override from the build stack
     *
     * @return string|null
     */
    protected function getLastParameterOverride()
    {
        // This would be implemented with a build stack in a full implementation
        // For now, return null as we don't have context tracking
        return null;
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
     * Instantiate a concrete instance of the given type
     *
     * @param string $concrete
     * @param array $parameters
     * @return mixed
     *
     * @throws ContainerException
     */
    protected function build($concrete, array $parameters = [])
    {
        // If the concrete is a Closure, just execute it and return the result
        if ($concrete instanceof \Closure) {
            return $concrete($this, $parameters);
        }
        
        try {
            $reflector = new \ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new ContainerException("Class {$concrete} does not exist", 0, $e);
        }
        
        // If the class is not instantiable, we can't build it
        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class {$concrete} is not instantiable");
        }
        
        $constructor = $reflector->getConstructor();
        
        // If there's no constructor, just instantiate the class
        if (is_null($constructor)) {
            return new $concrete;
        }
        
        // Get the constructor parameters
        $dependencies = $constructor->getParameters();
        
        // If there are no dependencies, just instantiate the class
        if (empty($dependencies)) {
            return new $concrete;
        }
        
        // Build the dependencies
        $instances = $this->resolveDependencies($dependencies, $parameters);
        
        // Create a new instance with the resolved dependencies
        return $reflector->newInstanceArgs($instances);
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
            // Get the parameter name
            $name = $dependency->name;
            
            // If we have this parameter in the parameters array, use it
            if (array_key_exists($name, $parameters)) {
                $results[] = $parameters[$name];
                continue;
            }
            
            // If the parameter is a class, resolve it from the container
            if ($dependency->getClass()) {
                $results[] = $this->make($dependency->getClass()->name);
                continue;
            }
            
            // If the parameter has a default value, use it
            if ($dependency->isDefaultValueAvailable()) {
                $results[] = $dependency->getDefaultValue();
                continue;
            }
            
            // We couldn't resolve the dependency
            throw new ContainerException("Unresolvable dependency: {$name}");
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
     * Get the contextual concrete binding for the given abstract
     *
     * @param string $abstract
     * @param string $concrete
     * @return mixed
     */
    protected function getContextualConcrete($abstract, $concrete)
    {
        if (isset($this->contextual[$concrete][$abstract])) {
            return $this->contextual[$concrete][$abstract];
        }
        
        return null;
    }
    
    /**
     * Assign a set of tags to a given binding
     *
     * @param array|string $abstracts
     * @param array|mixed ...$tags
     * @return void
     */
    public function tag($abstracts, ...$tags)
    {
        $tags = is_array($tags[0]) ? $tags[0] : $tags;
        
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
     * Call the given method on the given class
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function call($method, array $parameters = [])
    {
        if (is_string($method) && strpos($method, '@') !== false) {
            list($class, $method) = explode('@', $method, 2);
            $instance = $this->make($class);
        } elseif (is_array($method)) {
            list($instance, $method) = $method;
            if (is_string($instance)) {
                $instance = $this->make($instance);
            }
        } else {
            throw new ContainerException('Invalid method format');
        }
        
        return $this->callMethod($instance, $method, $parameters);
    }
    
    /**
     * Call a method with dependency injection
     *
     * @param object $instance
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    protected function callMethod($instance, $method, array $parameters = [])
    {
        try {
            $reflector = new \ReflectionMethod($instance, $method);
        } catch (\ReflectionException $e) {
            throw new ContainerException("Method {$method} does not exist", 0, $e);
        }
        
        $dependencies = $reflector->getParameters();
        
        if (empty($dependencies)) {
            return $reflector->invoke($instance);
        }
        
        $instances = $this->resolveDependencies($dependencies, $parameters);
        
        return $reflector->invokeArgs($instance, $instances);
    }
    
    /**
     * Register an extension callback
     *
     * @param string $abstract
     * @param \Closure $closure
     * @return void
     */
    public function extend($abstract, \Closure $closure)
    {
        if (!isset($this->extensions[$abstract])) {
            $this->extensions[$abstract] = [];
        }
        
        $this->extensions[$abstract][] = $closure;
    }
    
    /**
     * Register a resolving callback
     *
     * @param string $abstract
     * @param \Closure $callback
     * @return void
     */
    public function resolving($abstract, \Closure $callback)
    {
        if (!isset($this->resolvingCallbacks[$abstract])) {
            $this->resolvingCallbacks[$abstract] = [];
        }
        
        $this->resolvingCallbacks[$abstract][] = $callback;
    }
    
    /**
     * Register a resolved callback
     *
     * @param string $abstract
     * @param \Closure $callback
     * @return void
     */
    public function resolved($abstract, \Closure $callback)
    {
        if (!isset($this->resolvedCallbacks[$abstract])) {
            $this->resolvedCallbacks[$abstract] = [];
        }
        
        $this->resolvedCallbacks[$abstract][] = $callback;
    }
    
    /**
     * Fire the resolving callbacks for the given abstract type
     *
     * @param string $abstract
     * @param mixed $object
     * @return void
     */
    protected function fireResolvingCallbacks($abstract, $object)
    {
        if (isset($this->resolvingCallbacks[$abstract])) {
            foreach ($this->resolvingCallbacks[$abstract] as $callback) {
                $callback($object, $this);
            }
        }
    }
    
    /**
     * Fire the resolved callbacks for the given abstract type
     *
     * @param string $abstract
     * @param mixed $object
     * @return void
     */
    protected function fireResolvedCallbacks($abstract, $object)
    {
        if (isset($this->resolvedCallbacks[$abstract])) {
            foreach ($this->resolvedCallbacks[$abstract] as $callback) {
                $callback($object, $this);
            }
        }
    }
    
    /**
     * Apply extensions to the resolved object
     *
     * @param string $abstract
     * @param mixed $object
     * @return mixed
     */
    protected function applyExtensions($abstract, $object)
    {
        if (isset($this->extensions[$abstract])) {
            foreach ($this->extensions[$abstract] as $extension) {
                $object = $extension($object, $this);
            }
        }
        
        return $object;
    }
    
    /**
     * Clone is not allowed for singletons
     */
    private function __clone() {}
    
    /**
     * Wakeup is not allowed for singletons
     */
    public function __wakeup() {}
}