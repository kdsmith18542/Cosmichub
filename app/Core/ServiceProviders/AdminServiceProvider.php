<?php

namespace App\Core\ServiceProviders;

use App\Core\ServiceProvider;
use App\Services\AdminService;

/**
 * Admin Service Provider
 * 
 * Registers AdminService in the dependency injection container
 */
class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register the service in the container
     */
    public function register(): void
    {
        $this->container->singleton(AdminService::class, function($container) {
            return new AdminService();
        });
        
        // Register alias for easier access
        $this->container->alias('admin', AdminService::class);
    }
    
    /**
     * Boot the service (if needed)
     */
    public function boot(): void
    {
        // Any additional setup can go here
    }
}