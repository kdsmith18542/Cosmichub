<?php

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use Psr\Log\LoggerInterface;

/**
 * Logging Middleware
 * 
 * Logs HTTP requests and responses for debugging and monitoring purposes.
 */
class LoggingMiddleware implements MiddlewareInterface
{
    /**
     * Log configuration.
     *
     * @var array
     */
    protected $config = [
        'log_requests' => true,
        'log_responses' => true,
        'log_headers' => false,
        'log_body' => false,
        'max_body_length' => 1000,
        'sensitive_headers' => [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token'
        ],
        'sensitive_fields' => [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'api_key'
        ]
    ];

    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next)
    {
        $startTime = microtime(true);
        $requestId = $this->generateRequestId();
        
        // Log the incoming request
        if ($this->config['log_requests']) {
            $this->logRequest($request, $requestId);
        }
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds
        
        // Log the outgoing response
        if ($this->config['log_responses']) {
            $this->logResponse($response, $requestId, $duration);
        }
        
        // Add request ID to response headers for tracking
        $response->setHeader('X-Request-ID', $requestId);
        
        return $response;
    }
    
    /**
     * Log the incoming request.
     *
     * @param Request $request
     * @param string $requestId
     * @return void
     */
    protected function logRequest(Request $request, $requestId)
    {
        $logData = [
            'type' => 'request',
            'request_id' => $requestId,
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'path' => $request->getPath(),
            'query_string' => $request->getQueryString(),
            'ip' => $this->getClientIp($request),
            'user_agent' => $request->getHeader('User-Agent')
        ];
        
        // Add headers if configured
        if ($this->config['log_headers']) {
            $logData['headers'] = $this->sanitizeHeaders($request->getHeaders());
        }
        
        // Add request body if configured
        if ($this->config['log_body']) {
            $body = $request->getBody();
            if ($body) {
                $logData['body'] = $this->sanitizeBody($body);
            }
        }
        
        // Add authenticated user info if available
        $userId = $this->getAuthenticatedUserId($request);
        if ($userId) {
            $logData['user_id'] = $userId;
        }
        
        $this->writeLog('info', 'HTTP Request', $logData);
    }
    
    /**
     * Log the outgoing response.
     *
     * @param Response $response
     * @param string $requestId
     * @param float $duration
     * @return void
     */
    protected function logResponse(Response $response, $requestId, $duration)
    {
        $logData = [
            'type' => 'response',
            'request_id' => $requestId,
            'timestamp' => date('Y-m-d H:i:s'),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'content_length' => strlen($response->getContent())
        ];
        
        // Add headers if configured
        if ($this->config['log_headers']) {
            $logData['headers'] = $response->getHeaders();
        }
        
        // Add response body if configured (truncated for large responses)
        if ($this->config['log_body']) {
            $content = $response->getContent();
            if ($content) {
                $logData['body'] = $this->truncateContent($content);
            }
        }
        
        // Determine log level based on status code
        $level = $this->getLogLevelForStatusCode($response->getStatusCode());
        
        $this->writeLog($level, 'HTTP Response', $logData);
    }
    
    /**
     * Generate a unique request ID.
     *
     * @return string
     */
    protected function generateRequestId()
    {
        return uniqid('req_', true);
    }
    
    /**
     * Get the client IP address.
     *
     * @param Request $request
     * @return string
     */
    protected function getClientIp(Request $request)
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
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
     * Sanitize headers by removing sensitive information.
     *
     * @param array $headers
     * @return array
     */
    protected function sanitizeHeaders(array $headers)
    {
        $sanitized = [];
        
        foreach ($headers as $name => $value) {
            $lowerName = strtolower($name);
            
            if (in_array($lowerName, $this->config['sensitive_headers'])) {
                $sanitized[$name] = '[REDACTED]';
            } else {
                $sanitized[$name] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize request body by removing sensitive fields.
     *
     * @param string $body
     * @return string
     */
    protected function sanitizeBody($body)
    {
        // Truncate if too long
        $body = $this->truncateContent($body);
        
        // Try to parse as JSON and sanitize
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $sanitized = $this->sanitizeArray($decoded);
            return json_encode($sanitized);
        }
        
        // For form data, parse and sanitize
        if (strpos($body, '=') !== false && strpos($body, '&') !== false) {
            parse_str($body, $parsed);
            $sanitized = $this->sanitizeArray($parsed);
            return http_build_query($sanitized);
        }
        
        return $body;
    }
    
    /**
     * Sanitize an array by removing sensitive fields.
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeArray(array $data)
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            if (in_array($lowerKey, $this->config['sensitive_fields'])) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Truncate content if it exceeds the maximum length.
     *
     * @param string $content
     * @return string
     */
    protected function truncateContent($content)
    {
        if (strlen($content) > $this->config['max_body_length']) {
            return substr($content, 0, $this->config['max_body_length']) . '... [TRUNCATED]';
        }
        
        return $content;
    }
    
    /**
     * Get the appropriate log level for a status code.
     *
     * @param int $statusCode
     * @return string
     */
    protected function getLogLevelForStatusCode($statusCode)
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
     * Write log entry.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function writeLog(string $level, string $message, array $context = [])
    {
        $this->logger->log($level, $message, $context);
    }
    
    /**
     * Get the log directory path.
     *
     * @return string
     */
    protected function getLogDirectory()
    {
        return app()->storagePath('logs');
    }
    
    /**
     * Set logging configuration.
     *
     * @param array $config
     * @return void
     */
    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Get current logging configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}