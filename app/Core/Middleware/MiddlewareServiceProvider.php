<?php

namespace App\Core\Middleware;

use App\Core\ServiceProvider;
use App\Core\Application;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Config\Config;
use App\Core\Routing\Router;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ReflectionClass;
use Psr\Log\LoggerInterface;

/**
 * Middleware Service Provider
 * Registers middleware manager and configures middleware
 */
class MiddlewareServiceProvider extends ServiceProvider
{
    /**
     * The provided services
     *
     * @var array
     */
    protected $provides = [
        MiddlewareManager::class,
        'middleware'
    ];

    /**
     * The services to be registered as singletons
     *
     * @var array
     */
    protected $singletons = [
        MiddlewareManager::class,
        LoggerInterface::class
    ];

    /**
     * The service aliases
     *
     * @var array
     */
    protected $aliases = [
        'middleware' => MiddlewareManager::class
    ];

    /**
     * Default middleware aliases
     *
     * @var array
     */
    protected $defaultAliases = [
        'auth' => \App\Middlewares\AuthMiddleware::class,
        'api.auth' => \App\Middlewares\ApiAuthMiddleware::class,
        'role' => \App\Middlewares\RolePermissionMiddleware::class,
        'permission' => \App\Middlewares\RolePermissionMiddleware::class,
        'security' => \App\Middlewares\SecurityMiddleware::class,
        'cors' => \App\Middlewares\CorsMiddleware::class,
        'csrf' => \App\Middlewares\CsrfMiddleware::class,
        'throttle' => \App\Middlewares\RateLimitMiddleware::class,
        'validate' => \App\Middlewares\ValidationMiddleware::class,
        'log' => \App\Middlewares\LoggingMiddleware::class,
        'request_log' => \App\Middlewares\RequestLoggerMiddleware::class,
    }

    /**
     * Default middleware groups
     *
     * @var array
     */
    protected $defaultGroups = [
        'web' => [
            'security',
            'csrf',
            'cors',
            'log'
        ],
        'api' => [
            'security',
            'cors',
            'throttle',
            'log'
        ],
        'api.auth' => [
            'security',
            'api.auth',
            'cors',
            'throttle',
            'log'
        ],
        'auth' => [
            'auth'
        ],
        'guest' => [
            'security',
            'cors'
        ],
        'admin' => [
            'auth',
            'role',
            'security'
        ],
        'secure' => [
            'security',
            'csrf',
            'throttle'
        ],
        'protected' => [
            'auth',
            'role',
            'security',
            'csrf'
        ]
    ];

    /**
     * Default global middleware
     *
     * @var array
     */
    protected $defaultGlobalMiddleware = [
        'cors',
        'logging',
        'request_log'
    };

    /**
     * Default route middleware
     *
     * @var array
     */
    protected $defaultRouteMiddleware = [
        // Authentication routes
        '/auth/*' => ['guest'],
        '/login' => ['guest'],
        '/register' => ['guest'],
        '/logout' => ['auth'],
        
        // API routes
        '/api/*' => ['api'],
        
        // Admin routes
        '/admin/*' => ['admin'],
        
        // User dashboard
        '/dashboard/*' => ['auth'],
        '/profile/*' => ['auth'],
        
        // Secure operations
        '/payment/*' => ['secure'],
        '/subscription/*' => ['secure'],
        
        // Forms that need CSRF protection
        '/contact' => ['web', 'validation'],
        '/feedback' => ['web', 'validation'],
        
        // Rate-limited endpoints
        '/api/reports/*' => ['rate_limit'],
        '/api/compatibility/*' => ['rate_limit'],
        
        // Validation-required endpoints
        '/auth/register' => ['validation'],
        '/auth/login' => ['validation']
    ];

    /**
     * Middleware paths for auto-discovery
     *
     * @var array
     */
    protected $middlewarePaths = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Register the middleware services
     *
     * @return void
     */
    protected function registerServices()
    {
        $this->logger = $this->container->get(LoggerInterface::class);

        // Register middleware manager
        $this->container->singleton(MiddlewareManager::class, function ($container) {
            return new MiddlewareManager($this->app);
        });

        // Register middleware dependencies
        $this->registerMiddlewareDependencies();

        // Register middleware aliases
        $this->registerMiddlewareAliases();
        
        // Register middleware groups
        $this->registerMiddlewareGroups();
        
        // Register global middleware
        $this->registerGlobalMiddleware();
        
        // Register route-specific middleware
        $this->registerRouteMiddleware();

        // Register middleware discovery if enabled
        if ($this->config('middleware.discovery', false)) {
            $this->registerMiddlewareDiscovery();
        }
    }
    
    /**
     * Boot the middleware services
     *
     * @return void
     */
    protected function bootServices()
    {
        // Configure middleware from config files
        $this->configureFromConfig();

        // Register middleware priorities if enabled
        if ($this->config('middleware.priorities', true)) {
            $this->registerMiddlewarePriorities();
        }

        // Register middleware parameters if enabled
        if ($this->config('middleware.parameters', true)) {
            $this->registerMiddlewareParameters();
        }

        // Register middleware termination handlers if enabled
        if ($this->config('middleware.terminable', true)) {
            $this->registerMiddlewareTerminableHandlers();
        }
    }

    /**
     * Register middleware dependencies
     *
     * @return void
     */
    protected function registerMiddlewareDependencies()
    {
        // Make sure required dependencies are available
        if (!$this->container->has(Request::class)) {
            $this->container->singleton(Request::class, function () {
                return Request::capture();
            });
        }

        if (!$this->container->has(Response::class)) {
            $this->container->bind(Response::class, function () {
                return new Response();
            });
        }
    }
    
    /**
     * Register middleware aliases
     * 
     * @return void
     */
    protected function registerMiddlewareAliases()
    {
        $middleware = $this->container->make(MiddlewareManager::class);
        
        // Get aliases from config or use defaults
        $aliases = $this->config('middleware.aliases', $this->defaultAliases);
        
        // Merge with default aliases to ensure core middleware is always available
        $aliases = array_merge($this->defaultAliases, $aliases);
        
        $middleware->aliases($aliases);

        // Log registered aliases in debug mode
        if ($this->app->isDebug()) {
            $this->logger->debug("Registered middleware aliases: " . implode(", ", array_keys($aliases)));
        }
    }
    
    /**
     * Register middleware groups
     * 
     * @return void
     */
    protected function registerMiddlewareGroups()
    {
        $middleware = $this->container->make(MiddlewareManager::class);
        
        // Get groups from config or use defaults
        $groups = $this->config('middleware.groups', $this->defaultGroups);
        
        // Merge with default groups
        foreach ($this->defaultGroups as $name => $middlewareList) {
            if (!isset($groups[$name])) {
                $groups[$name] = $middlewareList;
            } else {
                $groups[$name] = array_merge($middlewareList, $groups[$name]);
            }
        }
        
        // Register each group
        foreach ($groups as $name => $middlewareList) {
            $middleware->group($name, $middlewareList);
        }

        // Log registered groups in debug mode
        if ($this->app->isDebug()) {
            $this->logger->debug("Registered middleware groups: " . implode(", ", array_keys($groups)));
        }
    }
    
    /**
     * Register global middleware
     * 
     * @return void
     */
    protected function registerGlobalMiddleware()
    {
        $middleware = $this->container->make(MiddlewareManager::class);
        
        // Get global middleware from config or use defaults
        $globalMiddleware = $this->config('middleware.global', $this->defaultGlobalMiddleware);
        
        // Merge with default global middleware
        $globalMiddleware = array_merge($this->defaultGlobalMiddleware, $globalMiddleware);
        
        // Remove duplicates
        $globalMiddleware = array_unique($globalMiddleware);
        
        $middleware->global($globalMiddleware);

        // Log registered global middleware in debug mode
        if ($this->app->isDebug()) {
            $this->logger->debug("Registered global middleware: " . implode(", ", $globalMiddleware));
        }
    }
    
    /**
     * Register route-specific middleware
     * 
     * @return void
     */
    protected function registerRouteMiddleware()
    {
        $middleware = $this->container->make(MiddlewareManager::class);
        
        // Get route middleware from config or use defaults
        $routeMiddleware = $this->config('middleware.routes', $this->defaultRouteMiddleware);
        
        // Merge with default route middleware
        $routeMiddleware = array_merge($this->defaultRouteMiddleware, $routeMiddleware);
        
        // Register each route middleware
        foreach ($routeMiddleware as $route => $middlewareList) {
            $middleware->route($route, $middlewareList);
        }

        // Log registered route middleware in debug mode
        if ($this->app->isDebug()) {
            $this->logger->debug("Registered route-specific middleware for " . count($routeMiddleware) . " routes");
        }
    }

    /**
     * Register middleware discovery
     *
     * @return void
     */
    protected function registerMiddlewareDiscovery()
    {
        // Get middleware paths from config
        $this->middlewarePaths = $this->config('middleware.paths', [
            $this->app->getBasePath() . '/middlewares',
            $this->app->getBasePath() . '/Middlewares',
            $this->app->getBasePath() . '/middleware',
            $this->app->getBasePath() . '/Middleware',
        ]);

        // Auto-discover middleware in the specified paths
        // This would be implemented to scan directories and register middleware
        // For now, we'll just log that discovery is enabled
        if ($this->app->isDebug()) {
            $this->logger->debug("Middleware discovery enabled. Paths: " . implode(", ", $this->middlewarePaths));
        }
    }
    
    /**
     * Configure middleware from config files
     * 
     * @return void
     */
    protected function configureFromConfig()
    {
        $middleware = $this->container->make(MiddlewareManager::class);
        $config = $this->container->make(Config::class);
        
        // Load middleware configuration
        $middlewareConfig = $config->get('middleware', []);
        
        // Register additional aliases from config
        if (isset($middlewareConfig['aliases']) && is_array($middlewareConfig['aliases'])) {
            $middleware->aliases($middlewareConfig['aliases']);
        }
        
        // Register additional groups from config
        if (isset($middlewareConfig['groups']) && is_array($middlewareConfig['groups'])) {
            foreach ($middlewareConfig['groups'] as $name => $group) {
                if (is_array($group)) {
                    $middleware->group($name, $group);
                }
            }
        }
        
        // Register additional global middleware from config
        if (isset($middlewareConfig['global']) && is_array($middlewareConfig['global'])) {
            $middleware->global($middlewareConfig['global']);
        }
        
        // Register additional route middleware from config
        if (isset($middlewareConfig['routes']) && is_array($middlewareConfig['routes'])) {
            foreach ($middlewareConfig['routes'] as $route => $routeMiddleware) {
                if (is_array($routeMiddleware)) {
                    $middleware->route($route, $routeMiddleware);
                }
            }
        }

        // Log configuration loaded in debug mode
        if ($this->app->isDebug()) {
            $this->logger->debug("Middleware configuration loaded from config files");
        }
    }

    /**
     * Register middleware priorities
     *
     * @return void
     */
    protected function registerMiddlewarePriorities()
    {
        $middleware = $this->container->make(MiddlewareManager::class);
        
        // Get priorities from config
        $priorities = $this->config('middleware.priority', [
            'cors' => 100,      // CORS should run first
            'logging' => 90,     // Logging early to capture all requests
            'rate_limit' => 80,  // Rate limiting before authentication
            'auth' => 70,        // Authentication before CSRF
            'csrf' => 60,        // CSRF protection
            'validation' => 50,  // Validation after authentication and CSRF
        ]);
        
        // Set priorities
        if (method_exists($middleware, 'setPriorities')) {
            $middleware->setPriorities($priorities);
        }

        // Log priorities in debug mode
        if ($this->app->isDebug()) {
            $this->logger->debug("Middleware priorities registered");
        }
    }

    /**
     * Register middleware parameters
     *
     * @return void
     */
    protected function registerMiddlewareParameters()
    {
        $middleware = $this->container->make(MiddlewareManager::class);
        
        // Get parameters from config
        $parameters = $this->config('middleware.parameters', [
            'rate_limit' => [
                'max_requests' => 60,
                'decay_minutes' => 1,
            ],
            'cors' => [
                'allow_origins' => ['*'],
                'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'allow_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            ],
        ]);
        
        // Set parameters
        if (method_exists($middleware, 'setParameters')) {
            $middleware->setParameters($parameters);
        }

        // Log parameters in debug mode
        if ($this->app->isDebug()) {
            $this->logger->debug("Middleware parameters registered");
        }
    }

    /**
     * Register middleware terminable handlers
     *
     * @return void
     */
    protected function registerMiddlewareTerminableHandlers()
    {
        $middleware = $this->container->make(MiddlewareManager::class);
        
        // Get terminable middleware from config
        $terminable = $this->config('middleware.terminable', [
            'logging',
            'rate_limit',
        ]);
        
        // Set terminable middleware
        if (method_exists($middleware, 'setTerminable')) {
            $middleware->setTerminable($terminable);
        }

        // Log terminable handlers in debug mode
        if ($this->app->isDebug()) {
            $this->logger->debug("Middleware terminable handlers registered");
        }
    }
}