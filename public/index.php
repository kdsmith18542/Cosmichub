<?php
/**
 * Front controller for the application
 * 
 * All requests are redirected through this file
 */

// Define the application path
$appPath = dirname(__DIR__);

// Debug: Log the app path
error_log("Application path: " . $appPath);

// Set the application path constant if not already defined
if (!defined('APP_PATH')) {
    define('APP_PATH', $appPath);
}

// Load the bootstrap file
require_once $appPath . '/bootstrap.php';

// Include and get the router instance with routes
$webRoutesPath = $appPath . '/app/routes/web.php';
if (!file_exists($webRoutesPath)) {
    die('Routes file not found at: ' . $webRoutesPath);
}
$router = require $webRoutesPath;

// Handle the request
$router->dispatch();
