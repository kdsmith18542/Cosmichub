<?php

/**
 * Production Environment Application Configuration
 * 
 * Configuration overrides for production environment
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes.
    |
    */
    'env' => 'production',

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */
    'debug' => false,

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */
    'url' => env('APP_URL', 'https://cosmichub.com'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */
    'locale' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the encryption service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */
    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Production Optimization Settings
    |--------------------------------------------------------------------------
    |
    | These settings are optimized for production performance
    |
    */
    'production' => [
        'asset_versioning' => true,
        'minify_assets' => true,
        'cache_views' => true,
        'cache_config' => true,
        'cache_routes' => true,
        'optimize_autoloader' => true,
        'preload_enabled' => env('PHP_PRELOAD_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Enhanced security settings for production
    |
    */
    'security' => [
        'force_https' => env('FORCE_HTTPS', true),
        'hsts_enabled' => env('HSTS_ENABLED', true),
        'hsts_max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
        'content_security_policy' => env('CSP_ENABLED', true),
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Reporting
    |--------------------------------------------------------------------------
    |
    | Configure error reporting for production
    |
    */
    'error_reporting' => [
        'level' => E_ERROR | E_WARNING | E_PARSE,
        'display_errors' => false,
        'display_startup_errors' => false,
        'log_errors' => true,
        'ignore_repeated_errors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance-related settings for production
    |
    */
    'performance' => [
        'opcache_enabled' => true,
        'query_cache' => true,
        'view_cache' => true,
        'route_cache' => true,
        'config_cache' => true,
        'session_cache' => true,
        'compression_enabled' => true,
        'gzip_level' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Logging
    |--------------------------------------------------------------------------
    |
    | Production monitoring and logging settings
    |
    */
    'monitoring' => [
        'apm_enabled' => env('APM_ENABLED', false),
        'metrics_enabled' => env('METRICS_ENABLED', true),
        'health_checks' => env('HEALTH_CHECKS_ENABLED', true),
        'performance_monitoring' => env('PERFORMANCE_MONITORING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Production rate limiting settings
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'api_requests_per_minute' => env('API_RATE_LIMIT', 60),
        'web_requests_per_minute' => env('WEB_RATE_LIMIT', 120),
        'strict_mode' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | Production maintenance mode settings
    |
    */
    'maintenance' => [
        'allowed_ips' => env('MAINTENANCE_ALLOWED_IPS', ''),
        'retry_after' => env('MAINTENANCE_RETRY_AFTER', 3600),
        'secret' => env('MAINTENANCE_SECRET'),
    ],
];