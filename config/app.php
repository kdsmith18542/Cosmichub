<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'CosmicHub'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */
    'env' => env('APP_ENV', 'production'),

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
    'debug' => env('APP_DEBUG', false),

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
    | Application Version
    |--------------------------------------------------------------------------
    |
    | This value represents the version of your application. This is used
    | for display purposes and can be helpful for debugging and support.
    |
    */
    'version' => env('APP_VERSION', '1.0.0'),

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
    | Asset URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the view to properly generate URLs for assets.
    | This will be used by the global asset function. You should set this
    | to the root of your application.
    |
    */
    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Features
    |--------------------------------------------------------------------------
    |
    | Here you may specify which features are enabled for your application.
    | These features can be toggled on or off based on your environment
    | or specific deployment requirements.
    |
    */
    'features' => [
        'registration' => env('FEATURE_REGISTRATION', true),
        'password_reset' => env('FEATURE_PASSWORD_RESET', true),
        'email_verification' => env('FEATURE_EMAIL_VERIFICATION', false),
        'two_factor_auth' => env('FEATURE_TWO_FACTOR_AUTH', false),
        'api_access' => env('FEATURE_API_ACCESS', true),
        'file_uploads' => env('FEATURE_FILE_UPLOADS', true),
        'social_login' => env('FEATURE_SOCIAL_LOGIN', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage the "maintenance mode" status of the application.
    |
    */
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */
    'providers' => [
        /*
         * CosmicHub Core Service Providers...
         */
        App\Core\Config\ConfigServiceProvider::class,
        App\Core\Logging\LoggingServiceProvider::class,
        App\Core\Routing\RouteServiceProvider::class,
        App\Core\Controller\ControllerServiceProvider::class,
        App\Core\Middleware\MiddlewareServiceProvider::class,
        App\Core\Database\DatabaseServiceProvider::class,
        App\Core\Model\ModelServiceProvider::class,
        App\Core\Repository\RepositoryServiceProvider::class,
        App\Core\Service\ServiceServiceProvider::class,
        App\Core\Session\SessionServiceProvider::class,
        App\Core\View\ViewServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */
    'aliases' => [
        'App' => App\Core\Application::class,
        'Config' => App\Core\Config\Config::class,
        'Route' => App\Core\Routing\Route::class,
        'DB' => App\Core\Database\DatabaseManager::class,
        'Session' => App\Core\Session\Session::class,
        'View' => App\Core\View\ViewFactory::class,
    ],
];
