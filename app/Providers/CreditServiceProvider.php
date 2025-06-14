<?php

namespace App\Providers;

use App\Core\ServiceProvider;
use App\Services\CreditService;

/**
 * Credit Service Provider
 * 
 * Registers the CreditService in the application container
 */
class CreditServiceProvider extends ServiceProvider
{
    /**
     * Register the service in the container
     */
    public function register()
    {
        $this->app->bind('CreditService', function($app) {
            return new CreditService($app);
        });
    }

    /**
     * Boot the service provider
     */
    public function boot()
    {
        // Any additional setup can be done here
    }
}