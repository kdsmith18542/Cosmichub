<?php
namespace App\Libraries;

/**
 * Simple router for the application
 */
class Router
{
    /**
     * @var array Routes collection
     */
    private $routes = [];
    
    /**
     * Add a route to the collection
     * 
     * @param string $method HTTP method
     * @param string $path URL path
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return self Returns $this for method chaining
     */
    public function addRoute($method, $path, $controller, $action)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
        
        return $this;
    }
    
    /**
     * Add a GET route
     * 
     * @param string $path URL path
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return self
     */
    public function get($path, $controller, $action)
    {
        return $this->addRoute('GET', $path, $controller, $action);
    }
    
    /**
     * Add a POST route
     * 
     * @param string $path URL path
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return self
     */
    public function post($path, $controller, $action)
    {
        return $this->addRoute('POST', $path, $controller, $action);
    }
    
    /**
     * Add a PUT route
     * 
     * @param string $path URL path
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return self
     */
    public function put($path, $controller, $action)
    {
        return $this->addRoute('PUT', $path, $controller, $action);
    }
    
    /**
     * Add a DELETE route
     * 
     * @param string $path URL path
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return self
     */
    public function delete($path, $controller, $action)
    {
        return $this->addRoute('DELETE', $path, $controller, $action);
    }
    
    /**
     * Add a PATCH route
     * 
     * @param string $path URL path
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return self
     */
    public function patch($path, $controller, $action)
    {
        return $this->addRoute('PATCH', $path, $controller, $action);
    }
    
    /**
     * Add a route that matches any HTTP method
     * 
     * @param string $path URL path
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return self
     */
    public function any($path, $controller, $action)
    {
        return $this->addRoute('ANY', $path, $controller, $action);
    }
    
    /**
     * Add multiple routes at once using an array
     * 
     * @param array $routes Array of routes in format [['method', 'path', 'controller', 'action'], ...]
     * @return self
     */
    public function addRoutes(array $routes)
    {
        foreach ($routes as $route) {
            $this->addRoute(...$route);
        }
        return $this;
    }
    
    /**
     * Dispatch the request to the appropriate controller
     */
    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            $pattern = $this->convertToRegex($route['path']);
            
            // Check if the route matches the request method or if it's set to 'ANY'
            $methodMatches = ($route['method'] === $requestMethod || $route['method'] === 'ANY');
            
            if ($methodMatches && preg_match($pattern, $requestUri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->callAction($route['controller'], $route['action'], $params);
                return;
            }
        }
        
        // No route found
        header('HTTP/1.0 404 Not Found');
        echo '404 Not Found';
    }
    
    /**
     * Convert route path to regex pattern
     */
    private function convertToRegex($path)
    {
        return '#^' . preg_replace('/\{([^\/]+)\}/', '(?P<$1>[^\/]+)', $path) . '$#';
    }
    
    /**
     * Call controller action with parameters
     */
    private function callAction($controller, $action, $params = [])
    {
        $controller = "App\\Controllers\\$controller";
        
        if (!class_exists($controller)) {
            throw new \Exception("Controller class $controller not found");
        }
        
        $controllerInstance = new $controller();
        
        if (!method_exists($controllerInstance, $action)) {
            throw new \Exception("Method $action not found in controller $controller");
        }
        
        call_user_func_array([$controllerInstance, $action], $params);
    }
}
