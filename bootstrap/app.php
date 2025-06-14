<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Define the application start time

// Define base paths (only if not already defined)
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', realpath(__DIR__ . '/..'));
}
if (!defined('APP_DIR')) {
    define('APP_DIR', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app'));
}
if (!defined('CONFIG_DIR')) {
    define('CONFIG_DIR', ROOT_DIR . '/config');
}
if (!defined('STORAGE_DIR')) {
    define('STORAGE_DIR', ROOT_DIR . '/storage');
}
if (!defined('LOGS_DIR')) {
    define('LOGS_DIR', STORAGE_DIR . '/logs');
}

// Load helper functions from bootstrap directory first
require_once __DIR__ . '/helpers.php';

// Require the Composer autoloader
try {
    
    if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require ROOT_DIR . '/vendor/autoload.php';
}
    

    
} catch (Throwable $e) {
            if ($container && $container->has(LoggerInterface::class)) {
            $container->make(LoggerInterface::class)->error('Autoloader failed to load: ' . $e->getMessage());
        } else {
            error_log('Autoloader failed to load: ' . $e->getMessage());
        }
    throw $e;
}

use App\Core\Application;
use App\Core\Container;
use App\Core\Config\Config;
use App\Core\Config\Exceptions\ConfigException;
use App\Core\Logging\LoggerInterface;

// Load helper functions from app directory (contains loadEnv and the primary env function)
if (file_exists(APP_DIR . '/helpers.php')) {
    require_once APP_DIR . '/helpers.php';
} else {
    // Fallback or error if app/helpers.php is critical and not found
    // Use a temporary logger or error_log if the main logger isn't available yet
    $container = null; // Initialize $container to null or an appropriate default
    if ($container && $container->has(LoggerInterface::class)) {
        $container->make(LoggerInterface::class)->warning('Warning: app/helpers.php not found. Environment loading might be affected.');
    } else {
                // This error_log is intentionally kept as a last resort if the logger isn't available.
        // It indicates a critical early bootstrap failure.
        error_log('Warning: app/helpers.php not found. Environment loading might be affected.');
    }
}
// Load environment configuration using loadEnv from app/helpers.php
$envFile = ROOT_DIR . '/.env';
if (function_exists('loadEnv')) {
    loadEnv($envFile);
} else {
    // Fallback to basic .env parsing if loadEnv is not available (e.g., app/helpers.php failed to load)
    if (file_exists($envFile)) {
        $envVars = parse_ini_file($envFile);
        if ($envVars) {
            foreach ($envVars as $key => $value) {
                if (!getenv($key) && !isset($_ENV[$key])) { // Check $_ENV as well
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
    }
}

// Set error reporting based on environment
$isDebug = getenv('APP_DEBUG') === 'true' || getenv('APP_ENV') === 'development';
if ($isDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '1');
}

// Ensure logs directory exists (Monolog will use this)
if (!is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0755, true);
}
ini_set('log_errors', '1'); // Keep PHP's error logging enabled

// Exception and error handling will be managed by the Application's
// ExceptionHandler, which should be registered and configured via a service provider
// or the core Application setup to use the 'logger' (Monolog) service.



// Create a new application instance
$app = Application::getInstance();
$app->setBasePath(ROOT_DIR);

// Configuration will be loaded by ConfigServiceProvider
$container = $app->getContainer();

// Bootstrap the application with service providers (this will register and boot EnhancedConfigServiceProvider)
$app->bootstrap();

// Get the configuration service from the container (loaded by EnhancedConfigServiceProvider)
try {
    $configService = $container->make('config');
    $logger = $container->has(LoggerInterface::class) ? $container->make(LoggerInterface::class) : null;

    if ($isDebug) {
        if ($logger) {
            $logger->info('Enhanced Bootstrap: Application bootstrap completed successfully');
        }
    } else {
                // This error_log is intentionally kept as a last resort if the logger isn't available.
        // It indicates a critical early bootstrap failure.
        error_log('Enhanced Bootstrap: Configuration loaded successfully via EnhancedConfigServiceProvider');
    }

} catch (Exception $e) {
    if ($logger) {
        $logger->error('Enhanced Bootstrap: Configuration initialization failed: ' . $e->getMessage());
    }
    throw new RuntimeException('Configuration initialization failed: ' . $e->getMessage());
}


// Set timezone using the Config service
date_default_timezone_set($configService->get('app.timezone', 'UTC'));

// Initialize database connection with enhanced error handling using the Config service
$dbSettings = $configService->get('database');
if ($dbSettings) {
    try {
        // $dbConfig is already the array of database settings
        $dsn = "sqlite:{$dbSettings['database']}";

        $options = $dbSettings['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $db = new PDO($dsn, null, null, $options);
        if (isset($dbSettings['foreign_key_constraints']) && $dbSettings['foreign_key_constraints']) {
             $db->exec('PRAGMA foreign_keys = ON;');
        }

        $container->instance('db', $db);
    } catch (PDOException $e) {
        if ($logger) {
            $logger->error('Enhanced Bootstrap: Database connection failed: ' . $e->getMessage());
        } else {
                    // This error_log is intentionally kept as a last resort if the logger isn't available.
        // It indicates a critical early bootstrap failure.
        error_log('Enhanced Bootstrap: Database connection failed: ' . $e->getMessage());
        }
        if ($isDebug) {
            throw $e;
        } else {
            throw new RuntimeException('Unable to connect to the database');
        }
    }
}

// Configure session with enhanced security using the Config service
$sessionSettings = $configService->get('session');
if ($sessionSettings) {
    $sessionConfig = $sessionSettings; // Use the retrieved session settings
    
    // Only set session parameters if session is not already active
    if (session_status() === PHP_SESSION_NONE) {
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
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Service providers are already bootstrapped above to get config

if ($isDebug) {
    if ($logger) {
        $logger->info('Enhanced Bootstrap: Application bootstrap completed successfully');
    }
} else {
        // This error_log is intentionally kept as a last resort if the logger isn't available.
    // It indicates a critical early bootstrap failure.
    error_log('Enhanced Bootstrap: Application bootstrap completed successfully');
}

// Return the application
return $app;