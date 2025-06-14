<?php

/**
 * Testing Environment Application Configuration
 * 
 * Configuration overrides for testing environment
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
    'env' => 'testing',

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
    'debug' => true,

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
    'url' => env('APP_URL', 'http://localhost'),

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
    'timezone' => 'UTC',

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
    'locale' => 'en',

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
    'fallback_locale' => 'en',

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
    'key' => env('APP_KEY', 'base64:' . base64_encode('testing-key-32-characters-long')),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Testing Environment Settings
    |--------------------------------------------------------------------------
    |
    | These settings are specific to testing environment
    |
    */
    'testing' => [
        'database_transactions' => true,
        'refresh_database' => true,
        'seed_database' => true,
        'fake_notifications' => true,
        'fake_mail' => true,
        'fake_events' => false,
        'fake_queue' => true,
        'disable_middleware' => [],
        'mock_external_apis' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance settings optimized for testing
    |
    */
    'performance' => [
        'opcache_enabled' => false,
        'query_cache' => false,
        'view_cache' => false,
        'route_cache' => false,
        'config_cache' => false,
        'session_cache' => false,
        'parallel_testing' => env('PARALLEL_TESTING', false),
        'memory_limit' => '512M',
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Reporting
    |--------------------------------------------------------------------------
    |
    | Configure error reporting for testing
    |
    */
    'error_reporting' => [
        'level' => E_ALL,
        'display_errors' => true,
        'display_startup_errors' => true,
        'log_errors' => true,
        'ignore_repeated_errors' => false,
        'throw_on_error' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Database Settings
    |--------------------------------------------------------------------------
    |
    | Database settings for testing
    |
    */
    'database' => [
        'default_connection' => 'testing',
        'use_transactions' => true,
        'migrate_fresh' => true,
        'seed_before_tests' => true,
        'cleanup_after_tests' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Logging Settings
    |--------------------------------------------------------------------------
    |
    | Logging configuration for tests
    |
    */
    'logging' => [
        'default_channel' => 'testing',
        'log_level' => 'debug',
        'log_sql_queries' => true,
        'log_http_requests' => true,
        'log_exceptions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Coverage Settings
    |--------------------------------------------------------------------------
    |
    | Code coverage settings for testing
    |
    */
    'coverage' => [
        'enabled' => env('COVERAGE_ENABLED', false),
        'driver' => env('COVERAGE_DRIVER', 'xdebug'),
        'output_format' => env('COVERAGE_FORMAT', 'html'),
        'output_directory' => env('COVERAGE_DIR', 'tests/coverage'),
        'minimum_threshold' => env('COVERAGE_THRESHOLD', 80),
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Fixtures and Factories
    |--------------------------------------------------------------------------
    |
    | Settings for test data generation
    |
    */
    'fixtures' => [
        'auto_load' => true,
        'directory' => 'tests/fixtures',
        'use_factories' => true,
        'factory_directory' => 'database/factories',
        'faker_locale' => 'en_US',
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Assertions and Expectations
    |--------------------------------------------------------------------------
    |
    | Settings for test assertions
    |
    */
    'assertions' => [
        'strict_mode' => true,
        'deprecation_handling' => 'strict',
        'warning_handling' => 'strict',
        'notice_handling' => 'strict',
        'timeout' => 30, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Mocking Settings
    |--------------------------------------------------------------------------
    |
    | Settings for mocking external services
    |
    */
    'mocking' => [
        'http_client' => true,
        'file_system' => false,
        'cache' => false,
        'queue' => true,
        'mail' => true,
        'notifications' => true,
        'events' => false,
        'external_apis' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Settings for cleaning up after tests
    |
    */
    'cleanup' => [
        'clear_cache' => true,
        'clear_logs' => false,
        'clear_sessions' => true,
        'clear_temp_files' => true,
        'reset_singletons' => true,
    ],
];