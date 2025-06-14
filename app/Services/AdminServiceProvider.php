<?php

namespace App\Services;

use App\Core\ServiceProvider;
use App\Core\Application;

/**
 * Admin Service Provider
 * 
 * Registers the AdminService in the dependency injection container
 */
class AdminServiceProvider extends ServiceProvider
{
    /**
     * The provided services
     *
     * @var array
     */
    protected $provides = [
        AdminService::class,
        'admin.service',
    ];

    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        $this->registerAdminService();
        $this->registerAliases();
    }

    /**
     * Register the admin service
     *
     * @return void
     */
    protected function registerAdminService()
    {
        $this->app->singleton(AdminService::class, function (Application $app) {
            return new AdminService();
        });
    }

    /**
     * Register service aliases
     *
     * @return void
     */
    protected function registerAliases()
    {
        $this->app->alias('admin.service', AdminService::class);
    }

    /**
     * Boot the service provider
     *
     * @return void
     */
    public function boot()
    {
        // Any boot logic can go here
    }

    /**
     * Get the services provided by the provider
     *
     * @return array
     */
    public function provides()
    {
        return $this->provides;
    }
}