<?php

namespace App\Core\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\MiddlewareException;

/**
 * Middleware Manager
 * Handles middleware registration, aliases, groups, and pipeline management
 */
class MiddlewareManager
{
    /**
     * @var Container
     */
    protected $container;
    
    /**
     * @var array Registered middleware aliases
     */
    protected $aliases = [];
    
    /**
     * @var array Registered middleware groups
     */
    protected $groups = [];
    
    /**
     * @var array Global middleware
     */
    protected $globalMiddleware = [];
    
    /**
     * @var array Route middleware
     */
    protected $routeMiddleware = [];
    
    /**
     * @var MiddlewareResolver
     */
    protected $resolver;
    
    /**
     * @var MiddlewarePipeline
     */
    protected $pipeline;
    
    /**
     * @var array Middleware priorities
     */
    protected $priorities = [
        'security' => 1000,
        'cors' => 900,
        'csrf' => 800,
        'auth' => 700,
        'api.auth' => 700,
        'role' => 600,
        'permission' => 600,
        'throttle' => 500,
        'validate' => 400,
        'log' => 100
    ];
    
    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->resolver = new MiddlewareResolver($container);
        $this->pipeline = new MiddlewarePipeline($container, $this->resolver);
        
        $this->registerDefaultAliases();
        $this->registerDefaultGroups();
    }
    
    /**
     * Register default middleware aliases
     * 
     * @return void
     */
    protected function registerDefaultAliases()
    {
        $this->aliases = [
            'auth' => \App\Middlewares\AuthMiddleware::class,
            'cors' => \App\Middlewares\CorsMiddleware::class,
            'csrf' => \App\Middlewares\CsrfMiddleware::class,
            'rate_limit' => \App\Middlewares\RateLimitMiddleware::class,
            'logging' => \App\Middlewares\LoggingMiddleware::class,
            'validation' => \App\Middlewares\ValidationMiddleware::class,
        ];
    }
    
    /**
     * Register default middleware groups
     * 
     * @return void
     */
    protected function registerDefaultGroups()
    {
        $this->groups = [
            'web' => [
                'cors',
                'csrf',
                'logging'
            ],
            'api' => [
                'cors',
                'rate_limit',
                'logging'
            ],
            'auth' => [
                'auth'
            ],
            'guest' => [
                // Middleware for guest users
            ]
        ];
    }
    
    /**
     * Register a middleware alias
     * 
     * @param string $alias
     * @param string $class
     * @return void
     */
    public function alias($alias, $class)
    {
        $this->aliases[$alias] = $class;
    }
    
    /**
     * Register multiple middleware aliases
     * 
     * @param array $aliases
     * @return void
     */
    public function aliases(array $aliases)
    {
        $this->aliases = array_merge($this->aliases, $aliases);
    }
    
    /**
     * Register a middleware group
     * 
     * @param string $name
     * @param array $middleware
     * @return void
     */
    public function group($name, array $middleware)
    {
        $this->groups[$name] = $middleware;
    }
    
    /**
     * Add middleware to global stack
     * 
     * @param string|array $middleware
     * @return void
     */
    public function global($middleware)
    {
        if (is_array($middleware)) {
            $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
        } else {
            $this->globalMiddleware[] = $middleware;
        }
    }
    
    /**
     * Add middleware for specific route
     * 
     * @param string $route
     * @param string|array $middleware
     * @return void
     */
    public function route($route, $middleware)
    {
        if (!isset($this->routeMiddleware[$route])) {
            $this->routeMiddleware[$route] = [];
        }
        
        if (is_array($middleware)) {
            $this->routeMiddleware[$route] = array_merge($this->routeMiddleware[$route], $middleware);
        } else {
            $this->routeMiddleware[$route][] = $middleware;
        }
    }
    
    /**
     * Execute middleware for a request
     * 
     * @param array $middleware
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function execute(array $middleware, $request, callable $next)
    {
        $pipeline = $this->createPipeline();
        
        // Add global middleware first
        foreach ($this->globalMiddleware as $globalMiddleware) {
            $priority = $this->getMiddlewarePriority($globalMiddleware);
            $pipeline->add($globalMiddleware, $priority);
        }
        
        // Add route-specific middleware
        foreach ($middleware as $routeMiddleware) {
            $priority = $this->getMiddlewarePriority($routeMiddleware);
            $pipeline->add($routeMiddleware, $priority);
        }
        
        return $pipeline->execute($request, $next);
    }
    
    /**
     * Create a new middleware pipeline
     * 
     * @return MiddlewarePipeline
     */
    public function createPipeline()
    {
        return $this->pipeline->fresh();
    }
    
    /**
     * Add global middleware
     * 
     * @param mixed $middleware
     * @param int $priority
     * @return $this
     */
    public function addGlobalMiddleware($middleware, $priority = null)
    {
        if ($priority === null) {
            $priority = $this->getMiddlewarePriority($middleware);
        }
        
        $this->globalMiddleware[] = [
            'middleware' => $middleware,
            'priority' => $priority
        ];
        
        // Sort by priority
        usort($this->globalMiddleware, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        return $this;
    }
    
    /**
     * Remove global middleware
     * 
     * @param string $middleware
     * @return $this
     */
    public function removeGlobalMiddleware($middleware)
    {
        $this->globalMiddleware = array_filter($this->globalMiddleware, function($item) use ($middleware) {
            return $item['middleware'] !== $middleware;
        });
        
        return $this;
    }
    
    /**
     * Get middleware priority
     * 
     * @param mixed $middleware
     * @return int
     */
    protected function getMiddlewarePriority($middleware)
    {
        // If middleware is an alias, get its priority
        if (is_string($middleware) && isset($this->priorities[$middleware])) {
            return $this->priorities[$middleware];
        }
        
        // Extract middleware name from parameters
        if (is_string($middleware) && strpos($middleware, ':') !== false) {
            $name = explode(':', $middleware)[0];
            if (isset($this->priorities[$name])) {
                return $this->priorities[$name];
            }
        }
        
        // Default priority
        return 0;
    }
    
    /**
     * Set middleware priority
     * 
     * @param string $middleware
     * @param int $priority
     * @return $this
     */
    public function setPriority($middleware, $priority)
    {
        $this->priorities[$middleware] = $priority;
        return $this;
    }
    
    /**
     * Get middleware priorities
     * 
     * @return array
     */
    public function getPriorities()
    {
        return $this->priorities;
    }
    
    /**
     * Create conditional middleware pipeline
     * 
     * @param callable $condition
     * @return MiddlewarePipeline
     */
    public function when(callable $condition)
    {
        $pipeline = $this->createPipeline();
        
        foreach ($this->globalMiddleware as $item) {
            $pipeline->addIf($item['middleware'], $condition, $item['priority']);
        }
        
        return $pipeline;
    }
    
    /**
     * Create middleware pipeline for specific routes
     * 
     * @param array|string $routes
     * @return MiddlewarePipeline
     */
    public function forRoutes($routes)
    {
        $pipeline = $this->createPipeline();
        
        foreach ($this->globalMiddleware as $item) {
            $pipeline->addForRoutes($item['middleware'], $routes, $item['priority']);
        }
        
        return $pipeline;
    }
    
    /**
     * Create middleware pipeline except for specific routes
     * 
     * @param array|string $routes
     * @return MiddlewarePipeline
     */
    public function exceptRoutes($routes)
    {
        $pipeline = $this->createPipeline();
        
        foreach ($this->globalMiddleware as $item) {
            $pipeline->addExceptRoutes($item['middleware'], $routes, $item['priority']);
        }
        
        return $pipeline;
    }
    
    /**
     * Create middleware pipeline for specific HTTP methods
     * 
     * @param array|string $methods
     * @return MiddlewarePipeline
     */
    public function forMethods($methods)
    {
        $pipeline = $this->createPipeline();
        
        foreach ($this->globalMiddleware as $item) {
            $pipeline->addForMethods($item['middleware'], $methods, $item['priority']);
        }
        
        return $pipeline;
    }
    
    /**
     * Handle route-specific middleware
     * 
     * @param Request $request The request
     * @param array $middleware The middleware to execute
     * @param callable $next The next handler
     * @return Response
     */
    public function handleRouteMiddleware(Request $request, array $middleware, callable $next)
    {
        // Resolve middleware instances
        $middlewareStack = $this->resolveMiddleware($middleware);
        
        // Create the middleware pipeline
        $pipeline = $this->buildPipeline($middlewareStack, $next);
        
        // Execute the pipeline
        return $pipeline($request);
    }
    
    /**
     * Get middleware for the current route
     * 
     * @param Request $request
     * @return array
     */
    protected function getRouteMiddleware(Request $request)
    {
        $path = $request->getPath();
        $middleware = [];
        
        foreach ($this->routeMiddleware as $route => $routeMiddleware) {
            if ($this->matchesRoute($path, $route)) {
                $middleware = array_merge($middleware, $routeMiddleware);
            }
        }
        
        return $middleware;
    }
    
    /**
     * Check if path matches route pattern
     * 
     * @param string $path
     * @param string $route
     * @return bool
     */
    protected function matchesRoute($path, $route)
    {
        // Exact match
        if ($path === $route) {
            return true;
        }
        
        // Wildcard match
        if (strpos($route, '*') !== false) {
            $pattern = '/^' . str_replace('*', '.*', preg_quote($route, '/')) . '$/';
            return preg_match($pattern, $path);
        }
        
        // Parameter match
        if (strpos($route, '{') !== false) {
            $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $route);
            $pattern = '/^' . str_replace('/', '\/', $pattern) . '$/';
            return preg_match($pattern, $path);
        }
        
        return false;
    }
    
    /**
     * Resolve middleware instances
     * 
     * @param array $middleware
     * @return array
     */
    protected function resolveMiddleware(array $middleware)
    {
        $resolved = [];
        
        foreach ($middleware as $middlewareItem) {
            $resolved = array_merge($resolved, $this->resolveMiddlewareItem($middlewareItem));
        }
        
        return $resolved;
    }
    
    /**
     * Resolve a single middleware item
     * 
     * @param string $middleware
     * @return array
     */
    protected function resolveMiddlewareItem($middleware)
    {
        // Check if it's a group
        if (isset($this->groups[$middleware])) {
            return $this->resolveMiddleware($this->groups[$middleware]);
        }
        
        // Check if it's an alias
        if (isset($this->aliases[$middleware])) {
            $class = $this->aliases[$middleware];
        } else {
            $class = $middleware;
        }
        
        // Create middleware instance
        try {
            $instance = new $class($this->app);
            
            if (!$instance instanceof Middleware) {
                throw new \InvalidArgumentException("Middleware {$class} must extend Middleware class");
            }
            
            return [$instance];
        } catch (\Exception $e) {
            $this->logError('Failed to resolve middleware', [
                'middleware' => $middleware,
                'class' => $class,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Create middleware pipeline
     * 
     * @param array $middleware
     * @param callable $destination
     * @return callable
     */
    protected function buildPipeline(array $middleware, callable $destination)
    {
        return array_reduce(
            array_reverse($middleware),
            function ($next, $middleware) {
                return function (Request $request) use ($middleware, $next) {
                    try {
                        return $middleware->handle($request, $next);
                    } catch (\Exception $e) {
                        $this->logError('Middleware execution failed', [
                            'middleware' => get_class($middleware),
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        throw $e;
                    }
                };
            },
            $destination
        );
    }
    
    /**
     * Get all registered aliases
     * 
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }
    
    /**
     * Get all registered groups
     * 
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }
    
    /**
     * Get global middleware
     * 
     * @return array
     */
    public function getGlobalMiddleware()
    {
        return array_column($this->globalMiddleware, 'middleware');
    }
    
    /**
     * Get all route middleware
     * 
     * @return array
     */
    public function getAllRouteMiddleware()
    {
        return $this->routeMiddleware;
    }
    
    /**
     * Clear all middleware
     * 
     * @return void
     */
    public function clear()
    {
        $this->globalMiddleware = [];
        $this->routeMiddleware = [];
    }
    
    /**
     * Remove middleware from global stack
     * 
     * @param string $middleware
     * @return void
     */
    public function removeGlobal($middleware)
    {
        $this->globalMiddleware = array_filter($this->globalMiddleware, function ($item) use ($middleware) {
            return $item !== $middleware;
        });
    }
    
    /**
     * Remove middleware from route
     * 
     * @param string $route
     * @param string $middleware
     * @return void
     */
    public function removeRoute($route, $middleware)
    {
        if (isset($this->routeMiddleware[$route])) {
            $this->routeMiddleware[$route] = array_filter(
                $this->routeMiddleware[$route],
                function ($item) use ($middleware) {
                    return $item !== $middleware;
                }
            );
        }
    }
    
    /**
     * Check if middleware is registered
     * 
     * @param string $middleware
     * @return bool
     */
    public function hasMiddleware($middleware)
    {
        return isset($this->aliases[$middleware]) || 
               isset($this->groups[$middleware]) || 
               class_exists($middleware);
    }
    
    /**
     * Get middleware statistics
     * 
     * @return array
     */
    public function getStats()
    {
        return [
            'aliases_count' => count($this->aliases),
            'groups_count' => count($this->groups),
            'global_middleware_count' => count($this->globalMiddleware),
            'priorities_count' => count($this->priorities),
            'registered_aliases' => array_keys($this->aliases),
            'registered_groups' => array_keys($this->groups)
        ];
    }
}