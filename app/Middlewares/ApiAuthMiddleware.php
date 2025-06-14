<?php

namespace App\Middlewares;

use App\Core\Middleware\Middleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\UnauthorizedException;
use App\Services\AuthService;
use App\Services\UserTokenService;

/**
 * API Authentication middleware
 * Handles JWT token authentication for API routes
 */
class ApiAuthMiddleware extends Middleware
{
    /**
     * @var AuthService
     */
    protected $authService;
    
    /**
     * @var UserTokenService
     */
    protected $tokenService;
    
    /**
     * @var array Default configuration
     */
    protected $config = [
        'token_header' => 'Authorization',
        'token_prefix' => 'Bearer ',
        'check_token_expiry' => true,
        'check_token_blacklist' => true,
        'require_scope' => [],
        'rate_limit_by_token' => true
    ];
    
    /**
     * Handle the request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws UnauthorizedException
     */
    public function handle(Request $request, callable $next)
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }
        
        $this->authService = $this->app->make(AuthService::class);
        $this->tokenService = $this->app->make(UserTokenService::class);
        
        // Extract token from request
        $token = $this->extractToken($request);
        
        if (!$token) {
            return $this->handleMissingToken($request);
        }
        
        // Validate token
        $tokenData = $this->validateToken($token);
        
        if (!$tokenData) {
            return $this->handleInvalidToken($request);
        }
        
        // Check token expiry
        if ($this->config['check_token_expiry'] && $this->isTokenExpired($tokenData)) {
            return $this->handleExpiredToken($request);
        }
        
        // Check token blacklist
        if ($this->config['check_token_blacklist'] && $this->isTokenBlacklisted($token)) {
            return $this->handleBlacklistedToken($request);
        }
        
        // Load user from token
        $user = $this->loadUserFromToken($tokenData);
        
        if (!$user) {
            return $this->handleUserNotFound($request);
        }
        
        // Check required scopes
        if (!empty($this->config['require_scope']) && !$this->hasRequiredScope($tokenData)) {
            return $this->handleInsufficientScope($request);
        }
        
        // Set user in request
        $request->setUser($user);
        $request->setToken($tokenData);
        
        // Update token last used timestamp
        $this->updateTokenUsage($token);
        
        // Log successful API authentication
        $this->logActivity('API authentication successful', [
            'user_id' => $user['id'],
            'token_id' => $tokenData['id'] ?? null,
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent()
        ]);
        
        return $next($request);
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
     * Extract token from request
     * 
     * @param Request $request
     * @return string|null
     */
    protected function extractToken(Request $request)
    {
        // Check Authorization header
        $authHeader = $request->getHeader($this->config['token_header']);
        
        if ($authHeader && strpos($authHeader, $this->config['token_prefix']) === 0) {
            return substr($authHeader, strlen($this->config['token_prefix']));
        }
        
        // Check query parameter as fallback
        return $request->get('token') ?? $request->get('access_token');
    }
    
    /**
     * Validate token
     * 
     * @param string $token
     * @return array|null
     */
    protected function validateToken($token)
    {
        try {
            return $this->tokenService->validateToken($token);
        } catch (\Exception $e) {
            $this->logActivity('Token validation failed', [
                'error' => $e->getMessage(),
                'ip' => $this->getClientIp()
            ]);
            return null;
        }
    }
    
    /**
     * Check if token is expired
     * 
     * @param array $tokenData
     * @return bool
     */
    protected function isTokenExpired($tokenData)
    {
        if (!isset($tokenData['expires_at'])) {
            return false;
        }
        
        return strtotime($tokenData['expires_at']) < time();
    }
    
    /**
     * Check if token is blacklisted
     * 
     * @param string $token
     * @return bool
     */
    protected function isTokenBlacklisted($token)
    {
        return $this->tokenService->isTokenBlacklisted($token);
    }
    
    /**
     * Load user from token data
     * 
     * @param array $tokenData
     * @return array|null
     */
    protected function loadUserFromToken($tokenData)
    {
        if (!isset($tokenData['user_id'])) {
            return null;
        }
        
        return $this->authService->getUserById($tokenData['user_id']);
    }
    
    /**
     * Check if token has required scope
     * 
     * @param array $tokenData
     * @return bool
     */
    protected function hasRequiredScope($tokenData)
    {
        $tokenScopes = $tokenData['scopes'] ?? [];
        
        foreach ($this->config['require_scope'] as $requiredScope) {
            if (!in_array($requiredScope, $tokenScopes)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Update token usage timestamp
     * 
     * @param string $token
     * @return void
     */
    protected function updateTokenUsage($token)
    {
        $this->tokenService->updateLastUsed($token);
    }
    
    /**
     * Handle missing token
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleMissingToken(Request $request)
    {
        $this->logActivity('API authentication failed - missing token', [
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp()
        ]);
        
        throw new UnauthorizedException('Authentication token required', 'MISSING_TOKEN');
    }
    
    /**
     * Handle invalid token
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleInvalidToken(Request $request)
    {
        $this->logActivity('API authentication failed - invalid token', [
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp()
        ]);
        
        throw new UnauthorizedException('Invalid authentication token', 'INVALID_TOKEN');
    }
    
    /**
     * Handle expired token
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleExpiredToken(Request $request)
    {
        $this->logActivity('API authentication failed - expired token', [
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp()
        ]);
        
        throw new UnauthorizedException('Authentication token has expired', 'EXPIRED_TOKEN');
    }
    
    /**
     * Handle blacklisted token
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleBlacklistedToken(Request $request)
    {
        $this->logActivity('API authentication failed - blacklisted token', [
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp()
        ]);
        
        throw new UnauthorizedException('Authentication token has been revoked', 'REVOKED_TOKEN');
    }
    
    /**
     * Handle user not found
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleUserNotFound(Request $request)
    {
        $this->logActivity('API authentication failed - user not found', [
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp()
        ]);
        
        throw new UnauthorizedException('User account not found', 'USER_NOT_FOUND');
    }
    
    /**
     * Handle insufficient scope
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleInsufficientScope(Request $request)
    {
        $this->logActivity('API authentication failed - insufficient scope', [
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'required_scope' => $this->config['require_scope'],
            'ip' => $this->getClientIp()
        ]);
        
        throw new UnauthorizedException('Insufficient token scope', 'INSUFFICIENT_SCOPE');
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    protected function getClientIp()
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
               $_SERVER['HTTP_X_REAL_IP'] ?? 
               $_SERVER['REMOTE_ADDR'] ?? 
               'unknown';
    }
    
    /**
     * Get user agent
     * 
     * @return string
     */
    protected function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
}