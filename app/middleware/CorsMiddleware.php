<?php

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;

/**
 * CORS Middleware
 * 
 * Handles Cross-Origin Resource Sharing (CORS) requests.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Handle an incoming request.
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
        
        $response = $next($request);
        
        return $this->addCorsHeaders($request, $response);
    }
    
    /**
     * Handle preflight OPTIONS request.
     *
     * @param Request $request
     * @return Response
     */
    protected function handlePreflightRequest(Request $request)
    {
        $response = new Response('', 200);
        
        return $this->addCorsHeaders($request, $response);
    }
    
    /**
     * Add CORS headers to the response.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    protected function addCorsHeaders(Request $request, Response $response)
    {
        $origin = $request->getHeader('Origin');
        
        // Allow specific origins or all origins
        $allowedOrigins = $this->getAllowedOrigins();
        
        if ($this->isOriginAllowed($origin, $allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin ?: '*');
        }
        
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
        $response->setHeader('Access-Control-Max-Age', '86400'); // 24 hours
        
        return $response;
    }
    
    /**
     * Get allowed origins from configuration.
     *
     * @return array
     */
    protected function getAllowedOrigins()
    {
        return [
            'http://localhost:3000',
            'http://localhost:8080',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8080',
            // Add your frontend domains here
        ];
    }
    
    /**
     * Check if the origin is allowed.
     *
     * @param string|null $origin
     * @param array $allowedOrigins
     * @return bool
     */
    protected function isOriginAllowed($origin, array $allowedOrigins)
    {
        if (!$origin) {
            return true;
        }
        
        return in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins);
    }
}