<?php

/**
 * Service Provider Registration
 *
 * This file defines all the service providers that should be registered
 * with the application. This follows the refactoring plan to improve
 * service provider management and dependency injection.
 */

use App\Core\Config\ConfigServiceProvider;
use App\Core\Controller\ControllerServiceProvider;
use App\Core\Database\DatabaseServiceProvider;
use App\Core\Database\ModelServiceProvider;
use App\Core\Http\HttpServiceProvider;
use App\Core\Middleware\MiddlewareServiceProvider;
use App\Core\Repository\RepositoryServiceProvider;
use App\Core\Routing\RouteServiceProvider;
use App\Core\Service\ServiceServiceProvider;
use App\Core\Session\SessionServiceProvider;
use App\Core\Validation\ValidationServiceProvider;
use App\Core\View\ViewServiceProvider;
use App\Exceptions\ExceptionServiceProvider;
use App\Core\ServiceProviders\UserTokenServiceProvider;
use App\Providers\ShareableServiceProvider;
use App\Services\AdminServiceProvider;
use App\Services\CreditServiceProvider;
use App\Services\AnalyticsServiceProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Core Service Providers
    |--------------------------------------------------------------------------
    |
    | These service providers are essential for the application to function
    | and are loaded first during the bootstrap process.
    |
    */
    'core' => [
        ExceptionServiceProvider::class,
        ConfigServiceProvider::class,
        \App\Core\Events\EventServiceProvider::class,
        SessionServiceProvider::class,
        DatabaseServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Framework Service Providers
    |--------------------------------------------------------------------------
    |
    | These service providers provide framework-level functionality
    | and depend on the core providers.
    |
    */
    'framework' => [
        ModelServiceProvider::class,
        RepositoryServiceProvider::class,
        ServiceServiceProvider::class,
        ValidationServiceProvider::class,
        HttpServiceProvider::class,
        ViewServiceProvider::class,
        MiddlewareServiceProvider::class,
        RouteServiceProvider::class,
        ControllerServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Service Providers
    |--------------------------------------------------------------------------
    |
    | These service providers are specific to your application
    | and provide business logic services.
    |
    */
    'application' => [
        // Add your custom service providers here
        UserTokenServiceProvider::class,
        ShareableServiceProvider::class,
        AdminServiceProvider::class,
        CreditServiceProvider::class,
        AnalyticsServiceProvider::class,
        // App\Providers\CustomServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Service Providers
    |--------------------------------------------------------------------------
    |
    | These service providers are only loaded in development environment
    | to provide debugging and development tools.
    |
    */
    'development' => [
        // Add development-only providers here
        // App\Providers\DebugServiceProvider::class,
    ],
];