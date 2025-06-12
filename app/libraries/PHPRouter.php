<?php
namespace App\Libraries;

/**
 * PHP-Based Router - No .htaccess Required
 * 
 * This router handles URL routing purely in PHP, making it compatible
 * with any hosting provider regardless of .htaccess support.
 * 
 * Supports multiple URL formats:
 * 1. Path Info: /index.php/controller/action
 * 2. Query String: /index.php?route=/controller/action
 * 3. Direct: /index.php (home page)
 */
class PHPRouter
{
    /**
     * @var array Routes collection
     */
    private $routes = [];
    
    /**
     * @var string Current request path
     */
    private $requestPath;
    
    /**
     * @var string Current request method
     */
    private $requestMethod;
    
    /**
     * Constructor - Initialize request parsing
     */
    public function __construct()
    {
        $this->parseRequest();
    }
    
    /**
     * Parse the incoming request to extract path and method
     */
    private function parseRequest()
    {
        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Try different methods to get the route
        $route = $this->extractRoute();
        
        // Clean and normalize the route
        $this->requestPath = $this->normalizeRoute($route);
    }
    
    /**
     * Extract route from various URL formats
     */
    private function extractRoute()
    {
        // Method 1: Check query parameter 'route'
        if (isset($_GET['route'])) {
            return $_GET['route'];
        }
        
        // Method 2: Check PATH_INFO
        if (isset($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        }
        
        // Method 3: Parse from REQUEST_URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        
        // Remove script name from URI
        $route = str_replace($scriptName, '', $requestUri);
        
        // Remove query string
        $route = parse_url($route, PHP_URL_PATH) ?: '/';
        
        // If route starts with /, it might be path info style
        if ($route !== '/' && strpos($requestUri, $scriptName . $route) !== false) {
            return $route;
        }
        
        // Default to home
        return '/';
    }
    
    /**
     * Normalize route path
     */
    private function normalizeRoute($route)
    {
        // Ensure route starts with /
        $route = '/' . ltrim($route, '/');
        
        // Remove trailing slash unless it's root
        if ($route !== '/' && substr($route, -1) === '/') {
            $route = rtrim($route, '/');
        }
        
        return $route;
    }
    
    /**
     * Add a route to the collection
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
     */
    public function get($path, $controller, $action)
    {
        return $this->addRoute('GET', $path, $controller, $action);
    }
    
    /**
     * Add a POST route
     */
    public function post($path, $controller, $action)
    {
        return $this->addRoute('POST', $path, $controller, $action);
    }
    
    /**
     * Add a PUT route
     */
    public function put($path, $controller, $action)
    {
        return $this->addRoute('PUT', $path, $controller, $action);
    }
    
    /**
     * Add a DELETE route
     */
    public function delete($path, $controller, $action)
    {
        return $this->addRoute('DELETE', $path, $controller, $action);
    }
    
    /**
     * Add a PATCH route
     */
    public function patch($path, $controller, $action)
    {
        return $this->addRoute('PATCH', $path, $controller, $action);
    }
    
    /**
     * Add a route that matches any HTTP method
     */
    public function any($path, $controller, $action)
    {
        return $this->addRoute('ANY', $path, $controller, $action);
    }
    
    /**
     * Handle the current request
     */
    public function handleRequest()
    {
        foreach ($this->routes as $route) {
            $pattern = $this->convertToRegex($route['path']);
            
            // Check if the route matches the request method
            $methodMatches = ($route['method'] === $this->requestMethod || $route['method'] === 'ANY');
            
            if ($methodMatches && preg_match($pattern, $this->requestPath, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->callAction($route['controller'], $route['action'], $params);
                return;
            }
        }
        
        // No route found - show 404
        $this->show404();
    }
    
    /**
     * Convert route path to regex pattern
     */
    private function convertToRegex($path)
    {
        // Escape special regex characters except for our placeholders
        $pattern = preg_quote($path, '#');
        
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\\\{([^\}]+)\\\}/', '(?P<$1>[^/]+)', $pattern);
        
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Call controller action with parameters
     */
    private function callAction($controller, $action, $params = [])
    {
        $controllerClass = "App\\Controllers\\$controller";
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller class $controllerClass not found");
        }
        
        $controllerInstance = new $controllerClass();
        
        if (!method_exists($controllerInstance, $action)) {
            throw new \Exception("Method $action not found in controller $controllerClass");
        }
        
        // Call the action with parameters
        call_user_func_array([$controllerInstance, $action], array_values($params));
    }
    
    /**
     * Show 404 error page
     */
    private function show404()
    {
        http_response_code(404);
        echo '<!DOCTYPE html>';
        echo '<html><head>';
        echo '<title>Page Not Found</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '</head>';
        echo '<body class="bg-light">';
        echo '<div class="container mt-5">';
        echo '<div class="row justify-content-center">';
        echo '<div class="col-md-6 text-center">';
        echo '<h1 class="mb-4">404 - Page Not Found</h1>';
        echo '<p class="mb-4">The requested page could not be found.</p>';
        echo '<a href="/" class="btn btn-primary">Return to Home</a>';
        echo '</div></div></div>';
        echo '</body></html>';}
    }
    
    /**
     * Generate URL for a given path
     */
    public function generateUrl($path, $params = [])
    {
        $baseUrl = $this->getBaseUrl();
        
        // If path is root, just return base URL
        if ($path === '/') {
            return $baseUrl;
        }
        
        // Replace parameters in path
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }
        
        // Use PATH_INFO style if supported, otherwise query string
        if ($this->supportsPathInfo()) {
            return $baseUrl . $path;
        } else {
            return $baseUrl . '?route=' . urlencode($path);
        }
    }
    
    /**
     * Get base URL for the application
     */
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        
        return $protocol . '://' . $host . $script;
    }
    
    /**
     * Check if server supports PATH_INFO
     */
    private function supportsPathInfo()
    {
        // Most servers support PATH_INFO, but some shared hosting might not
        // This is a simple check - you can make it more sophisticated
        return isset($_SERVER['PATH_INFO']) || 
               (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_NAME']));
    }
    
    /**
     * Get current request path (for debugging)
     */
    public function getCurrentPath()
    {
        return $this->requestPath;
    }
    
    /**
     * Get current request method (for debugging)
     */
    public function getCurrentMethod()
    {
        return $this->requestMethod;
    }
}