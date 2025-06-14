<?php

/**
 * Local Environment Application Configuration
 * 
 * Configuration overrides for local development environment
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
    'env' => 'local',

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
    'url' => env('APP_URL', 'http://localhost:8000'),

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
    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Local Development Settings
    |--------------------------------------------------------------------------
    |
    | These settings are specific to local development environment
    |
    */
    'local' => [
        'hot_reload' => true,
        'asset_versioning' => false,
        'minify_assets' => false,
        'cache_views' => false,
        'cache_config' => false,
        'cache_routes' => false,
        'optimize_autoloader' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Tools
    |--------------------------------------------------------------------------
    |
    | Enable development tools and debugging features
    |
    */
    'dev_tools' => [
        'telescope' => env('TELESCOPE_ENABLED', true),
        'debugbar' => env('DEBUGBAR_ENABLED', true),
        'clockwork' => env('CLOCKWORK_ENABLED', false),
        'profiler' => env('PROFILER_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Reporting
    |--------------------------------------------------------------------------
    |
    | Configure error reporting for local development
    |
    */
    'error_reporting' => [
        'level' => E_ALL,
        'display_errors' => true,
        'display_startup_errors' => true,
        'log_errors' => true,
        'ignore_repeated_errors' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance-related settings for local development
    |
    */
    'performance' => [
        'opcache_enabled' => false,
        'query_cache' => false,
        'view_cache' => false,
        'route_cache' => false,
        'config_cache' => false,
    ],
];