<?php

namespace App\Middlewares;

use App\Core\Middleware\Middleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\ForbiddenException;

/**
 * Rate limiting middleware
 * Prevents abuse by limiting the number of requests per time window
 */
class RateLimitMiddleware extends Middleware
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
        
        $identifier = $this->getIdentifier($request);
        $limit = $this->getLimit($request);
        $window = $this->getWindow($request);
        
        // Check rate limit
        $attempts = $this->getAttempts($identifier, $window);
        
        if ($attempts >= $limit) {
            $this->logActivity('Rate limit exceeded', [
                'identifier' => $identifier,
                'attempts' => $attempts,
                'limit' => $limit,
                'window' => $window,
                'ip' => $this->getClientIp(),
                'user_agent' => $this->getUserAgent()
            ]);
            
            throw ForbiddenException::rateLimitExceeded(
                "Rate limit exceeded. Maximum {$limit} requests per {$window} seconds."
            );
        }
        
        // Increment attempts
        $this->incrementAttempts($identifier, $window);
        
        // Process the request
        $response = $next($request);
        
        // Add rate limit headers
        $this->addRateLimitHeaders($response, $limit, $attempts + 1, $window, $identifier);
        
        return $response;
    }
    
    /**
     * Get the rate limit identifier for the request
     * 
     * @param Request $request
     * @return string
     */
    protected function getIdentifier(Request $request)
    {
        $user = $this->getUser();
        
        if ($user) {
            // Use user ID for authenticated requests
            return 'user:' . $user['id'];
        }
        
        // Use IP address for anonymous requests
        return 'ip:' . $this->getClientIp();
    }
    
    /**
     * Get the rate limit for the request
     * 
     * @param Request $request
     * @return int
     */
    protected function getLimit(Request $request)
    {
        $user = $this->getUser();
        
        // Different limits for different user types
        if ($user) {
            $userType = $user['type'] ?? 'user';
            return $this->config("rate_limit.limits.{$userType}", $this->config('rate_limit.limits.authenticated', 1000));
        }
        
        return $this->config('rate_limit.limits.anonymous', 100);
    }
    
    /**
     * Get the time window in seconds
     * 
     * @param Request $request
     * @return int
     */
    protected function getWindow(Request $request)
    {
        return $this->config('rate_limit.window', 3600); // 1 hour
    }
    
    /**
     * Get the number of attempts for an identifier
     * 
     * @param string $identifier
     * @param int $window
     * @return int
     */
    protected function getAttempts($identifier, $window)
    {
        $cacheKey = $this->getCacheKey($identifier);
        $cacheFile = $this->getCacheFile($cacheKey);
        
        if (!file_exists($cacheFile)) {
            return 0;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if (!$data || !isset($data['expires']) || $data['expires'] < time()) {
            // Cache expired
            @unlink($cacheFile);
            return 0;
        }
        
        return $data['attempts'] ?? 0;
    }
    
    /**
     * Increment attempts for an identifier
     * 
     * @param string $identifier
     * @param int $window
     * @return void
     */
    protected function incrementAttempts($identifier, $window)
    {
        $cacheKey = $this->getCacheKey($identifier);
        $cacheFile = $this->getCacheFile($cacheKey);
        
        $attempts = $this->getAttempts($identifier, $window) + 1;
        $expires = time() + $window;
        
        $data = [
            'attempts' => $attempts,
            'expires' => $expires,
            'identifier' => $identifier,
            'created_at' => time()
        ];
        
        // Ensure cache directory exists
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($cacheFile, json_encode($data));
    }
    
    /**
     * Get cache key for identifier
     * 
     * @param string $identifier
     * @return string
     */
    protected function getCacheKey($identifier)
    {
        return 'rate_limit:' . md5($identifier);
    }
    
    /**
     * Get cache file path
     * 
     * @param string $cacheKey
     * @return string
     */
    protected function getCacheFile($cacheKey)
    {
        $cacheDir = $this->getBasePath() . '/storage/cache/rate_limit';
        return $cacheDir . '/' . $cacheKey . '.json';
    }
    
    /**
     * Add rate limit headers to response
     * 
     * @param Response $response
     * @param int $limit
     * @param int $used
     * @param int $window
     * @param string $identifier
     * @return void
     */
    protected function addRateLimitHeaders(Response $response, $limit, $used, $window, $identifier)
    {
        $remaining = max(0, $limit - $used);
        $resetTime = time() + $window;
        
        $response->setHeader('X-RateLimit-Limit', (string)$limit);
        $response->setHeader('X-RateLimit-Remaining', (string)$remaining);
        $response->setHeader('X-RateLimit-Reset', (string)$resetTime);
        $response->setHeader('X-RateLimit-Used', (string)$used);
        
        // Add retry-after header if limit exceeded
        if ($remaining === 0) {
            $response->setHeader('Retry-After', (string)$window);
        }
    }
    
    /**
     * Check if the request should skip rate limiting
     * 
     * @param Request $request
     * @return bool
     */
    protected function shouldSkip(Request $request)
    {
        // Skip if rate limiting is disabled
        if (!$this->config('rate_limit.enabled', true)) {
            return true;
        }
        
        // Skip for whitelisted IPs
        $clientIp = $this->getClientIp();
        $whitelist = $this->config('rate_limit.whitelist', []);
        
        if (in_array($clientIp, $whitelist)) {
            return true;
        }
        
        // Skip for internal requests
        if ($this->isInternalRequest($request)) {
            return true;
        }
        
        // Skip for specific routes
        $path = $request->getPath();
        $skipRoutes = $this->config('rate_limit.skip_routes', ['/health', '/status']);
        
        foreach ($skipRoutes as $route) {
            if (strpos($path, $route) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if this is an internal request
     * 
     * @param Request $request
     * @return bool
     */
    protected function isInternalRequest(Request $request)
    {
        $clientIp = $this->getClientIp();
        
        // Check for localhost
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return true;
        }
        
        // Check for private IP ranges
        if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Clean up expired cache files
     * 
     * @return void
     */
    public function cleanupExpiredCache()
    {
        $cacheDir = $this->getBasePath() . '/storage/cache/rate_limit';
        
        if (!is_dir($cacheDir)) {
            return;
        }
        
        $files = glob($cacheDir . '/*.json');
        $now = time();
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if (!$data || !isset($data['expires']) || $data['expires'] < $now) {
                @unlink($file);
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            $this->logActivity('Rate limit cache cleanup', [
                'files_cleaned' => $cleaned,
                'cache_dir' => $cacheDir
            ]);
        }
    }
}