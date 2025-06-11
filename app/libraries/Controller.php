<?php
namespace App\Libraries;

/**
 * Base controller class with common functionality for all controllers
 */
class Controller
{
    /**
     * @var \PDO Database connection
     */
    protected $db;
    
    /**
     * @var bool Transaction status
     */
    private $inTransaction = false;
    
    /**
     * Constructor - can be overridden by child classes
     */
    public function __construct()
    {
        // Initialize database connection if not already set
        if (!isset($GLOBALS['db']) || !($GLOBALS['db'] instanceof \PDO)) {
            // You might want to initialize the database connection here
            // $this->db = new \PDO(...);
            // $GLOBALS['db'] = $this->db;
        } else {
            $this->db = $GLOBALS['db'];
        }
    }
    
    /**
     * Begin a database transaction
     * 
     * @return bool True on success, false on failure
     */
    protected function beginTransaction()
    {
        if ($this->db && !$this->inTransaction) {
            $this->inTransaction = $this->db->beginTransaction();
            return $this->inTransaction;
        }
        return false;
    }
    
    /**
     * Commit the current transaction
     * 
     * @return bool True on success, false on failure
     */
    protected function commitTransaction()
    {
        if ($this->db && $this->inTransaction) {
            $result = $this->db->commit();
            $this->inTransaction = false;
            return $result;
        }
        return false;
    }
    
    /**
     * Roll back the current transaction
     * 
     * @return bool True on success, false on failure
     */
    protected function rollbackTransaction()
    {
        if ($this->db && $this->inTransaction) {
            $result = $this->db->rollBack();
            $this->inTransaction = false;
            return $result;
        }
        return false;
    }
    
    /**
     * Send a welcome email to the user
     * 
     * @param array $user User data
     * @return bool True on success, false on failure
     */
    protected function sendWelcomeEmail($user)
    {
        // This is a placeholder. In a real application, you would implement email sending logic here.
        // For example, using PHPMailer, SwiftMailer, or a service like SendGrid.
        
        // Example implementation (commented out as it requires actual email configuration):
        /*
        $to = $user['email'];
        $subject = 'Welcome to CosmicHub!';
        $message = "Hello " . htmlspecialchars($user['name']) . ",\n\n";
        $message .= "Thank you for registering with CosmicHub. We're excited to have you on board!\n\n";
        $message .= "Get started by exploring your dashboard at: " . SITE_URL . "/dashboard\n\n";
        $message .= "Best regards,\nThe CosmicHub Team";
        
        $headers = [
            'From' => 'noreply@cosmichub.online',
            'Reply-To' => 'support@cosmichub.online',
            'X-Mailer' => 'PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $message, $headers);
        */
        
        // For now, just log that we would send an email
        error_log('Welcome email would be sent to: ' . $user['email']);
        return true;
    }
    
    /**
     * Require user to be logged in
     * 
     * @param string $redirect URL to redirect if not logged in
     * @return void
     */
    protected function requireLogin($redirect = '/login')
    {
        require_login($redirect);
    }
    
    /**
     * Require user to be a guest (not logged in)
     * 
     * @param string $redirect URL to redirect if logged in
     * @return void
     */
    protected function requireGuest($redirect = '/dashboard')
    {
        require_guest($redirect);
    }
    
    /**
     * Require user to have specific role
     * 
     * @param string|array $roles Required role(s)
     * @param string $redirect URL to redirect if check fails
     * @return void
     */
    protected function requireRole($roles, $redirect = '/')
    {
        require_role($roles, $redirect);
    }
    
    /**
     * Get current authenticated user
     * 
     * @return array|null User data or null if not logged in
     */
    protected function getCurrentUser()
    {
        return get_current_user();
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null User ID or null if not logged in
     */
    protected function getCurrentUserId()
    {
        return get_current_user_id();
    }
    
    /**
     * Check if current user has a specific role
     * 
     * @param string|array $roles Role(s) to check
     * @return bool True if user has the role, false otherwise
     */
    protected function hasRole($roles)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        $userRole = $user['role'] ?? 'user';
        $roles = is_array($roles) ? $roles : [$roles];
        
        return in_array($userRole, $roles);
    }
    
    /**
     * Set authentication session data
     * 
     * @param array $user User data to store in session
     * @return void
     */
    protected function setAuthSession(array $user)
    {
        $_SESSION['user'] = $user;
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Set last activity timestamp
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Clear authentication session data
     * 
     * @return void
     */
    protected function clearAuthSession()
    {
        // Unset all session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    /**
     * Get flash message by key
     * 
     * @param string $key Key of the flash message
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Flash message data or default value
     */
    protected function getFlash($key, $default = null)
    {
        if (isset($_SESSION['flash'][$key])) {
            $value = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $value;
        }
        return $default;
    }
    
    /**
     * Set flash message
     * 
     * @param string $key Key to store the message under
     * @param mixed $value Message data to store
     * @return void
     */
    protected function setFlash($key, $value)
    {
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][$key] = $value;
    }
    
    /**
     * Clear flash messages
     * 
     * @param string ...$keys Keys to clear (if none provided, clears all)
     * @return void
     */
    protected function clearFlash(...$keys)
    {
        if (empty($keys)) {
            unset($_SESSION['flash']);
        } else {
            foreach ($keys as $key) {
                unset($_SESSION['flash'][$key]);
            }
        }
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (default: 302)
     * @return void
     */
    protected function redirect($url, $statusCode = 302)
    {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    /**
     * Return JSON response
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code (default: 200)
     * @param array $headers Additional HTTP headers
     * @return void
     */
    protected function json($data, $statusCode = 200, $headers = [])
    {
        header('Content-Type: application/json');
        
        // Set additional headers if provided
        foreach ($headers as $header => $value) {
            header("$header: $value");
        }
        
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Render a view file
     * 
     * @param string $view View file name (without .php extension)
     * @param array $data Data to pass to the view
     * @param string $layout Layout file name (without .php extension, relative to views/layouts/)
     * @throws \Exception If view file is not found
     * @return void
     */
    protected function view($view, $data = [], $layout = 'main')
    {
        // Start output buffering at the very beginning
        if (ob_get_level() == 0) {
            ob_start();
        }
        
        // Clean any previous output
        if (ob_get_length() > 0) {
            ob_clean();
        }
        
        // Add the layout to the data array
        $data['layout'] = $layout;
        
        // Extract data to variables
        extract($data, EXTR_SKIP);
        
        // Build view file path
        $viewFile = VIEWS_DIR . "/{$view}.php";
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file {$view}.php not found in " . VIEWS_DIR);
        }
        
        // Buffer the view content
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        
        // Include layout if specified
        if ($layout) {
            $layoutFile = VIEWS_DIR . "/layouts/{$layout}.php";
            if (file_exists($layoutFile)) {
                // Clean any output that might have been generated before including the layout
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                // Include layout with content variable
                include $layoutFile;
                return;
            }
        }
        
        // Output content directly if no layout or layout not found
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo $content;
    }
}
