<?php
/**
 * Web routes for the application
 */

use App\Libraries\Router;

// Create a new router instance
$router = new Router();

// Home page
$router->get('/', 'HomeController', 'index');

// Authentication routes
$router->get('/login', 'AuthController', 'loginForm')
      ->post('/login', 'AuthController', 'login')
      ->get('/register', 'AuthController', 'registerForm')
      ->post('/register', 'AuthController', 'register')
      ->get('/logout', 'AuthController', 'logout');

// Email Verification Routes
$router->get('/email/verify', 'VerificationController', 'show')
      ->get('/email/verify/{id}/{token}', 'VerificationController', 'verify')
      ->post('/email/verification-notification', 'VerificationController', 'resend');

// Dashboard
$router->get('/dashboard', 'DashboardController', 'index');

// Report routes
$router->get('/reports', 'ReportController', 'index')
      ->get('/reports/create', 'ReportController', 'create')
      ->post('/reports', 'ReportController', 'store')
      ->get('/reports/{id}', 'ReportController', 'show')
      ->get('/reports/{id}/export/{format}', 'ReportController', 'export');

// User profile
$router->get('/profile', 'UserController', 'profile')
      ->post('/profile', 'UserController', 'updateProfile');

// Payment routes
$router->get('/payment/plans', 'PaymentController', 'plans')
      ->get('/payment/checkout/{planId}', 'PaymentController', 'checkout')
      ->post('/payment/process', 'PaymentController', 'process')
      ->get('/payment/success', 'PaymentController', 'success')
      ->get('/payment/cancel', 'PaymentController', 'cancel')
      ->get('/payment/history', 'PaymentController', 'history')
      ->post('/payment/webhook', 'PaymentController', 'webhook');

// Subscription routes
$router->get('/subscription', 'SubscriptionController', 'index')
      ->post('/subscription/subscribe', 'SubscriptionController', 'subscribe')
      ->get('/subscription/cancel', 'SubscriptionController', 'cancel');

// API routes
$router->get('/api/reports', 'Api\\ReportController', 'index')
      ->post('/api/reports', 'Api\\ReportController', 'store')
      ->get('/api/reports/{id}', 'Api\\ReportController', 'show')
      ->delete('/api/reports/{id}', 'Api\\ReportController', 'destroy');

// Daily Vibe routes
$router->get('/daily-vibe', 'DailyVibeController', 'index')
      ->post('/daily-vibe/generate', 'DailyVibeController', 'generate')
      ->get('/daily-vibe/history', 'DailyVibeController', 'history');

// Return the router for use in index.php
return $router;
