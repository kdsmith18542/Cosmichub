<?php

namespace App\Core\Routing;

/**
 * RouteCollection class for managing routes
 */
class RouteCollection
{
    /**
     * @var array The routes by method
     */
    protected $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => [],
        'OPTIONS' => [],
    ];
    
    /**
     * @var array The named routes
     */
    protected $namedRoutes = [];
    
    /**
     * Add a route to the collection
     * 
     * @param Route $route The route to add
     * @return $this
     */
    public function add(Route $route)
    {
        $method = $route->getMethod();
        $uri = $route->getUri();
        
        $this->routes[$method][$uri] = $route;
        
        // If the route has a name, add it to the named routes
        if ($name = $route->getName()) {
            $this->namedRoutes[$name] = $route;
        }
        
        return $this;
    }
    
    /**
     * Get all routes for a method
     * 
     * @param string $method The HTTP method
     * @return array
     */
    public function getRoutesByMethod($method)
    {
        return $this->routes[$method] ?? [];
    }
    
    /**
     * Get all routes
     * 
     * @return array
     */
    public function getAllRoutes()
    {
        return $this->routes;
    }
    
    /**
     * Get a route by name
     * 
     * @param string $name The route name
     * @return Route|null
     */
    public function getRouteByName($name)
    {
        return $this->namedRoutes[$name] ?? null;
    }
    
    /**
     * Check if a named route exists
     * 
     * @param string $name The route name
     * @return bool
     */
    public function hasNamedRoute($name)
    {
        return isset($this->namedRoutes[$name]);
    }
    
    /**
     * Get a route by URI and method
     * 
     * @param string $uri The route URI
     * @param string $method The HTTP method
     * @return Route|null
     */
    public function getRouteByUri($uri, $method)
    {
        return $this->routes[$method][$uri] ?? null;
    }
    
    /**
     * Check if a route exists for a URI and method
     * 
     * @param string $uri The route URI
     * @param string $method The HTTP method
     * @return bool
     */
    public function hasRoute($uri, $method)
    {
        return isset($this->routes[$method][$uri]);
    }
    
    /**
     * Generate a URL for a named route
     * 
     * @param string $name The route name
     * @param array $parameters The route parameters
     * @return string|null
     */
    public function url($name, array $parameters = [])
    {
        // Get the route by name
        $route = $this->getRouteByName($name);
        
        // If the route doesn't exist, return null
        if (!$route) {
            return null;
        }
        
        // Get the route URI
        $uri = $route->getUri();
        
        // Replace the parameters in the URI
        foreach ($parameters as $key => $value) {
            $uri = str_replace(':' . $key, $value, $uri);
        }
        
        return $uri;
    }
}