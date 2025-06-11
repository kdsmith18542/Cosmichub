<?php
/**
 * Authentication Helper Functions
 * 
 * Provides helper functions for authentication and authorization
 */

if (!function_exists('is_logged_in')) {
    /**
     * Check if user is logged in
     * 
     * @return bool True if user is logged in, false otherwise
     */
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('require_login')) {
    /**
     * Require user to be logged in
     * 
     * @param string $redirect URL to redirect if not logged in
     * @return void
     */
    function require_login($redirect = '/login') {
        if (!is_logged_in()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect);
            exit;
        }
    }
}

if (!function_exists('require_guest')) {
    /**
     * Require user to be a guest (not logged in)
     * 
     * @param string $redirect URL to redirect if logged in
     * @return void
     */
    function require_guest($redirect = '/dashboard') {
        if (is_logged_in()) {
            header('Location: ' . $redirect);
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    /**
     * Require user to have specific role
     * 
     * @param string|array $roles Required role(s)
     * @param string $redirect URL to redirect if check fails
     * @return void
     */
    function require_role($roles, $redirect = '/') {
        if (!is_logged_in()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
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
    }
}

if (!function_exists('get_current_user_id')) {
    /**
     * Get current user ID
     * 
     * @return int|null User ID or null if not logged in
     */
    function get_current_user_id() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('get_current_user')) {
    /**
     * Get current user data
     * 
     * @return array|null User data or null if not logged in
     */
    function get_current_user() {
        if (!is_logged_in()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }
}

if (!function_exists('set_auth_session')) {
    /**
     * Set authentication session data
     * 
     * @param array $user User data
     * @return void
     */
    function set_auth_session($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'] ?? '';
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
}

if (!function_exists('clear_auth_session')) {
    /**
     * Clear authentication session data
     * 
     * @return void
     */
    function clear_auth_session() {
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_email'],
            $_SESSION['user_role'],
            $_SESSION['last_activity']
        );
    }
}
