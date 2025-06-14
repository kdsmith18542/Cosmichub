<?php

namespace App\Middlewares;

use App\Core\Middleware\Middleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\SecurityException;
use App\Services\SecurityService;

/**
 * Security middleware
 * Handles various security measures including IP filtering, request sanitization, and security headers
 */
class SecurityMiddleware extends Middleware
{
    /**
     * @var SecurityService
     */
    protected $securityService;
    
    /**
     * @var array Configuration
     */
    protected $config = [
        'ip_whitelist' => [],
        'ip_blacklist' => [],
        'check_user_agent' => true,
        'blocked_user_agents' => [
            'bot', 'crawler', 'spider', 'scraper'
        ],
        'sanitize_input' => true,
        'max_request_size' => 10485760, // 10MB
        'allowed_file_types' => [
            'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'
        ],
        'security_headers' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
        ],
        'rate_limit_enabled' => true,
        'rate_limit_requests' => 100,
        'rate_limit_window' => 3600, // 1 hour
        'honeypot_enabled' => true,
        'honeypot_fields' => ['website', 'url', 'homepage'],
        'sql_injection_protection' => true,
        'xss_protection' => true,
        'csrf_protection' => true
    ];
    
    /**
     * Handle the request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws SecurityException
     */
    public function handle(Request $request, callable $next)
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }
        
        $this->securityService = $this->app->make(SecurityService::class);
        
        // Check IP restrictions
        $this->checkIpRestrictions($request);
        
        // Check user agent
        if ($this->config['check_user_agent']) {
            $this->checkUserAgent($request);
        }
        
        // Check request size
        $this->checkRequestSize($request);
        
        // Check for honeypot fields
        if ($this->config['honeypot_enabled']) {
            $this->checkHoneypot($request);
        }
        
        // Sanitize input
        if ($this->config['sanitize_input']) {
            $this->sanitizeInput($request);
        }
        
        // Check for SQL injection attempts
        if ($this->config['sql_injection_protection']) {
            $this->checkSqlInjection($request);
        }
        
        // Check for XSS attempts
        if ($this->config['xss_protection']) {
            $this->checkXss($request);
        }
        
        // Rate limiting
        if ($this->config['rate_limit_enabled']) {
            $this->checkRateLimit($request);
        }
        
        // Validate file uploads
        $this->validateFileUploads($request);
        
        // Process the request
        $response = $next($request);
        
        // Add security headers
        $this->addSecurityHeaders($response);
        
        // Log security event
        $this->logActivity('Security check passed', [
            'ip' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent(),
            'path' => $request->getPath(),
            'method' => $request->getMethod()
        ]);
        
        return $response;
    }
    
    /**
     * Configure the middleware with parameters
     * 
     * @param array $config
     * @return $this
     */
    public function configure(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
    
    /**
     * Check IP restrictions
     * 
     * @param Request $request
     * @throws SecurityException
     */
    protected function checkIpRestrictions(Request $request)
    {
        $clientIp = $this->getClientIp();
        
        // Check blacklist first
        if (!empty($this->config['ip_blacklist'])) {
            foreach ($this->config['ip_blacklist'] as $blockedIp) {
                if ($this->ipMatches($clientIp, $blockedIp)) {
                    $this->handleSecurityViolation('IP blacklisted', [
                        'ip' => $clientIp,
                        'blocked_ip' => $blockedIp
                    ]);
                }
            }
        }
        
        // Check whitelist if configured
        if (!empty($this->config['ip_whitelist'])) {
            $allowed = false;
            
            foreach ($this->config['ip_whitelist'] as $allowedIp) {
                if ($this->ipMatches($clientIp, $allowedIp)) {
                    $allowed = true;
                    break;
                }
            }
            
            if (!$allowed) {
                $this->handleSecurityViolation('IP not whitelisted', [
                    'ip' => $clientIp,
                    'whitelist' => $this->config['ip_whitelist']
                ]);
            }
        }
    }
    
    /**
     * Check user agent
     * 
     * @param Request $request
     * @throws SecurityException
     */
    protected function checkUserAgent(Request $request)
    {
        $userAgent = strtolower($this->getUserAgent());
        
        foreach ($this->config['blocked_user_agents'] as $blockedAgent) {
            if (strpos($userAgent, strtolower($blockedAgent)) !== false) {
                $this->handleSecurityViolation('Blocked user agent', [
                    'user_agent' => $userAgent,
                    'blocked_pattern' => $blockedAgent
                ]);
            }
        }
    }
    
    /**
     * Check request size
     * 
     * @param Request $request
     * @throws SecurityException
     */
    protected function checkRequestSize(Request $request)
    {
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
        
        if ($contentLength > $this->config['max_request_size']) {
            $this->handleSecurityViolation('Request size too large', [
                'content_length' => $contentLength,
                'max_allowed' => $this->config['max_request_size']
            ]);
        }
    }
    
    /**
     * Check honeypot fields
     * 
     * @param Request $request
     * @throws SecurityException
     */
    protected function checkHoneypot(Request $request)
    {
        foreach ($this->config['honeypot_fields'] as $field) {
            if ($request->has($field) && !empty($request->get($field))) {
                $this->handleSecurityViolation('Honeypot field filled', [
                    'field' => $field,
                    'value' => $request->get($field)
                ]);
            }
        }
    }
    
    /**
     * Sanitize input data
     * 
     * @param Request $request
     */
    protected function sanitizeInput(Request $request)
    {
        $data = $request->all();
        $sanitized = $this->sanitizeArray($data);
        $request->replace($sanitized);
    }
    
    /**
     * Recursively sanitize array data
     * 
     * @param array $data
     * @return array
     */
    protected function sanitizeArray($data)
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $this->sanitizeString($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize string value
     * 
     * @param string $value
     * @return string
     */
    protected function sanitizeString($value)
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        // Remove control characters except tab, newline, and carriage return
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        return $value;
    }
    
    /**
     * Check for SQL injection attempts
     * 
     * @param Request $request
     * @throws SecurityException
     */
    protected function checkSqlInjection(Request $request)
    {
        $sqlPatterns = [
            '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i',
            '/\b(or|and)\s+\d+\s*=\s*\d+/i',
            '/[\'"]\s*(or|and)\s+[\'"]\s*[\'"]\s*=\s*[\'"]\s*/i',
            '/\b(script|javascript|vbscript)\b/i'
        ];

        $data = $request->all();
        $input = json_encode($data);

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new SecurityException('Potential SQL injection detected', [
                    'ip' => $this->getClientIp(),
                    'user_agent' => $this->getUserAgent(),
                    'pattern' => $pattern
                ]);
            }
        }
    }

    /**
     * Check for XSS attacks
     */
    protected function checkXss(Request $request)
    {
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/javascript:/i',
            '/on\w+\s*=/i'
        ];

        $data = $request->all();
        $input = json_encode($data);

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new SecurityException('Potential XSS attack detected', [
                    'ip' => $this->getClientIp(),
                    'user_agent' => $this->getUserAgent(),
                    'pattern' => $pattern
                ]);
            }
        }
    }

    /**
     * Check rate limiting
     */
    protected function checkRateLimit(Request $request)
    {
        $key = 'rate_limit:' . $this->getClientIp();
        $cache = $this->app->make('cache');
        
        $attempts = $cache->get($key, 0);
        
        if ($attempts >= $this->config['rate_limit_requests']) {
            throw new SecurityException('Rate limit exceeded', [
                'ip' => $this->getClientIp(),
                'attempts' => $attempts,
                'limit' => $this->config['rate_limit_requests']
            ]);
        }
        
        $cache->put($key, $attempts + 1, $this->config['rate_limit_window']);
    }

    /**
     * Validate file uploads
     */
    protected function validateFileUploads(Request $request)
    {
        if (empty($_FILES)) {
            return;
        }

        foreach ($_FILES as $file) {
            if (is_array($file['name'])) {
                for ($i = 0; $i < count($file['name']); $i++) {
                    $this->validateSingleFile([
                        'name' => $file['name'][$i],
                        'type' => $file['type'][$i],
                        'size' => $file['size'][$i]
                    ]);
                }
            } else {
                $this->validateSingleFile($file);
            }
        }
    }

    /**
     * Validate a single file
     */
    protected function validateSingleFile($file)
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $this->config['allowed_file_types'])) {
            throw new SecurityException('File type not allowed', [
                'filename' => $file['name'],
                'extension' => $extension,
                'allowed' => $this->config['allowed_file_types']
            ]);
        }
    }

    /**
     * Add security headers to response
     */
    protected function addSecurityHeaders(Response $response)
    {
        foreach ($this->config['security_headers'] as $header => $value) {
            $response->setHeader($header, $value);
        }
    }

    /**
     * Get client IP address
     */
    protected function getClientIp()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
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
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent
     */
    protected function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if IP matches pattern
     */
    protected function ipMatches($ip, $pattern)
    {
        if ($ip === $pattern) {
            return true;
        }

        if (strpos($pattern, '/') !== false) {
            return $this->ipInCidr($ip, $pattern);
        }

        if (strpos($pattern, '*') !== false) {
            $regex = '/^' . str_replace('*', '\d+', preg_quote($pattern, '/')) . '$/';
            return preg_match($regex, $ip);
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInCidr($ip, $cidr)
    {
        list($subnet, $mask) = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }

    /**
     * Check if request should be skipped
     */
    protected function shouldSkip(Request $request)
    {
        $path = $request->getPath();
        $skipPaths = ['/health', '/status', '/favicon.ico'];
        
        foreach ($skipPaths as $skipPath) {
            if (strpos($path, $skipPath) === 0) {
                return true;
            }
        }
        
        return false;
    }
}