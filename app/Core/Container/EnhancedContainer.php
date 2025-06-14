<?php

namespace App\Core\Container;

use App\Core\Exceptions\ContainerException;
use App\Core\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Closure;

/**
 * Enhanced Dependency Injection Container
 * 
 * PSR-11 compliant container with advanced features like
 * auto-wiring, contextual binding, and service tagging
 */
class EnhancedContainer implements ContainerInterface
{
    /**
     * Container bindings
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Container instances
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Service aliases
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Singleton bindings
     *
     * @var array
     */
    protected $singletons = [];

    /**
     * Contextual bindings
     *
     * @var array
     */
    protected $contextual = [];

    /**
     * Tagged services
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Service resolving callbacks
     *
     * @var array
     */
    protected $resolving = [];

    /**
     * Service resolved callbacks
     *
     * @var array
     */
    protected $resolved = [];

    /**
     * Build stack to prevent circular dependencies
     *
     * @var array
     */
    protected $buildStack = [];

    /**
     * Parameter overrides for current resolution
     *
     * @var array
     */
    protected $with = [];

    /**
     * Bind a service to the container
     *
     * @param string $abstract
     * @param mixed $concrete
     * @param bool $shared
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->dropStaleInstances($abstract);

        if ($concrete === null) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');

        if ($shared) {
            $this->singletons[$abstract] = true;
        }
    }

    /**
     * Bind a service as singleton
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Bind an existing instance as singleton
     *
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        $this->removeAbstractAlias($abstract);
        $this->instances[$abstract] = $instance;
        return $instance;
    }

    /**
     * Register an alias for a service
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias($abstract, $alias)
    {
        if ($alias === $abstract) {
            throw new ContainerException("Cannot alias service to itself: {$abstract}");
        }

        $this->aliases[$alias] = $abstract;
    }

    /**
     * Tag services for group resolution
     *
     * @param array|string $abstracts
     * @param array|string $tags
     * @return void
     */
    public function tag($abstracts, $tags)
    {
        $tags = is_array($tags) ? $tags : [$tags];
        $abstracts = is_array($abstracts) ? $abstracts : [$abstracts];

        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ($abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    /**
     * Get all services tagged with a specific tag
     *
     * @param string $tag
     * @return array
     */
    public function tagged($tag)
    {
        if (!isset($this->tags[$tag])) {
            return [];
        }

        $services = [];
        foreach ($this->tags[$tag] as $abstract) {
            $services[] = $this->make($abstract);
        }

        return $services;
    }

    /**
     * Define contextual binding
     *
     * @param string $concrete
     * @return ContextualBindingBuilder
     */
    public function when($concrete)
    {
        return new ContextualBindingBuilder($this, $this->getAlias($concrete));
    }

    /**
     * Add contextual binding
     *
     * @param string $concrete
     * @param string $abstract
     * @param mixed $implementation
     * @return void
     */
    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    /**
     * Resolve a service from the container
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function make($abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Resolve a service from the container
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws ContainerException
     * @throws NotFoundException
     */
    protected function resolve($abstract, $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        $needsContextualBuild = !empty($parameters) || !is_null(
            $this->getContextualConcrete($abstract)
        );

        if (isset($this->instances[$abstract]) && !$needsContextualBuild) {
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            $object = $this->make($concrete);
        }

        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        if ($this->isShared($abstract) && !$needsContextualBuild) {
            $this->instances[$abstract] = $object;
        }

        $this->fireResolvingCallbacks($abstract, $object);

        $this->resolved[$abstract] = true;

        array_pop($this->with);

        return $object;
    }

    /**
     * Build a concrete instance
     *
     * @param mixed $concrete
     * @return mixed
     * @throws ContainerException
     */
    public function build($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ContainerException("Target class [{$concrete}] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            return $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            array_pop($this->buildStack);
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (ContainerException $e) {
            array_pop($this->buildStack);
            throw $e;
        }

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve constructor dependencies
     *
     * @param array $dependencies
     * @return array
     * @throws ContainerException
     */
    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);
                continue;
            }

            $results[] = is_null($dependency->getClass())
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);
        }

        return $results;
    }

    /**
     * Resolve a class-based dependency
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws ContainerException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name);
        } catch (ContainerException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }

    /**
     * Resolve a primitive dependency
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws ContainerException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ContainerException(
            "Unresolvable dependency resolving [{$parameter}] in class {$parameter->getDeclaringClass()->getName()}"
        );
    }

    /**
     * Check if service exists in container
     *
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return $this->bound($id);
    }

    /**
     * Get service from container (PSR-11)
     *
     * @param string $id
     * @return mixed
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function get($id)
    {
        try {
            return $this->resolve($id);
        } catch (ContainerException $e) {
            if ($this->has($id)) {
                throw $e;
            }
            throw new NotFoundException("Service '{$id}' not found in container.", 0, $e);
        }
    }

    /**
     * Check if service is bound
     *
     * @param string $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               $this->isAlias($abstract);
    }

    /**
     * Check if service is resolved
     *
     * @param string $abstract
     * @return bool
     */
    public function resolved($abstract)
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Get the alias for an abstract if available
     *
     * @param string $abstract
     * @return string
     */
    public function getAlias($abstract)
    {
        if (!isset($this->aliases[$abstract])) {
            return $abstract;
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * Check if abstract is an alias
     *
     * @param string $name
     * @return bool
     */
    public function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Get concrete implementation for abstract
     *
     * @param string $abstract
     * @return mixed
     */
    protected function getConcrete($abstract)
    {
        if (!is_null($concrete = $this->getContextualConcrete($abstract))) {
            return $concrete;
        }

        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Get contextual concrete implementation
     *
     * @param string $abstract
     * @return mixed|null
     */
    protected function getContextualConcrete($abstract)
    {
        if (!is_null($binding = $this->findInContextualBindings($abstract))) {
            return $binding;
        }

        if (empty($this->abstractAliases[$abstract])) {
            return null;
        }

        foreach ($this->abstractAliases[$abstract] as $alias) {
            if (!is_null($binding = $this->findInContextualBindings($alias))) {
                return $binding;
            }
        }

        return null;
    }

    /**
     * Find contextual binding
     *
     * @param string $abstract
     * @return mixed|null
     */
    protected function findInContextualBindings($abstract)
    {
        return $this->contextual[end($this->buildStack)][$abstract] ?? null;
    }

    /**
     * Check if concrete is buildable
     *
     * @param mixed $concrete
     * @param string $abstract
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Check if service is shared (singleton)
     *
     * @param string $abstract
     * @return bool
     */
    public function isShared($abstract)
    {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Get closure for binding
     *
     * @param string $abstract
     * @param string $concrete
     * @return Closure
     */
    protected function getClosure($abstract, $concrete)
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve($concrete, $parameters);
        };
    }

    /**
     * Drop stale instances and aliases
     *
     * @param string $abstract
     * @return void
     */
    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Remove abstract alias
     *
     * @param string $searched
     * @return void
     */
    protected function removeAbstractAlias($searched)
    {
        if (!isset($this->aliases[$searched])) {
            return;
        }

        foreach ($this->abstractAliases as $abstract => $aliases) {
            foreach ($aliases as $index => $alias) {
                if ($alias == $searched) {
                    unset($this->abstractAliases[$abstract][$index]);
                }
            }
        }
    }

    /**
     * Handle non-instantiable class
     *
     * @param string $concrete
     * @return void
     * @throws ContainerException
     */
    protected function notInstantiable($concrete)
    {
        if (!empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);
            $message = "Target [{$concrete}] is not instantiable while building [{$previous}].";
        } else {
            $message = "Target [{$concrete}] is not instantiable.";
        }

        throw new ContainerException($message);
    }

    /**
     * Get extenders for abstract
     *
     * @param string $abstract
     * @return array
     */
    protected function getExtenders($abstract)
    {
        return $this->extenders[$this->getAlias($abstract)] ?? [];
    }

    /**
     * Fire resolving callbacks
     *
     * @param string $abstract
     * @param mixed $object
     * @return void
     */
    protected function fireResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);
        $this->fireCallbackArray($object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks));
        $this->fireAfterResolvingCallbacks($abstract, $object);
    }

    /**
     * Fire after resolving callbacks
     *
     * @param string $abstract
     * @param mixed $object
     * @return void
     */
    protected function fireAfterResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);
        $this->fireCallbackArray($object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks));
    }

    /**
     * Get last parameter override
     *
     * @return array
     */
    protected function getLastParameterOverride()
    {
        return count($this->with) ? end($this->with) : [];
    }

    /**
     * Check if parameter override exists
     *
     * @param ReflectionParameter $dependency
     * @return bool
     */
    protected function hasParameterOverride($dependency)
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    /**
     * Get parameter override
     *
     * @param ReflectionParameter $dependency
     * @return mixed
     */
    protected function getParameterOverride($dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * Flush the container
     *
     * @return void
     */
    public function flush()
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
    }
}