<?php

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;

/**
 * Rate Limiting Middleware
 * 
 * Protects the application from abuse by limiting the number of requests
 * a client can make within a specified time window.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * Default rate limit configuration.
     *
     * @var array
     */
    protected $config = [
        'max_attempts' => 60,     // Maximum requests per window
        'window_minutes' => 1,    // Time window in minutes
        'storage_prefix' => 'rate_limit:',
        'headers' => true,        // Include rate limit headers in response
    ];
    
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next)
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = $this->getMaxAttempts($request);
        $windowMinutes = $this->getWindowMinutes($request);
        
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildRateLimitResponse($key, $maxAttempts, $windowMinutes);
        }
        
        $this->incrementAttempts($key, $windowMinutes);
        
        $response = $next($request);
        
        if ($this->config['headers']) {
            return $this->addRateLimitHeaders($response, $key, $maxAttempts);
        }
        
        return $response;
    }
    
    /**
     * Resolve the request signature for rate limiting.
     *
     * @param Request $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request)
    {
        $ip = $this->getClientIp($request);
        $route = $request->getPath();
        
        // Include user ID if authenticated for per-user rate limiting
        $userId = $this->getAuthenticatedUserId($request);
        
        if ($userId) {
            return $this->config['storage_prefix'] . "user:{$userId}:{$route}";
        }
        
        return $this->config['storage_prefix'] . "ip:{$ip}:{$route}";
    }
    
    /**
     * Get the client IP address.
     *
     * @param Request $request
     * @return string
     */
    protected function getClientIp(Request $request)
    {
        // Check for IP from various headers (for proxy/load balancer scenarios)
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancers/proxies
            'HTTP_X_FORWARDED',          // Proxies
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxies
            'HTTP_FORWARDED',            // Proxies
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (take the first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Get the authenticated user ID if available.
     *
     * @param Request $request
     * @return string|null
     */
    protected function getAuthenticatedUserId(Request $request)
    {
        $session = app('session');
        return $session->get('user_id');
    }
    
    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param string $key
     * @param int $maxAttempts
     * @return bool
     */
    protected function tooManyAttempts($key, $maxAttempts)
    {
        return $this->getAttempts($key) >= $maxAttempts;
    }
    
    /**
     * Get the number of attempts for the given key.
     *
     * @param string $key
     * @return int
     */
    protected function getAttempts($key)
    {
        $session = app('session');
        return (int) $session->get($key, 0);
    }
    
    /**
     * Increment the attempts for the given key.
     *
     * @param string $key
     * @param int $windowMinutes
     * @return void
     */
    protected function incrementAttempts($key, $windowMinutes)
    {
        $session = app('session');
        $attempts = $this->getAttempts($key) + 1;
        
        // Store with expiration (simulate cache behavior)
        $session->put($key, $attempts);
        $session->put($key . ':expires', time() + ($windowMinutes * 60));
    }
    
    /**
     * Get the maximum number of attempts allowed.
     *
     * @param Request $request
     * @return int
     */
    protected function getMaxAttempts(Request $request)
    {
        // Different limits for different routes
        $path = $request->getPath();
        
        if (strpos($path, '/api/auth/') === 0) {
            return 5; // Stricter limit for auth endpoints
        }
        
        if (strpos($path, '/api/') === 0) {
            return 100; // Higher limit for API endpoints
        }
        
        return $this->config['max_attempts'];
    }
    
    /**
     * Get the time window in minutes.
     *
     * @param Request $request
     * @return int
     */
    protected function getWindowMinutes(Request $request)
    {
        return $this->config['window_minutes'];
    }
    
    /**
     * Build the rate limit exceeded response.
     *
     * @param string $key
     * @param int $maxAttempts
     * @param int $windowMinutes
     * @return Response
     */
    protected function buildRateLimitResponse($key, $maxAttempts, $windowMinutes)
    {
        $retryAfter = $this->getRetryAfter($key, $windowMinutes);
        
        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => time() + $retryAfter,
            'Retry-After' => $retryAfter,
        ];
        
        $content = json_encode([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $retryAfter
        ]);
        
        return new Response($content, 429, array_merge($headers, [
            'Content-Type' => 'application/json'
        ]));
    }
    
    /**
     * Get the number of seconds until the rate limit resets.
     *
     * @param string $key
     * @param int $windowMinutes
     * @return int
     */
    protected function getRetryAfter($key, $windowMinutes)
    {
        $session = app('session');
        $expires = $session->get($key . ':expires', time() + ($windowMinutes * 60));
        
        return max(0, $expires - time());
    }
    
    /**
     * Add rate limit headers to the response.
     *
     * @param Response $response
     * @param string $key
     * @param int $maxAttempts
     * @return Response
     */
    protected function addRateLimitHeaders(Response $response, $key, $maxAttempts)
    {
        $attempts = $this->getAttempts($key);
        $remaining = max(0, $maxAttempts - $attempts);
        
        $response->setHeader('X-RateLimit-Limit', $maxAttempts);
        $response->setHeader('X-RateLimit-Remaining', $remaining);
        
        return $response;
    }
    
    /**
     * Clear the rate limit for the given key.
     *
     * @param string $key
     * @return void
     */
    public function clearRateLimit($key)
    {
        $session = app('session');
        $session->forget($key);
        $session->forget($key . ':expires');
    }
    
    /**
     * Get the current rate limit status for a key.
     *
     * @param string $key
     * @param int $maxAttempts
     * @return array
     */
    public function getRateLimitStatus($key, $maxAttempts)
    {
        $attempts = $this->getAttempts($key);
        $remaining = max(0, $maxAttempts - $attempts);
        
        return [
            'limit' => $maxAttempts,
            'remaining' => $remaining,
            'attempts' => $attempts,
            'exceeded' => $attempts >= $maxAttempts
        ];
    }
}