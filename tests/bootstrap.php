<?php

/*
|--------------------------------------------------------------------------
| Test Bootstrap
|--------------------------------------------------------------------------
|
| This file is used to bootstrap the testing environment before running
| the test suite. It sets up the application environment and loads
| necessary dependencies for testing.
|
*/

// Set the testing environment
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'true';

// Define base paths
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', ROOT_DIR . '/app');
define('CONFIG_DIR', ROOT_DIR . '/config');
define('STORAGE_DIR', ROOT_DIR . '/storage');
define('LOGS_DIR', STORAGE_DIR . '/logs');

// Load Composer autoloader
require_once ROOT_DIR . '/vendor/autoload.php';

// Register Tests namespace autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'Tests\\') === 0) {
        $file = __DIR__ . '/' . str_replace(['Tests\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Load environment variables for testing
if (file_exists(ROOT_DIR . '/.env.testing')) {
    $lines = file(ROOT_DIR . '/.env.testing', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Set up error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Create logs directory if it doesn't exist
if (!is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0755, true);
}

// Set error log for testing
ini_set('error_log', LOGS_DIR . '/test_errors.log');

// Load the application for testing
require_once ROOT_DIR . '/bootstrap/app.php';