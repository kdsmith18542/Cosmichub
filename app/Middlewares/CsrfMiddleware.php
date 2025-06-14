<?php

namespace App\Middlewares;

use App\Core\Middleware\Middleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\ForbiddenException;

/**
 * CSRF (Cross-Site Request Forgery) protection middleware
 * Validates CSRF tokens for state-changing requests
 */
class CsrfMiddleware extends Middleware
{
    /**
     * Handle the request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws ForbiddenException
     */
    public function handle(Request $request, callable $next)
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }
        
        // Validate CSRF token for state-changing requests
        if ($this->isStateChangingRequest($request)) {
            if (!$this->validateCsrfToken($request)) {
                $this->logActivity('CSRF token validation failed', [
                    'method' => $request->getMethod(),
                    'path' => $request->getPath(),
                    'ip' => $this->getClientIp(),
                    'user_agent' => $this->getUserAgent(),
                    'referer' => $_SERVER['HTTP_REFERER'] ?? null
                ]);
                
                throw new ForbiddenException('CSRF token mismatch. Please refresh the page and try again.');
            }
        }
        
        // Process the request
        $response = $next($request);
        
        // Add CSRF token to response headers for AJAX requests
        if ($this->expectsJson($request)) {
            $response->setHeader('X-CSRF-Token', $this->generateCsrfToken());
        }
        
        return $response;
    }
    
    /**
     * Check if the request is a state-changing request
     * 
     * @param Request $request
     * @return bool
     */
    protected function isStateChangingRequest(Request $request)
    {
        $method = strtoupper($request->getMethod());
        return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);
    }
    
    /**
     * Validate CSRF token from request
     * 
     * @param Request $request
     * @return bool
     */
    protected function validateCsrfToken(Request $request)
    {
        $token = $this->getTokenFromRequest($request);
        
        if (!$token) {
            return false;
        }
        
        return $this->isValidToken($token);
    }
    
    /**
     * Get CSRF token from request
     * 
     * @param Request $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request)
    {
        // Check POST data
        if (isset($_POST['_token'])) {
            return $_POST['_token'];
        }
        
        // Check headers
        $headers = [
            'X-CSRF-Token',
            'X-XSRF-Token',
            'X-Requested-With-Token'
        ];
        
        foreach ($headers as $header) {
            $value = $request->getHeader($header);
            if ($value) {
                return $value;
            }
        }
        
        // Check query parameters (for GET requests with token)
        if (isset($_GET['_token'])) {
            return $_GET['_token'];
        }
        
        return null;
    }
    
    /**
     * Check if token is valid
     * 
     * @param string $token
     * @return bool
     */
    protected function isValidToken($token)
    {
        $sessionToken = $this->getSessionToken();
        
        if (!$sessionToken) {
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Get CSRF token from session
     * 
     * @return string|null
     */
    protected function getSessionToken()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        return $_SESSION['_csrf_token'] ?? null;
    }
    
    /**
     * Generate a new CSRF token
     * 
     * @return string
     */
    public function generateCsrfToken()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Generate token if it doesn't exist or is expired
        if (!isset($_SESSION['_csrf_token']) || $this->isTokenExpired()) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['_csrf_token_time'] = time();
        }
        
        return $_SESSION['_csrf_token'];
    }
    
    /**
     * Check if the current token is expired
     * 
     * @return bool
     */
    protected function isTokenExpired()
    {
        if (!isset($_SESSION['_csrf_token_time'])) {
            return true;
        }
        
        $tokenAge = time() - $_SESSION['_csrf_token_time'];
        $maxAge = $this->config('csrf.token_lifetime', 3600); // 1 hour default
        
        return $tokenAge > $maxAge;
    }
    
    /**
     * Get CSRF token for use in forms
     * 
     * @return string
     */
    public function getToken()
    {
        return $this->generateCsrfToken();
    }
    
    /**
     * Generate CSRF token input field for forms
     * 
     * @return string
     */
    public function getTokenField()
    {
        $token = $this->generateCsrfToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Generate CSRF meta tag for AJAX requests
     * 
     * @return string
     */
    public function getMetaTag()
    {
        $token = $this->generateCsrfToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Check if the request should skip CSRF validation
     * 
     * @param Request $request
     * @return bool
     */
    protected function shouldSkip(Request $request)
    {
        // Skip if CSRF protection is disabled
        if (!$this->config('csrf.enabled', true)) {
            return true;
        }
        
        // Skip for read-only requests
        if (!$this->isStateChangingRequest($request)) {
            return true;
        }
        
        // Skip for API routes with token authentication
        if ($this->isApiRequest($request) && $this->hasValidApiToken($request)) {
            return true;
        }
        
        // Skip for whitelisted routes
        $path = $request->getPath();
        $skipRoutes = $this->config('csrf.skip_routes', []);
        
        foreach ($skipRoutes as $route) {
            if ($this->matchesRoute($path, $route)) {
                return true;
            }
        }
        
        // Skip for webhook endpoints
        if ($this->isWebhookRequest($request)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if this is an API request
     * 
     * @param Request $request
     * @return bool
     */
    protected function isApiRequest(Request $request)
    {
        $path = $request->getPath();
        return strpos($path, '/api/') === 0;
    }
    
    /**
     * Check if request has valid API token
     * 
     * @param Request $request
     * @return bool
     */
    protected function hasValidApiToken(Request $request)
    {
        $token = $request->getHeader('Authorization');
        
        if (!$token) {
            return false;
        }
        
        // Basic validation - you might want to implement more sophisticated token validation
        return strpos($token, 'Bearer ') === 0 && strlen($token) > 20;
    }
    
    /**
     * Check if this is a webhook request
     * 
     * @param Request $request
     * @return bool
     */
    protected function isWebhookRequest(Request $request)
    {
        $path = $request->getPath();
        $webhookPaths = $this->config('csrf.webhook_paths', ['/webhook', '/webhooks']);
        
        foreach ($webhookPaths as $webhookPath) {
            if (strpos($path, $webhookPath) === 0) {
                return true;
            }
        }
        
        return false;
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
        
        return false;
    }
    
    /**
     * Regenerate CSRF token
     * 
     * @return string
     */
    public function regenerateToken()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        unset($_SESSION['_csrf_token'], $_SESSION['_csrf_token_time']);
        
        return $this->generateCsrfToken();
    }
    
    /**
     * Clear CSRF token from session
     * 
     * @return void
     */
    public function clearToken()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        unset($_SESSION['_csrf_token'], $_SESSION['_csrf_token_time']);
    }
}