<?php

namespace App\Core\ServiceProviders;

use App\Core\ServiceProvider\AbstractServiceProvider;
use App\Services\ReportService;

/**
 * Report Service Provider
 * 
 * Registers the ReportService in the dependency injection container
 * Part of the refactoring plan to implement proper service layer architecture
 */
class ReportServiceProvider extends AbstractServiceProvider
{
    /**
     * Register services in the container
     */
    public function register(): void
    {
        $this->container->singleton(ReportService::class, function ($container) {
            return new ReportService($container);
        });
        
        // Also register with a shorter alias
        $this->container->alias('report.service', ReportService::class);
    }
    
    /**
     * Boot services after all providers have been registered
     */
    public function boot(): void
    {
        // No additional boot logic needed for this service
    }
    
    /**
     * Get the services provided by this provider
     * 
     * @return array
     */
    public function provides(): array
    {
        return [
            ReportService::class,
            'report.service'
        ];
    }
}