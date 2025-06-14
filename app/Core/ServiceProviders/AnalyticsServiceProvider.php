<?php

namespace App\Core\ServiceProviders;

use App\Core\ServiceProvider\AbstractServiceProvider;
use App\Services\AnalyticsService;
use App\Repositories\AnalyticsRepository;

/**
 * Analytics Service Provider
 * 
 * Registers the AnalyticsService in the dependency injection container
 * Part of the refactoring plan to implement proper service layer architecture
 */
class AnalyticsServiceProvider extends AbstractServiceProvider
{
    /**
     * Register services in the container
     */
    public function register(): void
    {
        $this->container->singleton(AnalyticsService::class, function ($container) {
            return new AnalyticsService(
                $container->resolve(AnalyticsRepository::class)
            );
        });
        
        // Also register with a shorter alias
        $this->container->alias('analytics.service', AnalyticsService::class);
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
            AnalyticsService::class,
            'analytics.service'
        ];
    }
}