<?php

/**
 * Staging Environment Application Configuration
 * 
 * Configuration overrides for staging environment
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
    'env' => 'staging',

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
    'debug' => env('APP_DEBUG', true),

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
    'url' => env('APP_URL', 'https://staging.cosmichub.com'),

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
    | Staging Environment Settings
    |--------------------------------------------------------------------------
    |
    | These settings are specific to staging environment
    |
    */
    'staging' => [
        'mirror_production' => true,
        'debug_toolbar' => env('DEBUG_TOOLBAR_ENABLED', true),
        'query_logging' => env('QUERY_LOGGING_ENABLED', true),
        'performance_monitoring' => env('PERFORMANCE_MONITORING', true),
        'error_tracking' => env('ERROR_TRACKING_ENABLED', true),
        'feature_flags' => env('FEATURE_FLAGS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security settings for staging (less strict than production)
    |
    */
    'security' => [
        'force_https' => env('FORCE_HTTPS', true),
        'hsts_enabled' => env('HSTS_ENABLED', false),
        'hsts_max_age' => env('HSTS_MAX_AGE', 3600), // 1 hour
        'content_security_policy' => env('CSP_ENABLED', false),
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'basic_auth' => env('STAGING_BASIC_AUTH', false),
        'ip_whitelist' => env('STAGING_IP_WHITELIST', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Reporting
    |--------------------------------------------------------------------------
    |
    | Configure error reporting for staging
    |
    */
    'error_reporting' => [
        'level' => E_ALL & ~E_NOTICE,
        'display_errors' => env('DISPLAY_ERRORS', true),
        'display_startup_errors' => true,
        'log_errors' => true,
        'ignore_repeated_errors' => false,
        'detailed_errors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance-related settings for staging
    |
    */
    'performance' => [
        'opcache_enabled' => env('OPCACHE_ENABLED', true),
        'query_cache' => env('QUERY_CACHE_ENABLED', true),
        'view_cache' => env('VIEW_CACHE_ENABLED', true),
        'route_cache' => env('ROUTE_CACHE_ENABLED', false),
        'config_cache' => env('CONFIG_CACHE_ENABLED', false),
        'session_cache' => env('SESSION_CACHE_ENABLED', true),
        'compression_enabled' => env('COMPRESSION_ENABLED', true),
        'gzip_level' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Tools
    |--------------------------------------------------------------------------
    |
    | Enable development tools for staging testing
    |
    */
    'dev_tools' => [
        'telescope' => env('TELESCOPE_ENABLED', true),
        'debugbar' => env('DEBUGBAR_ENABLED', true),
        'clockwork' => env('CLOCKWORK_ENABLED', true),
        'profiler' => env('PROFILER_ENABLED', true),
        'query_detector' => env('QUERY_DETECTOR_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing and QA Settings
    |--------------------------------------------------------------------------
    |
    | Settings for testing and quality assurance
    |
    */
    'testing' => [
        'automated_tests' => env('AUTOMATED_TESTS_ENABLED', true),
        'browser_tests' => env('BROWSER_TESTS_ENABLED', true),
        'api_tests' => env('API_TESTS_ENABLED', true),
        'performance_tests' => env('PERFORMANCE_TESTS_ENABLED', true),
        'security_tests' => env('SECURITY_TESTS_ENABLED', true),
        'accessibility_tests' => env('ACCESSIBILITY_TESTS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Logging
    |--------------------------------------------------------------------------
    |
    | Staging monitoring and logging settings
    |
    */
    'monitoring' => [
        'apm_enabled' => env('APM_ENABLED', true),
        'metrics_enabled' => env('METRICS_ENABLED', true),
        'health_checks' => env('HEALTH_CHECKS_ENABLED', true),
        'performance_monitoring' => env('PERFORMANCE_MONITORING', true),
        'error_tracking' => env('ERROR_TRACKING_ENABLED', true),
        'log_level' => env('LOG_LEVEL', 'debug'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flag settings for staging
    |
    */
    'features' => [
        'new_ui' => env('FEATURE_NEW_UI', true),
        'api_v2' => env('FEATURE_API_V2', true),
        'advanced_search' => env('FEATURE_ADVANCED_SEARCH', true),
        'real_time_notifications' => env('FEATURE_REAL_TIME_NOTIFICATIONS', true),
        'experimental_features' => env('FEATURE_EXPERIMENTAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Staging rate limiting settings (more lenient than production)
    |
    */
    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),
        'api_requests_per_minute' => env('API_RATE_LIMIT', 120),
        'web_requests_per_minute' => env('WEB_RATE_LIMIT', 240),
        'strict_mode' => false,
        'bypass_for_testing' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data and Storage
    |--------------------------------------------------------------------------
    |
    | Data and storage settings for staging
    |
    */
    'data' => [
        'use_production_data' => env('USE_PRODUCTION_DATA', false),
        'anonymize_data' => env('ANONYMIZE_DATA', true),
        'data_retention_days' => env('DATA_RETENTION_DAYS', 30),
        'backup_enabled' => env('BACKUP_ENABLED', true),
        'backup_frequency' => env('BACKUP_FREQUENCY', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment Settings
    |--------------------------------------------------------------------------
    |
    | Settings related to deployment and CI/CD
    |
    */
    'deployment' => [
        'auto_deploy' => env('AUTO_DEPLOY_ENABLED', true),
        'deploy_notifications' => env('DEPLOY_NOTIFICATIONS', true),
        'rollback_enabled' => env('ROLLBACK_ENABLED', true),
        'health_check_after_deploy' => env('HEALTH_CHECK_AFTER_DEPLOY', true),
        'smoke_tests_after_deploy' => env('SMOKE_TESTS_AFTER_DEPLOY', true),
    ],
];