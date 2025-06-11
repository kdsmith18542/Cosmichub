<?php
/**
 * Application Configuration
 * 
 * This file returns an array of configuration settings for the application.
 * Environment variables are loaded from the .env file if it exists.
 */

// Load environment variables
require_once __DIR__ . '/../helpers.php';

// Load .env file if it exists
if (file_exists(__DIR__ . '/../../.env')) {
    loadEnv(__DIR__ . '/../../.env');
}

// Application settings
return [
    'app' => [
        'name' => env('APP_NAME', 'CosmicHub'),
        'env' => env('APP_ENV', 'production'),
        'debug' => env('APP_DEBUG', false),
        'url' => env('APP_URL', 'http://cosmichub.local'),
        'timezone' => env('TIMEZONE', 'UTC'),
        'date_format' => env('DATE_FORMAT', 'm/d/Y'), // USA Date Format (MM/DD/YYYY)
        'time_format' => env('TIME_FORMAT', 'h:i A'),   // 12-hour Time Format (e.g., 03:30 PM)
    ],
    
    // Database configuration
    'database' => require __DIR__ . '/database.php',
    
    // Session configuration
    'session' => [
        'driver' => env('SESSION_DRIVER', 'file'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'cookie' => [
            'path' => '/',
            'domain' => env('SESSION_DOMAIN', null),
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ],
    ],
];
