<?php
/**
 * Front Controller for CosmicHub Application
 * 
 * This file serves as the entry point for all web requests.
 * Compatible with shared Linux hosting and DirectAdmin.
 */

// Prevent direct access to this file from browser address bar
if (!isset($_SERVER['REQUEST_URI'])) {
    die('Direct access not allowed');
}

// Set error reporting for production
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Define the document root and application root for Strategy 2
define('DOCUMENT_ROOT', __DIR__);
define('APP_ROOT', dirname(__DIR__));

// Include the bootstrap file
require_once APP_ROOT . '/bootstrap.php';

// Start the application
try {
    // Initialize the router
    require_once APP_DIR . '/libraries/Router.php';
    $router = new \App\Libraries\Router();
    
    // Load routes
    require_once APP_DIR . '/routes/web.php';
    
    // Handle the request
    $router->handleRequest();
    
} catch (Exception $e) {
    // Log the error
    error_log('Application Error: ' . $e->getMessage());
    
    // Show user-friendly error page
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Service Temporarily Unavailable</title></head><body><h1>Service Temporarily Unavailable</h1><p>We are currently experiencing technical difficulties. Please try again later.</p></body></html>';
}