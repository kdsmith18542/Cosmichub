<?php
/**
 * Authentication Middleware
 * 
 * Protects routes that require authentication
 */

class AuthMiddleware {
    /**
     * Check if user is authenticated
     * 
     * @param string $redirect URL to redirect if not authenticated
     * @return bool True if authenticated, false otherwise
     */
    public static function check($redirect = '/login') {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect);
            exit;
        }
        return true;
    }
    
    /**
     * Check if user is a guest (not authenticated)
     * 
     * @param string $redirect URL to redirect if authenticated
     * @return bool True if guest, false otherwise
     */
    public static function guest($redirect = '/dashboard') {
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . $redirect);
            exit;
        }
        return true;
    }
    
    /**
     * Check if user has a specific role
     * 
     * @param string|array $roles Role or array of roles to check
     * @param string $redirect URL to redirect if check fails
     * @return bool True if user has required role, false otherwise
     */
    public static function role($roles, $redirect = '/') {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        $userRole = $_SESSION['user_role'] ?? 'user';
        $roles = is_array($roles) ? $roles : [$roles];
        
        if (!in_array($userRole, $roles)) {
            $_SESSION['error'] = 'You do not have permission to access this page';
            header('Location: ' . $redirect);
            exit;
        }
        
        return true;
    }
}
