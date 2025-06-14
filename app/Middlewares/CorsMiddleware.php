<?php

namespace App\Middlewares;

use App\Core\Middleware\Middleware;
use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * CORS (Cross-Origin Resource Sharing) middleware
 * Handles CORS headers for API requests
 */
class CorsMiddleware extends Middleware
{
    /**
     * Handle the request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next)
    {
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflightRequest($request);
        }
        
        // Process the request
        $response = $next($request);
        
        // Add CORS headers to the response
        return $this->addCorsHeaders($request, $response);
    }
    
    /**
     * Handle preflight OPTIONS request
     * 
     * @param Request $request
     * @return Response
     */
    protected function handlePreflightRequest(Request $request)
    {
        $response = new Response('', 200);
        
        // Add CORS headers
        $this->addCorsHeaders($request, $response);
        
        // Add preflight-specific headers
        $response->setHeader('Access-Control-Max-Age', $this->getMaxAge());
        
        $this->logActivity('CORS preflight request handled', [
            'origin' => $this->getOrigin($request),
            'method' => $request->getHeader('Access-Control-Request-Method'),
            'headers' => $request->getHeader('Access-Control-Request-Headers')
        ]);
        
        return $response;
    }
    
    /**
     * Add CORS headers to response
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    protected function addCorsHeaders(Request $request, Response $response)
    {
        $origin = $this->getOrigin($request);
        
        // Set allowed origin
        if ($this->isOriginAllowed($origin)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
        } else {
            $response->setHeader('Access-Control-Allow-Origin', $this->getDefaultOrigin());
        }
        
        // Set allowed methods
        $response->setHeader('Access-Control-Allow-Methods', $this->getAllowedMethods());
        
        // Set allowed headers
        $response->setHeader('Access-Control-Allow-Headers', $this->getAllowedHeaders());
        
        // Set exposed headers
        $exposedHeaders = $this->getExposedHeaders();
        if (!empty($exposedHeaders)) {
            $response->setHeader('Access-Control-Expose-Headers', $exposedHeaders);
        }
        
        // Set credentials
        if ($this->supportsCredentials()) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        return $response;
    }
    
    /**
     * Get the origin from the request
     * 
     * @param Request $request
     * @return string
     */
    protected function getOrigin(Request $request)
    {
        return $_SERVER['HTTP_ORIGIN'] ?? '';
    }
    
    /**
     * Check if the origin is allowed
     * 
     * @param string $origin
     * @return bool
     */
    protected function isOriginAllowed($origin)
    {
        $allowedOrigins = $this->getAllowedOrigins();
        
        // Allow all origins if * is specified
        if (in_array('*', $allowedOrigins)) {
            return true;
        }
        
        // Check exact match
        if (in_array($origin, $allowedOrigins)) {
            return true;
        }
        
        // Check pattern match
        foreach ($allowedOrigins as $allowedOrigin) {
            if ($this->matchesPattern($origin, $allowedOrigin)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if origin matches a pattern
     * 
     * @param string $origin
     * @param string $pattern
     * @return bool
     */
    protected function matchesPattern($origin, $pattern)
    {
        // Convert wildcard pattern to regex
        $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
        return preg_match($regex, $origin);
    }
    
    /**
     * Get allowed origins from configuration
     * 
     * @return array
     */
    protected function getAllowedOrigins()
    {
        return $this->config('cors.allowed_origins', ['*']);
    }
    
    /**
     * Get default origin when none is allowed
     * 
     * @return string
     */
    protected function getDefaultOrigin()
    {
        return $this->config('cors.default_origin', 'null');
    }
    
    /**
     * Get allowed HTTP methods
     * 
     * @return string
     */
    protected function getAllowedMethods()
    {
        $methods = $this->config('cors.allowed_methods', [
            'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'
        ]);
        
        return is_array($methods) ? implode(', ', $methods) : $methods;
    }
    
    /**
     * Get allowed headers
     * 
     * @return string
     */
    protected function getAllowedHeaders()
    {
        $headers = $this->config('cors.allowed_headers', [
            'Accept',
            'Authorization',
            'Content-Type',
            'X-Requested-With',
            'X-CSRF-Token',
            'X-API-Key'
        ]);
        
        return is_array($headers) ? implode(', ', $headers) : $headers;
    }
    
    /**
     * Get exposed headers
     * 
     * @return string
     */
    protected function getExposedHeaders()
    {
        $headers = $this->config('cors.exposed_headers', []);
        
        return is_array($headers) ? implode(', ', $headers) : $headers;
    }
    
    /**
     * Check if credentials are supported
     * 
     * @return bool
     */
    protected function supportsCredentials()
    {
        return $this->config('cors.supports_credentials', false);
    }
    
    /**
     * Get max age for preflight cache
     * 
     * @return int
     */
    protected function getMaxAge()
    {
        return $this->config('cors.max_age', 86400); // 24 hours
    }
    
    /**
     * Check if the request should skip CORS handling
     * 
     * @param Request $request
     * @return bool
     */
    protected function shouldSkip(Request $request)
    {
        // Skip for same-origin requests
        $origin = $this->getOrigin($request);
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $currentOrigin = $scheme . '://' . $host;
        
        if ($origin === $currentOrigin) {
            return true;
        }
        
        // Skip for non-API routes if configured
        if ($this->config('cors.api_only', true)) {
            $path = $request->getPath();
            if (strpos($path, '/api/') !== 0) {
                return true;
            }
        }
        
        return false;
    }
}