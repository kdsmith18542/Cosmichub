<?php
/**
 * Web routes for the application
 */

use App\Libraries\Router;

// Create a new router instance
$router = new Router();

// Home page
$router->addRoute('GET', '/', 'HomeController', 'index');

// Authentication routes
$router->addRoute('GET', '/login', 'AuthController', 'showLogin');
$router->addRoute('POST', '/login', 'AuthController', 'login');
$router->addRoute('GET', '/register', 'AuthController', 'showRegister');
$router->addRoute('POST', '/register', 'AuthController', 'register');
$router->addRoute('GET', '/logout', 'AuthController', 'logout');

// Report routes
$router->addRoute('GET', '/reports', 'ReportController', 'index');
$router->addRoute('GET', '/reports/create', 'ReportController', 'create');
$router->addRoute('POST', '/reports', 'ReportController', 'store');
$router->addRoute('GET', '/reports/{id}', 'ReportController', 'show');
$router->addRoute('GET', '/reports/{id}/export/{format}', 'ReportController', 'export');

// User profile
$router->addRoute('GET', '/profile', 'UserController', 'profile');
$router->addRoute('POST', '/profile', 'UserController', 'updateProfile');

// Subscription routes
$router->addRoute('GET', '/subscription', 'SubscriptionController', 'index');
$router->addRoute('POST', '/subscription/subscribe', 'SubscriptionController', 'subscribe');
$router->addRoute('GET', '/subscription/cancel', 'SubscriptionController', 'cancel');

// API routes
$router->addRoute('GET', '/api/reports', 'Api\\ReportController', 'index');
$router->addRoute('POST', '/api/reports', 'Api\\ReportController', 'store');
$router->addRoute('GET', '/api/reports/{id}', 'Api\\ReportController', 'show');
$router->addRoute('DELETE', '/api/reports/{id}', 'Api\\ReportController', 'destroy');

// Return the router for use in index.php
return $router;
