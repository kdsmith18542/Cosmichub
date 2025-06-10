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
     */
    public function addRoute($method, $path, $controller, $action)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
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
            
            if ($route['method'] === $requestMethod && preg_match($pattern, $requestUri, $matches)) {
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
