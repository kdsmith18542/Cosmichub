<?php

namespace App\Core\Middleware;

use App\Core\Application;
use App\Core\Http\Request;

/**
 * MiddlewareResolver class for resolving middleware
 */
class MiddlewareResolver
{
    /**
     * @var Application The application instance
     */
    protected $app;
    
    /**
     * @var array The middleware aliases
     */
    protected $middleware = [];
    
    /**
     * @var array The middleware groups
     */
    protected $middlewareGroups = [];
    
    /**
     * Create a new middleware resolver instance
     * 
     * @param Application $app The application instance
     * @param array $middleware The middleware aliases
     * @param array $middlewareGroups The middleware groups
     */
    public function __construct(Application $app, array $middleware = [], array $middlewareGroups = [])
    {
        $this->app = $app;
        $this->middleware = $middleware;
        $this->middlewareGroups = $middlewareGroups;
    }
    
    /**
     * Resolve a middleware
     * 
     * @param string|array|callable|object $middleware The middleware
     * @return callable
     */
    public function resolve($middleware)
    {
        // If the middleware is a closure, return it
        if ($middleware instanceof \Closure) {
            return $middleware;
        }
        
        // If the middleware is an object, check if it implements the interface
        if (is_object($middleware)) {
            if ($middleware instanceof MiddlewareInterface) {
                return [$middleware, 'handle'];
            }
            
            throw new \InvalidArgumentException('Middleware must implement MiddlewareInterface');
        }
        
        // If the middleware is a string, resolve it
        if (is_string($middleware)) {
            return $this->resolveString($middleware);
        }
        
        // If the middleware is an array, resolve each item
        if (is_array($middleware)) {
            return $this->resolveArray($middleware);
        }
        
        throw new \InvalidArgumentException('Invalid middleware');
    }
    
    /**
     * Resolve a string middleware
     * 
     * @param string $middleware The middleware
     * @return callable
     */
    protected function resolveString($middleware)
    {
        // Check if the middleware is a group
        if (isset($this->middlewareGroups[$middleware])) {
            return $this->resolveArray($this->middlewareGroups[$middleware]);
        }
        
        // Check if the middleware is an alias
        if (isset($this->middleware[$middleware])) {
            $middleware = $this->middleware[$middleware];
        }
        
        // If the middleware contains a colon, it has parameters
        if (strpos($middleware, ':') !== false) {
            list($middleware, $parameters) = explode(':', $middleware, 2);
            $parameters = explode(',', $parameters);
        } else {
            $parameters = [];
        }
        
        // Create the middleware instance
        $instance = $this->app->make($middleware);
        
        // Check if the middleware implements the interface
        if (!$instance instanceof MiddlewareInterface) {
            throw new \InvalidArgumentException('Middleware must implement MiddlewareInterface');
        }
        
        // Return a closure that calls the middleware handle method with the parameters
        return function (Request $request, callable $next = null) use ($instance, $parameters) {
            return $instance->handle($request, $next, ...$parameters);
        };
    }
    
    /**
     * Resolve an array of middleware
     * 
     * @param array $middleware The middleware array
     * @return callable
     */
    protected function resolveArray(array $middleware)
    {
        // If the array is empty, return a pass-through middleware
        if (empty($middleware)) {
            return function (Request $request, callable $next = null) {
                return $next ? $next($request) : $request;
            };
        }
        
        // Resolve each middleware in the array
        $resolved = array_map([$this, 'resolve'], $middleware);
        
        // Return a closure that calls each middleware in sequence
        return function (Request $request, callable $next = null) use ($resolved) {
            $current = $next;
            
            // Build the middleware stack from the end to the beginning
            foreach (array_reverse($resolved) as $middleware) {
                $current = function (Request $request) use ($middleware, $current) {
                    return $middleware($request, $current);
                };
            }
            
            return $current($request);
        };
    }
    
    /**
     * Add a middleware alias
     * 
     * @param string $alias The middleware alias
     * @param string $middleware The middleware class
     * @return $this
     */
    public function alias($alias, $middleware)
    {
        $this->middleware[$alias] = $middleware;
        
        return $this;
    }
    
    /**
     * Add a middleware group
     * 
     * @param string $name The group name
     * @param array $middleware The middleware array
     * @return $this
     */
    public function group($name, array $middleware)
    {
        $this->middlewareGroups[$name] = $middleware;
        
        return $this;
    }
    
    /**
     * Get all middleware aliases
     * 
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }
    
    /**
     * Get all middleware groups
     * 
     * @return array
     */
    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }
}