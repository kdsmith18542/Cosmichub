<?php

namespace App\Core\ServiceProviders;

use App\Core\ServiceProvider\AbstractServiceProvider;
use App\Services\RarityScoreService;

/**
 * Rarity Score Service Provider
 * 
 * Registers the RarityScoreService in the dependency injection container
 * Part of the refactoring plan to implement proper service layer architecture
 */
class RarityScoreServiceProvider extends AbstractServiceProvider
{
    /**
     * Register services in the container
     */
    public function register(): void
    {
        $this->container->singleton(RarityScoreService::class, function ($container) {
            return new RarityScoreService($container);
        });
        
        // Also register with a shorter alias
        $this->container->alias('rarity.score', RarityScoreService::class);
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
            RarityScoreService::class,
            'rarity.score'
        ];
    }
}