<?php

namespace App\Middlewares;

use App\Core\Middleware\Middleware;
use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * Logging middleware
 * Logs HTTP requests and responses for monitoring and debugging
 */
class LoggingMiddleware extends Middleware
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
        if ($this->shouldSkip($request)) {
            return $next($request);
        }
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Log request
        $this->logRequest($request);
        
        // Process the request
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        // Log response
        $this->logResponse($request, $response, $startTime, $endTime, $startMemory, $endMemory);
        
        return $response;
    }
    
    /**
     * Log the incoming request
     * 
     * @param Request $request
     * @return void
     */
    protected function logRequest(Request $request)
    {
        $data = [
            'method' => $request->getMethod(),
            'url' => $this->getFullUrl($request),
            'path' => $request->getPath(),
            'query_params' => $_GET,
            'headers' => $this->getFilteredHeaders(),
            'ip' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent(),
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $this->getUserId(),
            'session_id' => session_id() ?: null
        ];
        
        // Add request body for POST/PUT/PATCH requests (filtered)
        if (in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH'])) {
            $data['body'] = $this->getFilteredRequestBody();
        }
        
        $this->logActivity('HTTP Request', $data);
    }
    
    /**
     * Log the response
     * 
     * @param Request $request
     * @param Response $response
     * @param float $startTime
     * @param float $endTime
     * @param int $startMemory
     * @param int $endMemory
     * @return void
     */
    protected function logResponse(Request $request, Response $response, $startTime, $endTime, $startMemory, $endMemory)
    {
        $duration = round(($endTime - $startTime) * 1000, 2); // milliseconds
        $memoryUsed = $endMemory - $startMemory;
        $statusCode = $response->getStatusCode();
        
        $data = [
            'method' => $request->getMethod(),
            'path' => $request->getPath(),
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'response_size' => strlen($response->getContent()),
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $this->getUserId(),
            'ip' => $this->getClientIp()
        ];
        
        // Add response headers if configured
        if ($this->config('logging.include_response_headers', false)) {
            $data['response_headers'] = $response->getHeaders();
        }
        
        // Add response body for errors or if configured
        if ($statusCode >= 400 || $this->config('logging.include_response_body', false)) {
            $data['response_body'] = $this->getFilteredResponseBody($response);
        }
        
        // Determine log level based on status code
        $logLevel = $this->getLogLevel($statusCode);
        
        $this->log($logLevel, 'HTTP Response', $data);
        
        // Log slow requests
        if ($duration > $this->config('logging.slow_request_threshold', 1000)) {
            $this->logWarning('Slow HTTP Request', array_merge($data, [
                'threshold_ms' => $this->config('logging.slow_request_threshold', 1000)
            ]));
        }
        
        // Log high memory usage
        $memoryThreshold = $this->config('logging.high_memory_threshold', 50 * 1024 * 1024); // 50MB
        if ($memoryUsed > $memoryThreshold) {
            $this->logWarning('High Memory Usage', array_merge($data, [
                'threshold_mb' => round($memoryThreshold / 1024 / 1024, 2)
            ]));
        }
    }
    
    /**
     * Get the full URL of the request
     * 
     * @param Request $request
     * @return string
     */
    protected function getFullUrl(Request $request)
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return $scheme . '://' . $host . $uri;
    }
    
    /**
     * Get filtered headers (excluding sensitive information)
     * 
     * @return array
     */
    protected function getFilteredHeaders()
    {
        $headers = [];
        $sensitiveHeaders = $this->config('logging.sensitive_headers', [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token'
        ]);
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = strtolower(str_replace('HTTP_', '', $key));
                $headerName = str_replace('_', '-', $headerName);
                
                if (in_array($headerName, $sensitiveHeaders)) {
                    $headers[$headerName] = '[FILTERED]';
                } else {
                    $headers[$headerName] = $value;
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Get filtered request body (excluding sensitive information)
     * 
     * @return array|string
     */
    protected function getFilteredRequestBody()
    {
        $body = $_POST;
        $sensitiveFields = $this->config('logging.sensitive_fields', [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'ssn'
        ]);
        
        foreach ($sensitiveFields as $field) {
            if (isset($body[$field])) {
                $body[$field] = '[FILTERED]';
            }
        }
        
        // Limit body size
        $maxSize = $this->config('logging.max_body_size', 1024); // 1KB default
        $serialized = json_encode($body);
        
        if (strlen($serialized) > $maxSize) {
            return '[BODY TOO LARGE - ' . strlen($serialized) . ' bytes]';
        }
        
        return $body;
    }
    
    /**
     * Get filtered response body
     * 
     * @param Response $response
     * @return string
     */
    protected function getFilteredResponseBody(Response $response)
    {
        $content = $response->getContent();
        $maxSize = $this->config('logging.max_response_body_size', 2048); // 2KB default
        
        if (strlen($content) > $maxSize) {
            return '[RESPONSE TOO LARGE - ' . strlen($content) . ' bytes]';
        }
        
        // Try to parse as JSON and filter sensitive data
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $sensitiveFields = $this->config('logging.sensitive_fields', [
                'password',
                'token',
                'api_key',
                'secret'
            ]);
            
            foreach ($sensitiveFields as $field) {
                if (isset($decoded[$field])) {
                    $decoded[$field] = '[FILTERED]';
                }
            }
            
            return json_encode($decoded);
        }
        
        return $content;
    }
    
    /**
     * Get the user ID if available
     * 
     * @return int|null
     */
    protected function getUserId()
    {
        $user = $this->getUser();
        return $user ? ($user['id'] ?? null) : null;
    }
    
    /**
     * Get log level based on HTTP status code
     * 
     * @param int $statusCode
     * @return string
     */
    protected function getLogLevel($statusCode)
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } elseif ($statusCode >= 300) {
            return 'info';
        } else {
            return 'info';
        }
    }
    
    /**
     * Check if the request should skip logging
     * 
     * @param Request $request
     * @return bool
     */
    protected function shouldSkip(Request $request)
    {
        // Skip if logging is disabled
        if (!$this->config('logging.enabled', true)) {
            return true;
        }
        
        // Skip health check endpoints
        $path = $request->getPath();
        $skipPaths = $this->config('logging.skip_paths', ['/health', '/status', '/ping']);
        
        foreach ($skipPaths as $skipPath) {
            if (strpos($path, $skipPath) === 0) {
                return true;
            }
        }
        
        // Skip static assets
        if ($this->isStaticAsset($path)) {
            return true;
        }
        
        // Skip based on user agent (bots, crawlers)
        if ($this->shouldSkipUserAgent()) {
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
        $staticExtensions = $this->config('logging.static_extensions', [
            'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'
        ]);
        
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), $staticExtensions);
    }
    
    /**
     * Check if should skip based on user agent
     * 
     * @return bool
     */
    protected function shouldSkipUserAgent()
    {
        if (!$this->config('logging.skip_bots', true)) {
            return false;
        }
        
        return $this->isBot();
    }
    
    /**
     * Log request statistics
     * 
     * @return void
     */
    public function logStatistics()
    {
        $stats = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'included_files' => count(get_included_files()),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->logInfo('Request Statistics', $stats);
    }
}