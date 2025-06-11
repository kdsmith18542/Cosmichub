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
$router->get('/email/verify', 'EmailVerificationController', 'notice')
      ->get('/email/verify/{id}/{token}', 'EmailVerificationController', 'verify')
      ->post('/email/verification-notification', 'EmailVerificationController', 'resend')
      ->get('/email/verify/resend', 'EmailVerificationController', 'showResendForm');

// Dashboard
$router->get('/dashboard', 'DashboardController', 'index');

// Report routes
$router->get('/reports', 'ReportController', 'index')
      ->get('/reports/create', 'ReportController', 'create')
      ->post('/reports', 'ReportController', 'store')
      ->get('/reports/preview', 'ReportController', 'preview')
      ->post('/reports/clear-temp', 'ReportController', 'clearTemp')
      ->get('/reports/{id}', 'ReportController', 'show')
      ->get('/reports/{id}/export/{format}', 'ReportController', 'export')
      ->delete('/reports/{id}', 'ReportController', 'destroy');
$router->get('/reports/unlock/{id}', 'ReportController', 'unlock')
      ->get('/reports/{id}/export/{format}', 'ReportController', 'export')
      ->delete('/reports/{id}', 'ReportController', 'destroy');

// Credit Routes
$router->get('/credits', 'CreditController', 'index')
      ->post('/credits/purchase', 'CreditController', 'purchase')
      ->get('/credits/history', 'CreditController', 'history');

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
$router->get('/subscription', 'SubscriptionController', 'index') // View current subscription
      ->get('/subscription/checkout/{planId}', 'PaymentController', 'subscriptionCheckoutForm') // Show subscription checkout form
      ->post('/subscription/subscribe', 'PaymentController', 'subscribeToPlan') // Create/initiate a new subscription
      ->post('/subscription/cancel', 'SubscriptionController', 'cancel'); // Cancel an existing subscription

// API routes
$router->get('/api/reports', 'Api\\ReportController', 'index')
      ->post('/api/reports', 'Api\\ReportController', 'store')
      ->get('/api/reports/{id}', 'Api\\ReportController', 'show')
      ->delete('/api/reports/{id}', 'Api\\ReportController', 'destroy');

// Daily Vibe routes
$router->get('/daily-vibe', 'DailyVibeController', 'index')
      ->post('/daily-vibe/generate', 'DailyVibeController', 'generate')
      ->get('/daily-vibe/history', 'DailyVibeController', 'history');

// Compatibility routes
$router->get('/compatibility', 'CompatibilityController', 'index')
      ->post('/compatibility/generate', 'CompatibilityController', 'generate');

// Rarity Score routes
$router->get('/rarity-score', 'RarityScoreController', 'index')
      ->post('/rarity-score/calculate', 'RarityScoreController', 'calculate')
      ->get('/rarity-score/share-link', 'RarityScoreController', 'generateShareLink');

// Celebrity Reports
$router->get('/celebrity-reports', 'CelebrityReportController', 'index')
      ->get('/celebrity-reports/create', 'CelebrityReportController', 'create')
      ->post('/celebrity-reports', 'CelebrityReportController', 'store')
      ->get('/celebrity-reports/search', 'CelebrityReportController', 'search')
      ->get('/celebrity-reports/birthdate/{month}/{day}', 'CelebrityReportController', 'getByBirthDate')
      ->get('/celebrity-reports/{slug}', 'CelebrityReportController', 'show');

// Archetype Hub routes
$router->get('/archetypes', 'ArchetypeController', 'index');
$router->get('/archetypes/{slug}', 'ArchetypeController', 'show');
$router->post('/archetypes/{slug}/comment', 'ArchetypeController', 'storeComment');

// Add more routes here

// Return the router for use in index.php
return $router;
