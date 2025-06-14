<?php

namespace App\Services;

use App\Core\ServiceProvider\AbstractServiceProvider;

class CreditServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service in the container
     */
    public function register()
    {
        $this->container->singleton('CreditService', function ($app) {
            return new CreditService($app);
        });
    }
    
    /**
     * Register service aliases
     */
    public function registerAliases()
    {
        $this->container->alias('CreditService', CreditService::class);
    }
}