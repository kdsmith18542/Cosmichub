<?php
/**
 * Front controller for the application
 * 
 * All requests are redirected through this file
 */

// Define the application path
$appPath = dirname(__DIR__);

// Load the bootstrap file
require_once $appPath . '/bootstrap.php';

// Include the router
require_once APP_PATH . '/routes/web.php';

// Handle the request
$router = new \App\Libraries\Router();
$router->dispatch();
