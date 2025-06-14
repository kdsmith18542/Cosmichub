<?php

namespace App\Core\Middleware;

use App\Core\Application;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Traits\Loggable;

/**
 * Abstract base middleware class
 */
abstract class Middleware
{
    use Loggable;
    
    /**
     * @var Application The application instance
     */
    protected $app;
    
    /**
     * Create a new middleware instance
     * 
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    /**
     * Handle the request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    abstract public function handle(Request $request, callable $next);
    
    /**
     * Get the application instance
     * 
     * @return Application
     */
    protected function getApp()
    {
        return $this->app;
    }
    
    /**
     * Get the base path for logging
     * 
     * @return string
     */
    protected function getBasePath()
    {
        return $this->app->getBasePath();
    }
    
    /**
     * Check if the request should be skipped by this middleware
     * 
     * @param Request $request
     * @return bool
     */
    protected function shouldSkip(Request $request)
    {
        return false;
    }
    
    /**
     * Get the current user from the request
     * 
     * @param Request $request
     * @return mixed|null
     */
    protected function getUser(Request $request)
    {
        return $request->user ?? null;
    }
    
    /**
     * Check if the user is authenticated
     * 
     * @param Request $request
     * @return bool
     */
    protected function isAuthenticated(Request $request)
    {
        return $this->getUser($request) !== null;
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function config($key, $default = null)
    {
        $config = $this->app->make('config');
        return $config->get($key, $default);
    }
    
    /**
     * Create an error response
     * 
     * @param string $message
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    protected function errorResponse($message, $statusCode = 500, array $headers = [])
    {
        $response = new Response($message, $statusCode, $headers);
        
        // Set content type based on request
        if ($this->isJsonRequest()) {
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'error' => true,
                'message' => $message,
                'status' => $statusCode
            ]));
        }
        
        return $response;
    }
    
    /**
     * Create a JSON response
     * 
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    protected function jsonResponse(array $data, $statusCode = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        return new Response(json_encode($data), $statusCode, $headers);
    }
    
    /**
     * Create a redirect response
     * 
     * @param string $url
     * @param int $statusCode
     * @return Response
     */
    protected function redirectResponse($url, $statusCode = 302)
    {
        return new Response('', $statusCode, ['Location' => $url]);
    }
    
    /**
     * Check if the request expects a JSON response
     * 
     * @return bool
     */
    protected function isJsonRequest()
    {
        // Check Accept header
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) {
            return true;
        }
        
        // Check Content-Type header
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            return true;
        }
        
        // Check for AJAX requests
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        if (strtolower($requestedWith) === 'xmlhttprequest') {
            return true;
        }
        
        // Check URL path for API endpoints
        $path = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($path, '/api/') === 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get the client IP address
     * 
     * @return string
     */
    protected function getClientIp()
    {
        // Check for IP from shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        // Check for IP from remote address
        elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Get the user agent string
     * 
     * @return string
     */
    protected function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    /**
     * Check if the request is from a bot/crawler
     * 
     * @return bool
     */
    protected function isBot()
    {
        $userAgent = strtolower($this->getUserAgent());
        $bots = ['bot', 'crawler', 'spider', 'scraper', 'curl', 'wget'];
        
        foreach ($bots as $bot) {
            if (strpos($userAgent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log middleware activity
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logActivity($message, array $context = [])
    {
        $context = array_merge($context, [
            'middleware' => static::class,
            'ip' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent(),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? ''
        ]);
        
        $this->logInfo($message, $context);
    }
}