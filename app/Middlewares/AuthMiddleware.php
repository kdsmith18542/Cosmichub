<?php

namespace App\Middlewares;

use App\Core\Middleware\Middleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\UnauthorizedException;
use App\Services\AuthService;

/**
 * Authentication middleware
 * Protects routes that require user authentication
 */
class AuthMiddleware extends Middleware
{
    /**
     * @var AuthService
     */
    protected $authService;
    
    /**
     * @var array Default configuration
     */
    protected $config = [
        'redirect_to' => '/login',
        'store_intended_url' => true,
        'check_session_timeout' => true,
        'session_timeout' => 3600, // 1 hour
        'require_email_verification' => false,
        'allowed_roles' => [],
        'check_permissions' => []
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
        
        // Check if user is authenticated
        if (!$this->isAuthenticated($request)) {
            return $this->handleUnauthenticated($request);
        }
        
        $user = $this->getUser($request);
        
        // Check session timeout
        if ($this->config['check_session_timeout'] && $this->isSessionExpired($request)) {
            $this->authService->logout();
            return $this->handleSessionExpired($request);
        }
        
        // Check email verification if required
        if ($this->config['require_email_verification'] && !$this->isEmailVerified($user)) {
            return $this->handleEmailNotVerified($request);
        }
        
        // Check user roles if specified
        if (!empty($this->config['allowed_roles']) && !$this->hasRequiredRole($user)) {
            return $this->handleInsufficientRole($request);
        }
        
        // Check permissions if specified
        if (!empty($this->config['check_permissions']) && !$this->hasRequiredPermissions($user)) {
            return $this->handleInsufficientPermissions($request);
        }
        
        // Update last activity
        $this->updateLastActivity($request);
        
        // Log successful authentication
        $this->logActivity('User authenticated', [
            'user_id' => $user['id'],
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp()
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
     * Handle unauthenticated request
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleUnauthenticated(Request $request)
    {
        $this->logActivity('Authentication required', [
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent()
        ]);
        
        if ($this->expectsJson($request)) {
            throw new UnauthorizedException('Authentication required');
        }
        
        // Store intended URL for redirect after login
        if ($this->config['store_intended_url']) {
            $_SESSION['intended_url'] = $request->getFullUrl();
        }
        
        return new Response('', 302, [
            'Location' => $this->config['redirect_to']
        ]);
    }
    
    /**
     * Handle session expired
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleSessionExpired(Request $request)
    {
        $this->logActivity('Session expired', [
            'path' => $request->getPath(),
            'ip' => $this->getClientIp()
        ]);
        
        if ($this->expectsJson($request)) {
            throw new UnauthorizedException('Session expired');
        }
        
        $_SESSION['message'] = 'Your session has expired. Please log in again.';
        
        return new Response('', 302, [
            'Location' => $this->config['redirect_to']
        ]);
    }
    
    /**
     * Handle email not verified
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleEmailNotVerified(Request $request)
    {
        if ($this->expectsJson($request)) {
            throw new UnauthorizedException('Email verification required');
        }
        
        return new Response('', 302, [
            'Location' => '/email/verify'
        ]);
    }
    
    /**
     * Handle insufficient role
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleInsufficientRole(Request $request)
    {
        $this->logActivity('Insufficient role', [
            'user_id' => $this->getUser($request)['id'] ?? null,
            'required_roles' => $this->config['allowed_roles'],
            'path' => $request->getPath()
        ]);
        
        if ($this->expectsJson($request)) {
            throw new UnauthorizedException('Insufficient privileges');
        }
        
        $_SESSION['error'] = 'You do not have permission to access this page.';
        
        return new Response('', 302, [
            'Location' => '/dashboard'
        ]);
    }
    
    /**
     * Handle insufficient permissions
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleInsufficientPermissions(Request $request)
    {
        $this->logActivity('Insufficient permissions', [
            'user_id' => $this->getUser($request)['id'] ?? null,
            'required_permissions' => $this->config['check_permissions'],
            'path' => $request->getPath()
        ]);
        
        if ($this->expectsJson($request)) {
            throw new UnauthorizedException('Insufficient permissions');
        }
        
        $_SESSION['error'] = 'You do not have the required permissions.';
        
        return new Response('', 302, [
            'Location' => '/dashboard'
        ]);
    }
    
    /**
     * Check if session is expired
     * 
     * @param Request $request
     * @return bool
     */
    protected function isSessionExpired(Request $request)
    {
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        return (time() - $lastActivity) > $this->config['session_timeout'];
    }
    
    /**
     * Check if email is verified
     * 
     * @param array $user
     * @return bool
     */
    protected function isEmailVerified($user)
    {
        return !empty($user['email_verified_at']);
    }
    
    /**
     * Check if user has required role
     * 
     * @param array $user
     * @return bool
     */
    protected function hasRequiredRole($user)
    {
        $userRole = $user['role'] ?? 'user';
        return in_array($userRole, $this->config['allowed_roles']);
    }
    
    /**
     * Check if user has required permissions
     * 
     * @param array $user
     * @return bool
     */
    protected function hasRequiredPermissions($user)
    {
        $userPermissions = $user['permissions'] ?? [];
        
        foreach ($this->config['check_permissions'] as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Update last activity timestamp
     * 
     * @param Request $request
     * @return void
     */
    protected function updateLastActivity(Request $request)
    {
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check if request expects JSON response
     * 
     * @param Request $request
     * @return bool
     */
    protected function expectsJson(Request $request)
    {
        return $request->expectsJson() || 
               $request->isAjax() || 
               strpos($request->getPath(), '/api/') === 0;
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