<?php
/**
 * Application configuration
 */

// Load environment variables
$dotenv = parse_ini_file(__DIR__ . '/../../.env');

// Application settings
define('APP_NAME', 'CosmicHub');
define('APP_ENV', $dotenv['APP_ENV'] ?? 'production');
define('APP_DEBUG', $dotenv['APP_DEBUG'] ?? false);
define('APP_URL', $dotenv['APP_URL'] ?? 'http://localhost/cosmic-hub-lamp/public');

// Database configuration
$dbConfig = require __DIR__ . '/database.php';

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set($dotenv['TIMEZONE'] ?? 'UTC');

// Database connection function
function getDBConnection() {
    global $dbConfig;
    
    static $pdo;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Could not connect to the database. Please try again later.');
        }
    }
    
    return $pdo;
}

// Helper function to get environment variable
function env($key, $default = null) {
    global $dotenv;
    return $dotenv[$key] ?? $default;
}
