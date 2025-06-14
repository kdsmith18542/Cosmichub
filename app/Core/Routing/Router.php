<?php

namespace App\Core\Routing;

use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * Router class for handling HTTP routes
 */
class Router
{
    /**
     * @var RouteCollection The route collection
     */
    protected $routes;
    
    /**
     * @var array The route patterns
     */
    protected $patterns = [
        ':any' => '([^/]+)',
        ':num' => '([0-9]+)',
        ':all' => '(.*)',
        ':slug' => '([a-z0-9-]+)',
        ':uuid' => '([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})',
    ];
    
    /**
     * @var array The route parameter names
     */
    protected $paramNames = [];
    
    /**
     * @var string|null The current route group prefix
     */
    protected $groupPrefix = null;
    
    /**
     * @var array The current route group attributes
     */
    protected $groupAttributes = [];
    
    /**
     * Create a new router instance
     * 
     * @param RouteCollection $routes The route collection
     */
    public function __construct(RouteCollection $routes = null)
    {
        $this->routes = $routes ?: new RouteCollection();
    }
    
    /**
     * Register a GET route
     * 
     * @param string $uri The route URI
     * @param mixed $action The route action
     * @return Route
     */
    public function get($uri, $action)
    {
        return $this->addRoute('GET', $uri, $action);
    }
    
    /**
     * Register a POST route
     * 
     * @param string $uri The route URI
     * @param mixed $action The route action
     * @return Route
     */
    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }
    
    /**
     * Register a PUT route
     * 
     * @param string $uri The route URI
     * @param mixed $action The route action
     * @return Route
     */
    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }
    
    /**
     * Register a DELETE route
     * 
     * @param string $uri The route URI
     * @param mixed $action The route action
     * @return Route
     */
    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }
    
    /**
     * Register a PATCH route
     * 
     * @param string $uri The route URI
     * @param mixed $action The route action
     * @return Route
     */
    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }
    
    /**
     * Register a OPTIONS route
     * 
     * @param string $uri The route URI
     * @param mixed $action The route action
     * @return Route
     */
    public function options($uri, $action)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }
    
    /**
     * Register a route for multiple HTTP methods
     * 
     * @param array $methods The HTTP methods
     * @param string $uri The route URI
     * @param mixed $action The route action
     * @return Route
     */
    public function match(array $methods, $uri, $action)
    {
        $route = null;
        
        foreach ($methods as $method) {
            $route = $this->addRoute($method, $uri, $action);
        }
        
        return $route;
    }
    
    /**
     * Register a route for all HTTP methods
     * 
     * @param string $uri The route URI
     * @param mixed $action The route action
     * @return Route
     */
    public function any($uri, $action)
    {
        return $this->match(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], $uri, $action);
    }
    
    /**
     * Create a route group
     * 
     * @param array $attributes The group attributes
     * @param callable $callback The group definition callback
     * @return $this
     */
    public function group(array $attributes, callable $callback)
    {
        // Save the current group attributes and prefix
        $oldGroupAttributes = $this->groupAttributes;
        $oldGroupPrefix = $this->groupPrefix;
        
        // Merge the new group attributes with the current ones
        $this->groupAttributes = array_merge($this->groupAttributes, $attributes);
        
        // Update the group prefix
        if (isset($attributes['prefix'])) {
            $this->groupPrefix = $this->groupPrefix . '/' . trim($attributes['prefix'], '/');
        }
        
        // Execute the callback
        $callback($this);
        
        // Restore the group attributes and prefix
        $this->groupAttributes = $oldGroupAttributes;
        $this->groupPrefix = $oldGroupPrefix;
        
        return $this;
    }
    
    /**
     * Add a route to the router
     * 
     * @param string $method The HTTP method
     * @param string $uri The route URI
     * @param mixed $action The route action
     * @return Route
     */
    protected function addRoute($method, $uri, $action)
    {
        // Normalize the URI
        $uri = $this->normalizeUri($uri);
        
        // Add the group prefix if it exists
        if ($this->groupPrefix) {
            $uri = rtrim($this->groupPrefix, '/') . '/' . ltrim($uri, '/');
        }
        
        // Create the route
        $route = new Route($method, $uri, $action);
        
        // Add the group middleware if it exists
        if (isset($this->groupAttributes['middleware'])) {
            $route->middleware($this->groupAttributes['middleware']);
        }
        
        // Add the route to the collection
        $this->routes->add($route);
        
        return $route;
    }
    
    /**
     * Normalize the route URI
     * 
     * @param string $uri The route URI
     * @return string
     */
    protected function normalizeUri($uri)
    {
        // Remove trailing slashes
        $uri = rtrim($uri, '/');
        
        // Add a leading slash if it doesn't exist
        if ($uri !== '' && $uri[0] !== '/') {
            $uri = '/' . $uri;
        }
        
        // If the URI is empty, use the root path
        if ($uri === '') {
            $uri = '/';
        }
        
        return $uri;
    }
    
    /**
     * Dispatch the request to the router
     * 
     * @param Request $request The request to dispatch
     * @return Response
     */
    public function dispatch(Request $request)
    {
        // Get the request method and path
        $method = $request->getMethod();
        $path = $request->getPath();
        
        // Normalize the path
        $path = $this->normalizeUri($path);
        
        // Get the middleware manager
        $container = \App\Core\Container::getInstance();
        $middlewareManager = $container->bound('middleware') ? $container->get('middleware') : null;
        
        // Handle the request through middleware if available
        if ($middlewareManager) {
            return $middlewareManager->handle($request, function($request) use ($method, $path) {
                return $this->handleRoute($request, $method, $path);
            });
        }
        
        // Fallback to direct route handling if no middleware manager
        return $this->handleRoute($request, $method, $path);
    }
    
    /**
     * Handle the route execution
     * 
     * @param Request $request The request
     * @param string $method The HTTP method
     * @param string $path The request path
     * @return Response
     */
    protected function handleRoute(Request $request, $method, $path)
    {
        // Check if the route exists
        $route = $this->routes->getRouteByUri($path, $method);
        if ($route) {
            return $route->execute($request);
        }
        
        // Check if the route matches a pattern
        $routes = $this->routes->getRoutesByMethod($method);
        foreach ($routes as $uri => $route) {
            // Skip routes that don't contain pattern placeholders
            if (strpos($uri, ':') === false) {
                continue;
            }
            
            // Get the route pattern
            $pattern = $this->getRoutePattern($uri);
            
            // Check if the path matches the pattern
            if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                // Remove the first match (the full match)
                array_shift($matches);
                
                // Get the parameter names
                $paramNames = $this->getRouteParamNames($uri);
                
                // Create the route parameters
                $params = [];
                foreach ($paramNames as $index => $name) {
                    $params[$name] = $matches[$index];
                }
                
                // Set the route parameters
                $route->setParameters($params);
                
                // Execute the route
                return $route->execute($request);
            }
        }
        
        // If we get here, the route doesn't exist
        return $this->handleNotFound($request);
    }
    
    /**
     * Handle a not found route
     * 
     * @param Request $request The request
     * @return Response
     */
    protected function handleNotFound(Request $request)
    {
        return new Response('Not Found', 404);
    }
    
    /**
     * Get the route pattern for a URI
     * 
     * @param string $uri The route URI
     * @return string
     */
    protected function getRoutePattern($uri)
    {
        // Replace the pattern placeholders with regex patterns
        $pattern = strtr($uri, $this->patterns);
        
        return $pattern;
    }
    
    /**
     * Get the route parameter names for a URI
     * 
     * @param string $uri The route URI
     * @return array
     */
    protected function getRouteParamNames($uri)
    {
        // If the URI is already in the cache, return it
        if (isset($this->paramNames[$uri])) {
            return $this->paramNames[$uri];
        }
        
        // Extract the parameter names from the URI
        preg_match_all('/:([a-zA-Z0-9_]+)/', $uri, $matches);
        
        // Store the parameter names in the cache
        $this->paramNames[$uri] = $matches[1];
        
        return $this->paramNames[$uri];
    }
    
    /**
     * Get the route collection
     * 
     * @return RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}