<?php

namespace App\Middlewares;

use App\Core\Middleware\Middleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\ValidationException;
use App\Core\Traits\Validatable;

/**
 * Validation middleware
 * Validates request data before it reaches controllers
 */
class ValidationMiddleware extends Middleware
{
    use Validatable;
    
    /**
     * Validation rules for different routes
     * 
     * @var array
     */
    protected $routeRules = [];
    
    /**
     * Handle the request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws ValidationException
     */
    public function handle(Request $request, callable $next)
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }
        
        $rules = $this->getValidationRules($request);
        
        if (!empty($rules)) {
            $data = $this->getRequestData($request);
            
            try {
                $this->validate($data, $rules);
                
                $this->logActivity('Request validation passed', [
                    'method' => $request->getMethod(),
                    'path' => $request->getPath(),
                    'rules_count' => count($rules)
                ]);
            } catch (ValidationException $e) {
                $this->logActivity('Request validation failed', [
                    'method' => $request->getMethod(),
                    'path' => $request->getPath(),
                    'errors' => $e->getErrors(),
                    'ip' => $this->getClientIp()
                ]);
                
                throw $e;
            }
        }
        
        return $next($request);
    }
    
    /**
     * Get validation rules for the current request
     * 
     * @param Request $request
     * @return array
     */
    protected function getValidationRules(Request $request)
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getPath();
        
        // Load rules from configuration
        $this->loadValidationRules();
        
        // Check for exact path match
        $key = $method . ':' . $path;
        if (isset($this->routeRules[$key])) {
            return $this->routeRules[$key];
        }
        
        // Check for pattern matches
        foreach ($this->routeRules as $pattern => $rules) {
            if ($this->matchesPattern($method, $path, $pattern)) {
                return $rules;
            }
        }
        
        return [];
    }
    
    /**
     * Load validation rules from configuration
     * 
     * @return void
     */
    protected function loadValidationRules()
    {
        if (empty($this->routeRules)) {
            $this->routeRules = $this->config('validation.rules', []);
            
            // Add default rules
            $this->addDefaultRules();
        }
    }
    
    /**
     * Add default validation rules for common endpoints
     * 
     * @return void
     */
    protected function addDefaultRules()
    {
        $defaultRules = [
            // Authentication
            'POST:/auth/login' => [
                'email' => 'required|email',
                'password' => 'required|min:6'
            ],
            'POST:/auth/register' => [
                'name' => 'required|string|min:2|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required'
            ],
            'POST:/auth/forgot-password' => [
                'email' => 'required|email'
            ],
            'POST:/auth/reset-password' => [
                'token' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required'
            ],
            
            // User management
            'PUT:/user/profile' => [
                'name' => 'required|string|min:2|max:100',
                'email' => 'required|email',
                'phone' => 'string|max:20',
                'bio' => 'string|max:500'
            ],
            'POST:/user/change-password' => [
                'current_password' => 'required',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required'
            ],
            
            // Contact forms
            'POST:/contact' => [
                'name' => 'required|string|min:2|max:100',
                'email' => 'required|email',
                'subject' => 'required|string|min:5|max:200',
                'message' => 'required|string|min:10|max:1000'
            ],
            
            // Feedback
            'POST:/feedback' => [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'string|max:1000',
                'category' => 'string|in:bug,feature,general'
            ],
            
            // API endpoints
            'POST:/api/reports' => [
                'type' => 'required|string|in:birth_chart,compatibility,daily_horoscope',
                'birth_date' => 'required|date',
                'birth_time' => 'string',
                'birth_location' => 'required|string'
            ],
            
            // File uploads
            'POST:/upload' => [
                'file' => 'required|file|max:10240', // 10MB
                'type' => 'string|in:image,document'
            ]
        ];
        
        $this->routeRules = array_merge($defaultRules, $this->routeRules);
    }
    
    /**
     * Check if pattern matches the current request
     * 
     * @param string $method
     * @param string $path
     * @param string $pattern
     * @return bool
     */
    protected function matchesPattern($method, $path, $pattern)
    {
        // Split pattern into method and path
        if (strpos($pattern, ':') === false) {
            return false;
        }
        
        list($patternMethod, $patternPath) = explode(':', $pattern, 2);
        
        // Check method match
        if ($patternMethod !== '*' && $patternMethod !== $method) {
            return false;
        }
        
        // Check path match with wildcards
        if (strpos($patternPath, '*') !== false) {
            $regex = '/^' . str_replace('*', '.*', preg_quote($patternPath, '/')) . '$/';
            return preg_match($regex, $path);
        }
        
        // Check path match with parameters
        if (strpos($patternPath, '{') !== false) {
            $regex = preg_replace('/\{[^}]+\}/', '[^/]+', $patternPath);
            $regex = '/^' . str_replace('/', '\/', $regex) . '$/';
            return preg_match($regex, $path);
        }
        
        return $patternPath === $path;
    }
    
    /**
     * Get request data for validation
     * 
     * @param Request $request
     * @return array
     */
    protected function getRequestData(Request $request)
    {
        $data = [];
        
        // Get POST data
        if (!empty($_POST)) {
            $data = array_merge($data, $_POST);
        }
        
        // Get JSON data
        $jsonData = $this->getJsonData($request);
        if (!empty($jsonData)) {
            $data = array_merge($data, $jsonData);
        }
        
        // Get query parameters for GET requests
        if ($request->getMethod() === 'GET' && !empty($_GET)) {
            $data = array_merge($data, $_GET);
        }
        
        // Get file uploads
        if (!empty($_FILES)) {
            $data = array_merge($data, $this->processFileUploads($_FILES));
        }
        
        return $data;
    }
    
    /**
     * Get JSON data from request body
     * 
     * @param Request $request
     * @return array
     */
    protected function getJsonData(Request $request)
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }
        
        return [];
    }
    
    /**
     * Process file uploads for validation
     * 
     * @param array $files
     * @return array
     */
    protected function processFileUploads($files)
    {
        $processed = [];
        
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                // Multiple files
                $processed[$key] = [];
                for ($i = 0; $i < count($file['name']); $i++) {
                    $processed[$key][] = [
                        'name' => $file['name'][$i],
                        'type' => $file['type'][$i],
                        'size' => $file['size'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i]
                    ];
                }
            } else {
                // Single file
                $processed[$key] = $file;
            }
        }
        
        return $processed;
    }
    
    /**
     * Add custom validation rule for a route
     * 
     * @param string $method
     * @param string $path
     * @param array $rules
     * @return void
     */
    public function addRule($method, $path, array $rules)
    {
        $key = strtoupper($method) . ':' . $path;
        $this->routeRules[$key] = $rules;
    }
    
    /**
     * Add multiple validation rules
     * 
     * @param array $rules
     * @return void
     */
    public function addRules(array $rules)
    {
        $this->routeRules = array_merge($this->routeRules, $rules);
    }
    
    /**
     * Check if the request should skip validation
     * 
     * @param Request $request
     * @return bool
     */
    protected function shouldSkip(Request $request)
    {
        // Skip if validation is disabled
        if (!$this->config('validation.enabled', true)) {
            return true;
        }
        
        // Skip for GET requests unless configured otherwise
        if ($request->getMethod() === 'GET' && !$this->config('validation.validate_get', false)) {
            return true;
        }
        
        // Skip for specific paths
        $path = $request->getPath();
        $skipPaths = $this->config('validation.skip_paths', ['/health', '/status']);
        
        foreach ($skipPaths as $skipPath) {
            if (strpos($path, $skipPath) === 0) {
                return true;
            }
        }
        
        // Skip for static assets
        if ($this->isStaticAsset($path)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if the path is a static asset
     * 
     * @param string $path
     * @return bool
     */
    protected function isStaticAsset($path)
    {
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), $staticExtensions);
    }
    
    /**
     * Get validation rules for a specific route
     * 
     * @param string $method
     * @param string $path
     * @return array
     */
    public function getRulesForRoute($method, $path)
    {
        $key = strtoupper($method) . ':' . $path;
        return $this->routeRules[$key] ?? [];
    }
    
    /**
     * Remove validation rules for a route
     * 
     * @param string $method
     * @param string $path
     * @return void
     */
    public function removeRule($method, $path)
    {
        $key = strtoupper($method) . ':' . $path;
        unset($this->routeRules[$key]);
    }
}