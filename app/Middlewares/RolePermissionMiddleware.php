<?php

namespace App\Middlewares;

use App\Core\Middleware\Middleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\ForbiddenException;
use App\Services\AuthService;
use App\Services\RoleService;
use App\Services\PermissionService;

/**
 * Role and Permission middleware
 * Handles role-based and permission-based access control
 */
class RolePermissionMiddleware extends Middleware
{
    /**
     * @var AuthService
     */
    protected $authService;
    
    /**
     * @var RoleService
     */
    protected $roleService;
    
    /**
     * @var PermissionService
     */
    protected $permissionService;
    
    /**
     * @var array Configuration
     */
    protected $config = [
        'require_roles' => [],
        'require_permissions' => [],
        'require_all_roles' => false,
        'require_all_permissions' => false,
        'check_ownership' => false,
        'ownership_field' => 'user_id',
        'allow_super_admin' => true,
        'cache_permissions' => true
    ];
    
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
        
        $this->authService = $this->app->make(AuthService::class);
        $this->roleService = $this->app->make(RoleService::class);
        $this->permissionService = $this->app->make(PermissionService::class);
        
        // Get authenticated user
        $user = $request->getUser() ?? $this->authService->getUser();
        
        if (!$user) {
            return $this->handleUnauthenticated($request);
        }
        
        // Check super admin bypass
        if ($this->config['allow_super_admin'] && $this->isSuperAdmin($user)) {
            $this->logActivity('Super admin access granted', [
                'user_id' => $user['id'],
                'path' => $request->getPath(),
                'method' => $request->getMethod()
            ]);
            return $next($request);
        }
        
        // Check roles if required
        if (!empty($this->config['require_roles'])) {
            if (!$this->checkRoles($user, $this->config['require_roles'])) {
                return $this->handleInsufficientRole($request, $user);
            }
        }
        
        // Check permissions if required
        if (!empty($this->config['require_permissions'])) {
            if (!$this->checkPermissions($user, $this->config['require_permissions'])) {
                return $this->handleInsufficientPermission($request, $user);
            }
        }
        
        // Check ownership if required
        if ($this->config['check_ownership']) {
            if (!$this->checkOwnership($request, $user)) {
                return $this->handleOwnershipViolation($request, $user);
            }
        }
        
        // Log successful authorization
        $this->logActivity('Authorization successful', [
            'user_id' => $user['id'],
            'roles' => $this->getUserRoles($user),
            'path' => $request->getPath(),
            'method' => $request->getMethod()
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
     * Set required roles
     * 
     * @param array|string $roles
     * @param bool $requireAll
     * @return $this
     */
    public function requireRoles($roles, $requireAll = false)
    {
        $this->config['require_roles'] = is_array($roles) ? $roles : [$roles];
        $this->config['require_all_roles'] = $requireAll;
        return $this;
    }
    
    /**
     * Set required permissions
     * 
     * @param array|string $permissions
     * @param bool $requireAll
     * @return $this
     */
    public function requirePermissions($permissions, $requireAll = false)
    {
        $this->config['require_permissions'] = is_array($permissions) ? $permissions : [$permissions];
        $this->config['require_all_permissions'] = $requireAll;
        return $this;
    }
    
    /**
     * Enable ownership checking
     * 
     * @param string $field
     * @return $this
     */
    public function enableOwnershipCheck($field = 'user_id')
    {
        $this->config['check_ownership'] = true;
        $this->config['ownership_field'] = $field;
        return $this;
    }
    
    /**
     * Check if user is super admin
     * 
     * @param array $user
     * @return bool
     */
    protected function isSuperAdmin($user)
    {
        return isset($user['is_super_admin']) && $user['is_super_admin'] === true;
    }
    
    /**
     * Check user roles
     * 
     * @param array $user
     * @param array $requiredRoles
     * @return bool
     */
    protected function checkRoles($user, $requiredRoles)
    {
        $userRoles = $this->getUserRoles($user);
        
        if ($this->config['require_all_roles']) {
            // User must have ALL required roles
            foreach ($requiredRoles as $role) {
                if (!in_array($role, $userRoles)) {
                    return false;
                }
            }
            return true;
        } else {
            // User must have at least ONE required role
            foreach ($requiredRoles as $role) {
                if (in_array($role, $userRoles)) {
                    return true;
                }
            }
            return false;
        }
    }
    
    /**
     * Check user permissions
     * 
     * @param array $user
     * @param array $requiredPermissions
     * @return bool
     */
    protected function checkPermissions($user, $requiredPermissions)
    {
        $userPermissions = $this->getUserPermissions($user);
        
        if ($this->config['require_all_permissions']) {
            // User must have ALL required permissions
            foreach ($requiredPermissions as $permission) {
                if (!in_array($permission, $userPermissions)) {
                    return false;
                }
            }
            return true;
        } else {
            // User must have at least ONE required permission
            foreach ($requiredPermissions as $permission) {
                if (in_array($permission, $userPermissions)) {
                    return true;
                }
            }
            return false;
        }
    }
    
    /**
     * Check resource ownership
     * 
     * @param Request $request
     * @param array $user
     * @return bool
     */
    protected function checkOwnership(Request $request, $user)
    {
        // Get resource ID from route parameters
        $resourceId = $request->getRouteParam('id') ?? $request->get('id');
        
        if (!$resourceId) {
            // No resource ID to check ownership against
            return true;
        }
        
        // Get the resource and check ownership
        $resource = $this->getResource($request, $resourceId);
        
        if (!$resource) {
            return false;
        }
        
        $ownerField = $this->config['ownership_field'];
        
        return isset($resource[$ownerField]) && $resource[$ownerField] == $user['id'];
    }
    
    /**
     * Get user roles
     * 
     * @param array $user
     * @return array
     */
    protected function getUserRoles($user)
    {
        if ($this->config['cache_permissions']) {
            $cacheKey = 'user_roles_' . $user['id'];
            $cached = $this->getFromCache($cacheKey);
            
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $roles = $this->roleService->getUserRoles($user['id']);
        
        if ($this->config['cache_permissions']) {
            $this->putInCache($cacheKey, $roles, 300); // Cache for 5 minutes
        }
        
        return $roles;
    }
    
    /**
     * Get user permissions
     * 
     * @param array $user
     * @return array
     */
    protected function getUserPermissions($user)
    {
        if ($this->config['cache_permissions']) {
            $cacheKey = 'user_permissions_' . $user['id'];
            $cached = $this->getFromCache($cacheKey);
            
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $permissions = $this->permissionService->getUserPermissions($user['id']);
        
        if ($this->config['cache_permissions']) {
            $this->putInCache($cacheKey, $permissions, 300); // Cache for 5 minutes
        }
        
        return $permissions;
    }
    
    /**
     * Get resource for ownership checking
     * 
     * @param Request $request
     * @param mixed $resourceId
     * @return array|null
     */
    protected function getResource(Request $request, $resourceId)
    {
        // This is a simplified implementation
        // In a real application, you would determine the resource type
        // from the route and fetch it from the appropriate service
        
        $path = $request->getPath();
        
        // Extract resource type from path (e.g., /api/posts/123 -> posts)
        if (preg_match('/\/api\/([^\/]+)\//', $path, $matches)) {
            $resourceType = $matches[1];
            
            // Use a generic service to fetch the resource
            $serviceName = ucfirst(rtrim($resourceType, 's')) . 'Service';
            
            if ($this->app->has($serviceName)) {
                $service = $this->app->make($serviceName);
                
                if (method_exists($service, 'findById')) {
                    return $service->findById($resourceId);
                }
            }
        }
        
        return null;
    }
    
    /**
     * Handle unauthenticated user
     * 
     * @param Request $request
     * @return Response
     */
    protected function handleUnauthenticated(Request $request)
    {
        $this->logActivity('Authorization failed - unauthenticated', [
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp()
        ]);
        
        throw new ForbiddenException('Authentication required', 'UNAUTHENTICATED');
    }
    
    /**
     * Handle insufficient role
     * 
     * @param Request $request
     * @param array $user
     * @return Response
     */
    protected function handleInsufficientRole(Request $request, $user)
    {
        $this->logActivity('Authorization failed - insufficient role', [
            'user_id' => $user['id'],
            'user_roles' => $this->getUserRoles($user),
            'required_roles' => $this->config['require_roles'],
            'path' => $request->getPath(),
            'method' => $request->getMethod()
        ]);
        
        throw new ForbiddenException('Insufficient role privileges', 'INSUFFICIENT_ROLE');
    }
    
    /**
     * Handle insufficient permission
     * 
     * @param Request $request
     * @param array $user
     * @return Response
     */
    protected function handleInsufficientPermission(Request $request, $user)
    {
        $this->logActivity('Authorization failed - insufficient permission', [
            'user_id' => $user['id'],
            'user_permissions' => $this->getUserPermissions($user),
            'required_permissions' => $this->config['require_permissions'],
            'path' => $request->getPath(),
            'method' => $request->getMethod()
        ]);
        
        throw new ForbiddenException('Insufficient permissions', 'INSUFFICIENT_PERMISSION');
    }
    
    /**
     * Handle ownership violation
     * 
     * @param Request $request
     * @param array $user
     * @return Response
     */
    protected function handleOwnershipViolation(Request $request, $user)
    {
        $this->logActivity('Authorization failed - ownership violation', [
            'user_id' => $user['id'],
            'resource_id' => $request->getRouteParam('id'),
            'path' => $request->getPath(),
            'method' => $request->getMethod()
        ]);
        
        throw new ForbiddenException('Access denied - resource ownership required', 'OWNERSHIP_REQUIRED');
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
}