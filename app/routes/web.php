<?php
/**
 * Web routes for the application
 */

use App\Libraries\PHPRouter;

// Create a new router instance
$router = new PHPRouter();

// Home page - Viral Landing Page
$router->get('/', 'HomeController', 'index');

// Viral Landing Page Routes
$router->post('/generate-snapshot', 'HomeController', 'generateSnapshot')
      ->get('/cosmic-snapshot/{slug}', 'HomeController', 'showSnapshot')
      ->get('/cosmic-snapshot/{slug}/download-pdf', 'HomeController', 'downloadPDF')
      ->get('/cosmic-blueprint/{slug}', 'HomeController', 'showBlueprint')
      ->get('/cosmic/{slug}', 'HomeController', 'showSnapshot'); // SEO-friendly alias

// Authentication routes
$router->get('/login', 'AuthController', 'loginForm')
      ->post('/login', 'AuthController', 'login')
      ->get('/register', 'AuthController', 'registerForm')
      ->post('/register', 'AuthController', 'register')
      ->get('/logout', 'AuthController', 'logout');

// Password Reset Routes
$router->get('/password/reset', 'AuthController', 'showLinkRequestForm');
$router->post('/password/email', 'AuthController', 'sendResetLinkEmail');
$router->get('/password/reset/{token}', 'AuthController', 'showResetForm');
$router->post('/password/update', 'AuthController', 'resetPassword');

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
$router->get('/archetypes/create', 'ArchetypeController', 'create'); // Show form to create archetype
$router->post('/archetypes/store', 'ArchetypeController', 'store');    // Store new archetype
$router->get('/archetypes/{slug}', 'ArchetypeController', 'show');
$router->post('/archetypes/{slug}/comment', 'ArchetypeController', 'storeComment');

// Animated Shareables
$router->post('/shareables/generate-cosmic', 'ShareableController', 'generateCosmic')
      ->post('/shareables/generate-compatibility', 'ShareableController', 'generateCompatibility')
      ->post('/shareables/generate-rarity', 'ShareableController', 'generateRarity')
      ->get('/shareables/{id}', 'ShareableController', 'view')
      ->get('/shareables/{id}/download', 'ShareableController', 'download');

// Gift Routes
$router->get('/gift', 'GiftController', 'index');
$router->post('/gift/purchase', 'GiftController', 'purchase');
$router->get('/gift/redeem', 'GiftController', 'redeem');
$router->post('/gift/process-redemption', 'GiftController', 'processRedemption');
$router->get('/gift/success', 'GiftController', 'success');

// Phase 3: Beta Testing Routes
// Unified Admin Dashboard Routes
$router->get('/admin', 'AdminController', 'dashboard');
$router->get('/admin/dashboard', 'AdminController', 'dashboard');
$router->get('/admin/api', 'AdminController', 'api');
$router->get('/admin/export', 'AdminController', 'export');

// Analytics Routes (Legacy - redirects to unified admin)
$router->get('/analytics/dashboard', 'AnalyticsController', 'dashboard');
$router->get('/analytics/api/metrics', 'AnalyticsController', 'getMetrics');
$router->post('/analytics/track', 'AnalyticsController', 'trackEvent');
$router->get('/analytics/reports/daily', 'AnalyticsController', 'dailyReport');
$router->get('/analytics/export', 'AnalyticsController', 'exportData');

// Feedback Routes
$router->get('/feedback', 'FeedbackController', 'index');
$router->post('/feedback/submit', 'FeedbackController', 'submit');
$router->get('/feedback/widget', 'FeedbackController', 'widget');
$router->get('/feedback/admin', 'FeedbackController', 'admin');
$router->post('/feedback/admin/update-status', 'FeedbackController', 'updateStatus');
$router->post('/feedback/admin/respond', 'FeedbackController', 'respond');
$router->get('/feedback/admin/details/{id}', 'FeedbackController', 'getDetails');

// Health Check and Monitoring Routes
$router->get('/health', 'AnalyticsController', 'healthCheck');
$router->get('/status', 'AnalyticsController', 'systemStatus');
$router->get('/metrics', 'AnalyticsController', 'systemMetrics');

// SEO and Sitemap Routes
$router->get('/sitemap.xml', 'SitemapController', 'generateSitemap');
$router->get('/sitemap-index.xml', 'SitemapController', 'generateSitemapIndex');
$router->get('/sitemap-celebrities.xml', 'SitemapController', 'generateCelebritySitemap');

// Add more routes here

// Return the router for use in index.php
return $router;
