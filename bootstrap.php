<?php
/**
 * Bootstrap file for the application
 *
 * This file initializes the application and sets up autoloading.
 */

// Start output buffering at the very beginning
ob_start();

// Enable error reporting with maximum verbosity
error_reporting(-1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
ini_set('html_errors', '1');

// Define application paths before any output
// Use __DIR__ to get the current script's directory (most reliable method)
$scriptDir = __DIR__;

// Define possible root directories to check
$possibleRoots = [
    $scriptDir,                                  // Current script directory
    dirname($scriptDir),                        // One level up
    dirname(dirname($scriptDir)),               // Two levels up
    dirname(dirname(dirname($scriptDir))),      // Three levels up
    'C:\\Users\\Keith\\vscodeclone\\CosmicHub.Online-1'  // Absolute path as last resort
];

// Find the first valid root directory that contains app/ and public/
$rootDir = null;
foreach ($possibleRoots as $possibleRoot) {
    $possibleAppDir = $possibleRoot . '/app';
    $possiblePublicDir = $possibleRoot . '/public';
    
    if (is_dir($possibleAppDir) && is_dir($possiblePublicDir)) {
        $rootDir = $possibleRoot;
        break;
    }
}

if ($rootDir === null) {
    die("Could not determine project root directory. Tried: " . implode(", ", $possibleRoots));
}

// Define paths using forward slashes for consistency
$appDir = "$rootDir/app";
$configDir = "$appDir/config";
$viewsDir = "$appDir/views";

// Define constants for directory paths if not already defined
if (!defined('APP_DIR')) {
    define('APP_DIR', $appDir);
}
if (!defined('CONFIG_DIR')) {
    define('CONFIG_DIR', $configDir);
}
if (!defined('VIEWS_DIR')) {
    define('VIEWS_DIR', $viewsDir);
}
$storageDir = "$rootDir/storage";
$logsDir = "$storageDir/logs";

// Debug information
error_log("\n=== Bootstrap Path Resolution ===");
error_log("Script Directory: $scriptDir");
error_log("Root Directory: $rootDir");
error_log("App Directory: $appDir");
error_log("Config Directory: $configDir");

// Debug information
error_log("\n=== Bootstrap Path Resolution ===");
error_log("Script Directory: $scriptDir");
error_log("Root Directory: $rootDir");
error_log("App Directory: $appDir");
error_log("Config Directory: $configDir");
// Debug information
error_log("\n=== Bootstrap Path Resolution ===");
error_log("Script Directory: $scriptDir");
error_log("Root Directory: $rootDir");
error_log("App Directory: $appDir");
error_log("Config Directory: $configDir");

// Verify the directories exist with better error messages
$requiredDirs = [
    'root' => $rootDir,
    'app' => $appDir,
    'config' => $configDir,
    'storage' => $storageDir,
    'logs' => $logsDir
];

$missingDirs = [];
foreach ($requiredDirs as $name => $dir) {
    if (!is_dir($dir)) {
        $missingDirs[$name] = $dir;
        error_log("Warning: Required directory '$name' does not exist: $dir");
    }
}

// Only fail if critical directories are missing
$criticalDirs = ['app', 'config'];
$criticalMissing = array_intersect_key($missingDirs, array_flip($criticalDirs));
if (!empty($criticalMissing)) {
    $missingList = [];
    foreach ($criticalMissing as $name => $path) {
        $missingList[] = "- $name: $path";
    }
    die("Critical: Missing required directories in $rootDir:\n" . implode("\n", $missingList) . "\n");
}

// Create storage and logs directories if they don't exist
$writableDirs = [
    'storage' => $storageDir,
    'logs' => $logsDir
];

foreach ($writableDirs as $name => $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("Warning: Could not create $name directory: $dir");
        } else {
            error_log("Created $name directory: $dir");
        }
    }
}

// Verify the root directory contains expected directories
$expectedDirs = [
    'app' => "$rootDir/app",
    'config' => "$appDir/config",
    'public' => "$rootDir/public",
    'storage' => "$storageDir"
];

// Check each expected directory and try to find alternatives if missing
foreach ($expectedDirs as $name => $path) {
    if (!is_dir($path)) {
        error_log("Warning: Expected directory '$name' not found at: $path");
        
        // Try alternative locations for public directory
        if ($name === 'public') {
            $altPath = dirname($rootDir) . '/public';
            if (is_dir($altPath)) {
                error_log("Found alternative public directory at: $altPath");
                $rootDir = dirname($rootDir);
                $appDir = "$rootDir/app";
                $configDir = "$appDir/config";
                $storageDir = "$rootDir/storage";
                $logsDir = "$storageDir/logs";
                define('ROOT_DIR', $rootDir);
                define('APP_DIR', $appDir);
                define('CONFIG_DIR', $configDir);
                define('STORAGE_DIR', $storageDir);
                define('LOGS_DIR', $logsDir);
                error_log("Updated paths to use root directory: $rootDir");
                break;
            }
        }
    }
}

// Final check for critical directories
if (!is_dir($appDir) || !is_dir($configDir)) {
    // Get the current directory structure for debugging
    $dirStructure = [];
    $scanDir = dirname($rootDir);
    if (is_dir($scanDir)) {
        $dirStructure = array_diff(scandir($scanDir) ?: [], ['.', '..']);
    }
    
    die(sprintf(
        "Critical: Missing required directories.\n" .
        "- App directory: %s (%s)\n" .
        "- Config directory: %s (%s)\n" .
        "Current working directory: %s\n" .
        "Script directory: %s\n" .
        "Document root: %s\n" .
        "Available in parent directory: %s",
        $appDir, is_dir($appDir) ? 'exists' : 'missing',
        $configDir, is_dir($configDir) ? 'exists' : 'missing',
        getcwd(),
        __DIR__,
        $_SERVER['DOCUMENT_ROOT'] ?? 'Not set',
        implode(', ', $dirStructure)
    ));
}

// Log initial paths
error_log("\n=== Bootstrap Path Resolution ===");
error_log("Root Directory: $rootDir");
error_log("App Directory: $appDir");
error_log("Config Directory: $configDir");
error_log("Views Directory: $viewsDir");
error_log("Storage Directory: $storageDir");
error_log("Logs Directory: $logsDir");

// Ensure all directories exist and are writable
$requiredDirs = [
    'root' => $rootDir,
    'app' => $appDir,
    'config' => $configDir,
    'views' => $viewsDir,
    'storage' => $storageDir,
    'logs' => $logsDir
];

// Create directories if they don't exist and check permissions
foreach ($requiredDirs as $name => $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            die("Failed to create directory: $dir");
        }
        error_log("Created directory: $dir");
    }
    
    if (!is_writable($dir)) {
        if (!chmod($dir, 0755)) {
            error_log("Warning: Directory is not writable: $dir");
        }
    }
}

// Define constants with absolute paths if not already defined
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', $rootDir);
}
if (!defined('APP_DIR')) {
    define('APP_DIR', $appDir);
}
if (!defined('CONFIG_DIR')) {
    define('CONFIG_DIR', $configDir);
}
if (!defined('VIEWS_DIR')) {
    define('VIEWS_DIR', $viewsDir);
}
if (!defined('STORAGE_DIR')) {
    define('STORAGE_DIR', $storageDir);
}
if (!defined('LOGS_DIR')) {
    define('LOGS_DIR', $logsDir);
}

// Debug path information
error_log('Current working directory: ' . getcwd());
error_log('Script directory: ' . __DIR__);
error_log('ROOT_DIR: ' . ROOT_DIR);
error_log('APP_DIR: ' . APP_DIR);
error_log('CONFIG_DIR: ' . CONFIG_DIR);
error_log('VIEWS_DIR: ' . VIEWS_DIR);
error_log('STORAGE_DIR: ' . STORAGE_DIR);
error_log('LOGS_DIR: ' . LOGS_DIR);

// Define possible config file paths to check (in order of preference)
$possibleConfigPaths = [
    // Primary expected path (current config dir)
    "$configDir/config.php",
    // Common alternative paths
    "$rootDir/app/config/config.php",
    "$rootDir/config/config.php",
    // Parent directory variations
    dirname($rootDir) . "/app/config/config.php",
    dirname($rootDir) . "/config/config.php",
    // Absolute path as last resort
    "C:/Users/Keith/vscodeclone/CosmicHub.Online-1/app/config/config.php",
    // Fallback to current directory
    __DIR__ . "/config.php"
];

// Make paths unique and preserve order
$possibleConfigPaths = array_unique($possibleConfigPaths);

// Log config file check
error_log("\n=== Config File Check ===");
error_log("Looking for config file in the following locations:");

$configFile = null;
$checkedPaths = [];

// Try each possible path
foreach ($possibleConfigPaths as $path) {
    // Normalize path separators
    $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    $checkedPaths[] = $normalizedPath;
    
    $exists = file_exists($normalizedPath);
    $readable = is_readable($normalizedPath);
    
    error_log(sprintf(
        "- %s: %s, %s",
        $normalizedPath,
        $exists ? 'Exists' : 'Not found',
        $readable ? 'Readable' : 'Not readable'
    ));
    
    if ($exists && $readable) {
        $configFile = $normalizedPath;
        error_log("\nUsing config file: $configFile");
        break;
    }
}

if (!$configFile) {
    // Get directory listing of possible locations for debugging
    $dirListings = [];
    $checkedDirs = array_unique(array_map('dirname', $checkedPaths));
    
    foreach ($checkedDirs as $dir) {
        if (is_dir($dir)) {
            $files = @scandir($dir);
            if ($files !== false) {
                $dirListings[] = "Contents of $dir: " . implode(', ', array_diff($files, ['.', '..']));
            }
        }
    }
    
    $error = "\nERROR: Could not find a valid config file.\n\n" .
             "Tried the following paths:\n- " . implode("\n- ", $checkedPaths) . "\n\n" .
             "Current working directory: " . getcwd() . "\n" .
             "__DIR__: " . __DIR__ . "\n" .
             "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Not set') . "\n" .
             "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "\n\n" .
             "Directory listings:\n- " . implode("\n- ", $dirListings);
    
    error_log($error);
    die(nl2br(htmlspecialchars($error, ENT_QUOTES, 'UTF-8')));
}

// Verify the config file contains valid PHP
try {
    $config = require $configFile;
    if (!is_array($config)) {
        throw new RuntimeException("Config file did not return an array: $configFile");
    }
} catch (Throwable $e) {
    $error = "Failed to load config file: " . $e->getMessage() . "\n" .
             "File: " . $e->getFile() . " on line " . $e->getLine();
    error_log($error);
    die(nl2br(htmlspecialchars($error, ENT_QUOTES, 'UTF-8')));
}

error_log("Successfully loaded config from: $configFile");

// Load the configuration file
try {
    // Include the config file
    $config = require $configFile;
    
    // Verify the config structure
    if (!is_array($config)) {
        throw new RuntimeException('Config file did not return an array');
    }
    
    // Ensure required config sections exist
    if (!isset($config['app'])) {
        $config['app'] = [];
    }
    
    // Set default values if not specified
    $config['app'] = array_merge([
        'env' => 'production',
        'debug' => false,
        'name' => 'CosmicHub',
        'url' => 'http://localhost',
        'timezone' => 'UTC',
        'locale' => 'en'
    ], $config['app']);
    
    // Set error reporting based on debug mode
    if ($config['app']['debug']) {
        error_reporting(-1);
        ini_set('display_errors', '1');
    } else {
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        ini_set('display_errors', '0');
    }
    
    // Define constants from config if not already defined
    if (!defined('APP_ENV')) {
        define('APP_ENV', $config['app']['env'] ?? 'production');
    }
    if (!defined('APP_DEBUG')) {
        define('APP_DEBUG', $config['app']['debug'] ?? false);
    }
    
    error_log('Config loaded successfully from: ' . $configFile);
} catch (Exception $e) {
    $error = '\nERROR: Failed to load config file: ' . $e->getMessage() . "\n";
    $error .= 'File: ' . $e->getFile() . ' on line ' . $e->getLine() . "\n";
    $error .= 'Stack trace: ' . $e->getTraceAsString() . "\n";
    error_log($error);
    die(nl2br(htmlspecialchars($error)));
}

// Ensure storage and logs directories exist
if (!is_dir(STORAGE_DIR)) {
    mkdir(STORAGE_DIR, 0755, true);
}
if (!is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0755, true);
}

// Set error log file
$logFile = LOGS_DIR . DIRECTORY_SEPARATOR . 'php_errors.log';
ini_set('error_log', $logFile);

// Log PHP configuration
error_log('PHP Version: ' . phpversion());
error_log('PHP error_log: ' . ini_get('error_log'));
error_log('display_errors: ' . ini_get('display_errors'));
error_log('log_errors: ' . ini_get('log_errors'));

// Log current working directory
error_log('Current working directory: ' . getcwd());

// Log include path
error_log('Include path: ' . get_include_path());

// Log script start
error_log("\n=== Bootstrap started at " . date('Y-m-d H:i:s') . " ===\n");

// Function to log debug info
function log_debug($message) {
    global $logFile;
    $message = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    error_log($message);
}

// Set the default timezone
date_default_timezone_set('UTC');

// Log startup
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Application started\n", FILE_APPEND);

// Set up error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile) {
    $message = sprintf(
        '[%s] Error %s: %s in %s on line %d',
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    
    error_log($message);
    
    // Don't execute PHP internal error handler
    return true;
});

// Set up exception handler
set_exception_handler(function($exception) use ($logFile) {
    $message = sprintf(
        "[%s] Uncaught Exception: %s in %s on line %d\nStack trace:\n%s",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    error_log($message);
    
    if (ini_get('display_errors')) {
        http_response_code(500);
        echo '<h1>500 Internal Server Error</h1>';
        if (ini_get('display_errors') === '1') {
            echo '<pre>' . htmlspecialchars($message) . '</pre>';
        }
    }
});

// Set up autoloading
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'App\\';
    
    // Base directory for the namespace prefix
    $baseDir = APP_DIR . '/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($class, $len);
    
    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
$configPath = CONFIG_DIR . DIRECTORY_SEPARATOR . 'config.php';

// Debug information
error_log('\n=== Configuration Loading Debug ===');
error_log('Current working directory: ' . getcwd());
error_log('__DIR__: ' . __DIR__);
error_log('__FILE__: ' . __FILE__);
error_log('CONFIG_DIR: ' . CONFIG_DIR);
error_log('Config path: ' . $configPath);
error_log('Real path: ' . (($realPath = realpath($configPath)) ? $realPath : 'Not found'));
error_log('File exists: ' . (file_exists($configPath) ? 'Yes' : 'No'));
error_log('Is readable: ' . (is_readable($configPath) ? 'Yes' : 'No'));
error_log('Include path: ' . get_include_path());

// Check if the file exists using alternative methods
$alternativePaths = [
    $configPath,
    dirname(__DIR__) . '/app/config/config.php',
    __DIR__ . '/../../app/config/config.php',
    realpath(__DIR__ . '/../../app/config/config.php')
];

error_log('\n=== Checking alternative paths ===');
foreach ($alternativePaths as $i => $path) {
    $exists = file_exists($path);
    $readable = is_readable($path);
    $real = realpath($path);
    error_log(sprintf(
        "Path %d: %s\n   Exists: %s\n   Readable: %s\n   Real path: %s",
        $i + 1,
        $path,
        $exists ? 'Yes' : 'No',
        $readable ? 'Yes' : 'No',
        $real ?: 'Not found'
    ));
    
    if ($exists && $readable) {
        $configPath = $real ?: $path;
        error_log("Using config file: $configPath");
        break;
    }
}

if (!file_exists($configPath) || !is_readable($configPath)) {
    $error = "Configuration file not found or not readable at: $configPath\n";
    $error .= "Tried the following paths:\n" . implode("\n", $alternativePaths);
    error_log($error);
    die($error);
}

if (!file_exists($configPath)) {
    error_log('Config file does not exist at: ' . $configPath);
    die('Configuration file not found at: ' . $configPath);
}

if (!is_readable($configPath)) {
    error_log('Config file is not readable: ' . $configPath);
    die('Configuration file is not readable: ' . $configPath);
}

try {
    // Include the config file
    $config = require $configPath;
    
    if (!is_array($config)) {
        throw new Exception('Configuration file did not return an array');
    }
    
    $GLOBALS['config'] = $config;
    error_log('Configuration loaded successfully');
    
    // Set error reporting based on environment
    $isDev = ($config['app']['env'] ?? 'production') === 'development';
    if ($isDev) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    } else {
        error_reporting(0);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
    }
    
    // Set timezone
    date_default_timezone_set($config['app']['timezone'] ?? 'UTC');
    
    // Initialize database connection
    if (isset($config['database'])) {
        try {
            $dbConfig = $config['database'];
            $dsn = "sqlite:{$dbConfig['database']}";
            
            $options = $dbConfig['options'] ?? [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $db = new PDO($dsn, null, null, $options);
            $db->exec('PRAGMA foreign_keys = ON;');
            
            // Store the database connection in the global scope
            $GLOBALS['db'] = $db;
            
            // Set the database connection in the container if it exists
            if (class_exists('App\Core\Container')) {
                App\Core\Container::getInstance()->set('db', $db);
            }
            
            error_log('Database connection established successfully');
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            if ($isDev) {
                die('Database connection failed: ' . $e->getMessage());
            } else {
                die('Unable to connect to the database. Please try again later.');
            }
        }
    }

    // Set session configuration if available
    if (isset($config['session'])) {
        $sessionConfig = $config['session'];
        
        if (isset($sessionConfig['name'])) {
            session_name($sessionConfig['name']);
        }
        
        if (isset($sessionConfig['cookie'])) {
            $cookie = $sessionConfig['cookie'];
            session_set_cookie_params([
                'lifetime' => $cookie['lifetime'] ?? 0,
                'path' => $cookie['path'] ?? '/',
                'domain' => $cookie['domain'] ?? '',
                'secure' => $cookie['secure'] ?? isset($_SERVER['HTTPS']),
                'httponly' => $cookie['httponly'] ?? true,
                'samesite' => $cookie['samesite'] ?? 'Lax'
            ]);
        }
        
        if (isset($sessionConfig['gc_maxlifetime'])) {
            ini_set('session.gc_maxlifetime', $sessionConfig['gc_maxlifetime']);
        }
        
        if (isset($sessionConfig['save_path'])) {
            session_save_path($sessionConfig['save_path']);
        }
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
} catch (Exception $e) {
    $errorMsg = 'Error loading configuration: ' . $e->getMessage() . 
                ' in ' . $e->getFile() . ' on line ' . $e->getLine() . 
                '\nStack trace:\n' . $e->getTraceAsString();
    error_log($errorMsg);
    die('Failed to load configuration: ' . htmlspecialchars($e->getMessage()));
}

