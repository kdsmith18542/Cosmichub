<?php

namespace App\Services;

use App\Core\ServiceProvider\AbstractServiceProvider;

class AnalyticsServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service in the container
     */
    public function register()
    {
        $this->container->singleton('AnalyticsService', function ($app) {
            return new AnalyticsService($app);
        });
    }
    
    /**
     * Register service aliases
     */
    public function registerAliases()
    {
        $this->container->alias('AnalyticsService', AnalyticsService::class);
    }
}