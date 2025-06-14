<?php

namespace App\Core\Routing;

use App\Core\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * Route class for representing a single route
 */
class Route
{
    /**
     * @var string The HTTP method
     */
    protected $method;
    
    /**
     * @var string The route URI
     */
    protected $uri;
    
    /**
     * @var mixed The route action
     */
    protected $action;
    
    /**
     * @var array The route middleware
     */
    protected $middleware = [];
    
    /**
     * @var string|null The route name
     */
    protected $name = null;
    
    /**
     * @var array The route parameters
     */
    protected $parameters = [];
    
    /**
     * Create a new route instance
     * 
     * @param string $method The HTTP method
     * @param string $uri The route URI
     * @param mixed $action The route action
     */
    public function __construct($method, $uri, $action)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }
    
    /**
     * Set the route name
     * 
     * @param string $name The route name
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;
        
        return $this;
    }
    
    /**
     * Add middleware to the route
     * 
     * @param string|array $middleware The middleware
     * @return $this
     */
    public function middleware($middleware)
    {
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }
        
        $this->middleware = array_merge($this->middleware, $middleware);
        
        return $this;
    }
    
    /**
     * Get the route HTTP method
     * 
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
    
    /**
     * Get the route URI
     * 
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }
    
    /**
     * Get the route action
     * 
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Get the route middleware
     * 
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }
    
    /**
     * Get the route name
     * 
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set the route parameters
     * 
     * @param array $parameters The route parameters
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        
        return $this;
    }
    
    /**
     * Get the route parameters
     * 
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
    
    /**
     * Get a route parameter
     * 
     * @param string $name The parameter name
     * @param mixed $default The default value
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        return $this->parameters[$name] ?? $default;
    }
    
    /**
     * Check if the route has a parameter
     * 
     * @param string $name The parameter name
     * @return bool
     */
    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }
    
    /**
     * Execute the route
     * 
     * @param Request $request The request
     * @return Response
     */
    public function execute(Request $request)
    {
        // Set the route parameters on the request
        $request->setRouteParams($this->parameters);
        
        // Get the container instance
        $container = Container::getInstance();
        
        // Handle route-specific middleware through middleware manager if available
        $middlewareManager = $container->bound('middleware') ? $container->get('middleware') : null;
        
        if ($middlewareManager && !empty($this->middleware)) {
            // Create a pipeline for route-specific middleware
            return $middlewareManager->handleRouteMiddleware($request, $this->middleware, function($request) use ($container) {
                return $this->executeAction($request, $container);
            });
        }
        
        // Execute the action directly if no middleware manager or no route middleware
        return $this->executeAction($request, $container);
    }
    
    /**
     * Execute the route action
     * 
     * @param Request $request The request
     * @param Container $container The container instance
     * @return Response
     */
    protected function executeAction(Request $request, Container $container)
    {
        // If the action is a closure, execute it
        if ($this->action instanceof \Closure) {
            $result = $this->action($request);
            
            // If the result is not a response, convert it to one
            if (!$result instanceof Response) {
                $result = new Response($result);
            }
            
            return $result;
        }
        
        // If the action is a string, it's a controller@method
        if (is_string($this->action)) {
            // Check if the action is in the format 'Controller@method'
            if (strpos($this->action, '@') !== false) {
                list($controller, $method) = explode('@', $this->action, 2);
            } else {
                $controller = $this->action;
                $method = 'index';
            }
            
            // If the controller doesn't have a namespace, add the default namespace
            if (strpos($controller, '\\') === false) {
                $controller = 'App\\Controllers\\' . $controller;
            }
            
            // Create the controller instance
            $instance = $container->make($controller);
            
            // Call the controller method
            $result = $container->call([$instance, $method], ['request' => $request]);
            
            // If the result is not a response, convert it to one
            if (!$result instanceof Response) {
                $result = new Response($result);
            }
            
            return $result;
        }
        
        // If the action is an array, it's a controller and method
        if (is_array($this->action) && isset($this->action[0]) && isset($this->action[1])) {
            $controller = $this->action[0];
            $method = $this->action[1];
            
            // If the controller doesn't have a namespace, add the default namespace
            if (is_string($controller) && strpos($controller, '\\') === false) {
                $controller = 'App\\Controllers\\' . $controller;
            }
            
            // If the controller is a string, create an instance
            if (is_string($controller)) {
                $controller = $container->make($controller);
            }
            
            // Call the controller method
            $result = $container->call([$controller, $method], ['request' => $request]);
            
            // If the result is not a response, convert it to one
            if (!$result instanceof Response) {
                $result = new Response($result);
            }
            
            return $result;
        }
        
        // If we get here, the action is invalid
        throw new \RuntimeException('Invalid route action');
    }
}