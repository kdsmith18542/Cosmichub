<?php
/**
 * Bootstrap file for the application
 * 
 * This file initializes the application and sets up autoloading.
 */

// Define application constants
define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', __DIR__ . '/app');
define('CONFIG_PATH', APP_PATH . '/config');
define('STORAGE_PATH', APP_ROOT . '/storage');
define('VIEWS_PATH', APP_PATH . '/views');

// Set default timezone
date_default_timezone_set('UTC');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables
if (file_exists(APP_ROOT . '/.env')) {
    $env = parse_ini_file(APP_ROOT . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// Register autoloader
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'App\\';
    
    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/app/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Load helper functions
require_once __DIR__ . '/app/helpers.php';

// Load configuration
$config = [];
$configFiles = glob(CONFIG_PATH . '/*.php');

foreach ($configFiles as $file) {
    $key = basename($file, '.php');
    $config[$key] = require $file;
}

// Store config in a global variable for easy access
$GLOBALS['config'] = $config;

// Initialize database connection
try {
    // The Database class will be autoloaded when needed
    $db = Database::getInstance();
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Unable to connect to the database. Please check your configuration.');
}

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return false;
    }
    
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Standards',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ];
    
    $errorType = $errorTypes[$errno] ?? 'Unknown Error';
    $message = "$errorType: $errstr in $errfile on line $errline";
    
    error_log($message);
    
    // Don't execute PHP internal error handler
    return true;
});

// Set exception handler
set_exception_handler(function($e) {
    error_log('Uncaught Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " (" . $e->getLine() . ")\n";
        echo $e->getTraceAsString() . "\n";
    } else {
        // In a web context, show a user-friendly error page
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        
        if (file_exists(VIEWS_PATH . '/errors/500.php')) {
            include VIEWS_PATH . '/errors/500.php';
        } else {
            echo '<h1>500 Internal Server Error</h1>';
            echo '<p>An error occurred while processing your request.</p>';
            if (getenv('APP_ENV') === 'development') {
                echo '<pre>' . $e->getMessage() . '</pre>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }
        }
    }
});

// Shutdown function to handle fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
        $message = "Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}";
        error_log($message);
        
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            
            if (file_exists(VIEWS_PATH . '/errors/500.php')) {
                include VIEWS_PATH . '/errors/500.php';
            } else {
                echo '<h1>500 Internal Server Error</h1>';
                echo '<p>A fatal error occurred while processing your request.</p>';
                if (getenv('APP_ENV') === 'development') {
                    echo '<pre>' . $message . '</pre>';
                }
            }
        }
    }
});
