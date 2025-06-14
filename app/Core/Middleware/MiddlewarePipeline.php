<?php

namespace App\Core\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Container\Container;
use App\Exceptions\MiddlewareException;

/**
 * Middleware Pipeline
 * Manages the execution of middleware chains with advanced features
 */
class MiddlewarePipeline
{
    /**
     * @var Container
     */
    protected $container;
    
    /**
     * @var MiddlewareResolver
     */
    protected $resolver;
    
    /**
     * @var array Middleware stack
     */
    protected $middleware = [];
    
    /**
     * @var array Conditional middleware
     */
    protected $conditionalMiddleware = [];
    
    /**
     * @var array Middleware priorities
     */
    protected $priorities = [];
    
    /**
     * @var array Skip conditions
     */
    protected $skipConditions = [];
    
    /**
     * @var bool Whether to sort by priority
     */
    protected $sortByPriority = true;
    
    /**
     * Constructor
     * 
     * @param Container $container
     * @param MiddlewareResolver $resolver
     */
    public function __construct(Container $container, MiddlewareResolver $resolver)
    {
        $this->container = $container;
        $this->resolver = $resolver;
    }
    
    /**
     * Add middleware to the pipeline
     * 
     * @param mixed $middleware
     * @param int $priority
     * @return $this
     */
    public function add($middleware, $priority = 0)
    {
        $this->middleware[] = [
            'middleware' => $middleware,
            'priority' => $priority,
            'conditions' => []
        ];
        
        return $this;
    }
    
    /**
     * Add conditional middleware
     * 
     * @param mixed $middleware
     * @param callable $condition
     * @param int $priority
     * @return $this
     */
    public function addIf($middleware, callable $condition, $priority = 0)
    {
        $this->middleware[] = [
            'middleware' => $middleware,
            'priority' => $priority,
            'conditions' => [$condition]
        ];
        
        return $this;
    }
    
    /**
     * Add middleware unless condition is true
     * 
     * @param mixed $middleware
     * @param callable $condition
     * @param int $priority
     * @return $this
     */
    public function addUnless($middleware, callable $condition, $priority = 0)
    {
        $this->middleware[] = [
            'middleware' => $middleware,
            'priority' => $priority,
            'conditions' => [function($request) use ($condition) {
                return !$condition($request);
            }]
        ];
        
        return $this;
    }
    
    /**
     * Add middleware for specific routes
     * 
     * @param mixed $middleware
     * @param array|string $routes
     * @param int $priority
     * @return $this
     */
    public function addForRoutes($middleware, $routes, $priority = 0)
    {
        $routes = is_array($routes) ? $routes : [$routes];
        
        $this->middleware[] = [
            'middleware' => $middleware,
            'priority' => $priority,
            'conditions' => [function($request) use ($routes) {
                $currentRoute = $request->getPath();
                
                foreach ($routes as $route) {
                    if ($this->routeMatches($currentRoute, $route)) {
                        return true;
                    }
                }
                
                return false;
            }]
        ];
        
        return $this;
    }
    
    /**
     * Add middleware except for specific routes
     * 
     * @param mixed $middleware
     * @param array|string $routes
     * @param int $priority
     * @return $this
     */
    public function addExceptRoutes($middleware, $routes, $priority = 0)
    {
        $routes = is_array($routes) ? $routes : [$routes];
        
        $this->middleware[] = [
            'middleware' => $middleware,
            'priority' => $priority,
            'conditions' => [function($request) use ($routes) {
                $currentRoute = $request->getPath();
                
                foreach ($routes as $route) {
                    if ($this->routeMatches($currentRoute, $route)) {
                        return false;
                    }
                }
                
                return true;
            }]
        ];
        
        return $this;
    }
    
    /**
     * Add middleware for specific HTTP methods
     * 
     * @param mixed $middleware
     * @param array|string $methods
     * @param int $priority
     * @return $this
     */
    public function addForMethods($middleware, $methods, $priority = 0)
    {
        $methods = is_array($methods) ? $methods : [$methods];
        $methods = array_map('strtoupper', $methods);
        
        $this->middleware[] = [
            'middleware' => $middleware,
            'priority' => $priority,
            'conditions' => [function($request) use ($methods) {
                return in_array(strtoupper($request->getMethod()), $methods);
            }]
        ];
        
        return $this;
    }
    
    /**
     * Add middleware group
     * 
     * @param array $middlewareGroup
     * @param int $basePriority
     * @return $this
     */
    public function addGroup(array $middlewareGroup, $basePriority = 0)
    {
        foreach ($middlewareGroup as $index => $middleware) {
            $priority = $basePriority + $index;
            $this->add($middleware, $priority);
        }
        
        return $this;
    }
    
    /**
     * Prepend middleware to the beginning of the pipeline
     * 
     * @param mixed $middleware
     * @return $this
     */
    public function prepend($middleware)
    {
        $maxPriority = $this->getMaxPriority();
        $this->add($middleware, $maxPriority + 100);
        
        return $this;
    }
    
    /**
     * Append middleware to the end of the pipeline
     * 
     * @param mixed $middleware
     * @return $this
     */
    public function append($middleware)
    {
        $minPriority = $this->getMinPriority();
        $this->add($middleware, $minPriority - 100);
        
        return $this;
    }
    
    /**
     * Remove middleware from the pipeline
     * 
     * @param string $middlewareClass
     * @return $this
     */
    public function remove($middlewareClass)
    {
        $this->middleware = array_filter($this->middleware, function($item) use ($middlewareClass) {
            $resolved = $this->resolver->resolve($item['middleware']);
            return !($resolved instanceof $middlewareClass);
        });
        
        return $this;
    }
    
    /**
     * Replace middleware in the pipeline
     * 
     * @param string $oldMiddleware
     * @param mixed $newMiddleware
     * @return $this
     */
    public function replace($oldMiddleware, $newMiddleware)
    {
        foreach ($this->middleware as &$item) {
            if ($item['middleware'] === $oldMiddleware) {
                $item['middleware'] = $newMiddleware;
            }
        }
        
        return $this;
    }
    
    /**
     * Clear all middleware from the pipeline
     * 
     * @return $this
     */
    public function clear()
    {
        $this->middleware = [];
        return $this;
    }
    
    /**
     * Execute the middleware pipeline
     * 
     * @param Request $request
     * @param callable $destination
     * @return Response
     * @throws MiddlewareException
     */
    public function execute(Request $request, callable $destination)
    {
        $middleware = $this->getApplicableMiddleware($request);
        
        if ($this->sortByPriority) {
            $middleware = $this->sortByPriority($middleware);
        }
        
        return $this->createPipeline($middleware, $destination)($request);
    }
    
    /**
     * Get applicable middleware for the request
     * 
     * @param Request $request
     * @return array
     */
    protected function getApplicableMiddleware(Request $request)
    {
        $applicable = [];
        
        foreach ($this->middleware as $item) {
            if ($this->shouldApplyMiddleware($item, $request)) {
                $applicable[] = $item;
            }
        }
        
        return $applicable;
    }
    
    /**
     * Check if middleware should be applied
     * 
     * @param array $item
     * @param Request $request
     * @return bool
     */
    protected function shouldApplyMiddleware(array $item, Request $request)
    {
        foreach ($item['conditions'] as $condition) {
            if (!$condition($request)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sort middleware by priority (higher priority first)
     * 
     * @param array $middleware
     * @return array
     */
    protected function sortByPriority(array $middleware)
    {
        usort($middleware, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        return $middleware;
    }
    
    /**
     * Create the middleware pipeline
     * 
     * @param array $middleware
     * @param callable $destination
     * @return callable
     */
    protected function createPipeline(array $middleware, callable $destination)
    {
        return array_reduce(
            array_reverse($middleware),
            function($next, $item) {
                return function($request) use ($next, $item) {
                    try {
                        $middlewareInstance = $this->resolver->resolve($item['middleware']);
                        return $middlewareInstance->handle($request, $next);
                    } catch (\Exception $e) {
                        throw new MiddlewareException(
                            'Middleware execution failed: ' . $e->getMessage(),
                            0,
                            $e
                        );
                    }
                };
            },
            $destination
        );
    }
    
    /**
     * Check if route matches pattern
     * 
     * @param string $route
     * @param string $pattern
     * @return bool
     */
    protected function routeMatches($route, $pattern)
    {
        // Exact match
        if ($route === $pattern) {
            return true;
        }
        
        // Wildcard match
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace('*', '.*', preg_quote($pattern, '/'));
            return preg_match('/^' . $pattern . '$/', $route);
        }
        
        // Prefix match
        if (substr($pattern, -1) === '/') {
            return strpos($route, $pattern) === 0;
        }
        
        return false;
    }
    
    /**
     * Get maximum priority
     * 
     * @return int
     */
    protected function getMaxPriority()
    {
        if (empty($this->middleware)) {
            return 0;
        }
        
        return max(array_column($this->middleware, 'priority'));
    }
    
    /**
     * Get minimum priority
     * 
     * @return int
     */
    protected function getMinPriority()
    {
        if (empty($this->middleware)) {
            return 0;
        }
        
        return min(array_column($this->middleware, 'priority'));
    }
    
    /**
     * Get all middleware in the pipeline
     * 
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }
    
    /**
     * Get middleware count
     * 
     * @return int
     */
    public function count()
    {
        return count($this->middleware);
    }
    
    /**
     * Check if pipeline is empty
     * 
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->middleware);
    }
    
    /**
     * Enable or disable priority sorting
     * 
     * @param bool $sort
     * @return $this
     */
    public function setSortByPriority($sort)
    {
        $this->sortByPriority = $sort;
        return $this;
    }
    
    /**
     * Create a new pipeline instance
     * 
     * @return static
     */
    public function fresh()
    {
        return new static($this->container, $this->resolver);
    }
}