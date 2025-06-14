<?php

/**
 * Main Application Configuration
 *
 * This file contains the main configuration array for the CosmicHub application.
 * It consolidates all configuration settings in a structured format.
 */



return [
    'app' => [
        'name' => env('APP_NAME', 'CosmicHub'),
        'env' => env('APP_ENV', 'production'),
        'debug' => env('APP_DEBUG', false),
        'url' => env('APP_URL', 'http://cosmichub.local'),
        'timezone' => env('APP_TIMEZONE', 'UTC'),
        'key' => env('APP_KEY', ''),
        'cipher' => 'AES-256-CBC',
    ],

    'database' => [
        'driver' => env('DB_DRIVER', 'sqlite'),
        'database' => env('DB_DATABASE', __DIR__ . '/../database/database.sqlite'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'username' => env('DB_USERNAME', ''),
        'password' => env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix' => env('DB_PREFIX', ''),
    ],

    'session' => [
        'driver' => env('SESSION_DRIVER', 'file'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
        'encrypt' => env('SESSION_ENCRYPT', false),
        'files' => storage_path('framework/sessions'),
        'connection' => env('SESSION_CONNECTION', null),
        'table' => env('SESSION_TABLE', 'sessions'),
        'store' => env('SESSION_STORE', null),
        'lottery' => [2, 100],
        'cookie' => [
            'name' => env('SESSION_COOKIE', 'cosmichub_session'),
            'path' => '/',
            'domain' => env('SESSION_DOMAIN', null),
            'secure' => env('SESSION_SECURE_COOKIE', false),
            'httponly' => true,
            'samesite' => 'lax',
        ],
    ],

    'security' => [
        'hash_cost' => env('HASH_COST', 12),
        'password_timeout' => env('PASSWORD_TIMEOUT', 10800),
    ],

    'mail' => [
        'default' => env('MAIL_MAILER', 'smtp'),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@cosmichub.local'),
            'name' => env('MAIL_FROM_NAME', 'CosmicHub'),
        ],
    ],
];
