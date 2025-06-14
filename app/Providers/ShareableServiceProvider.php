<?php

namespace App\Providers;

use App\Core\ServiceProvider\ServiceProvider;
use App\Services\ShareableService;
use App\Repositories\ShareableRepository;

/**
 * ShareableServiceProvider for registering ShareableService
 */
class ShareableServiceProvider extends ServiceProvider
{
    /**
     * Register the service in the container
     */
    public function register(): void
    {
        $this->container->bind(ShareableRepository::class, function() {
            return new ShareableRepository();
        });
        
        $this->container->bind(ShareableService::class, function() {
            return new ShareableService(
                $this->container->resolve(ShareableRepository::class)
            );
        });
    }
    
    /**
     * Boot the service provider
     */
    public function boot(): void
    {
        // Boot logic if needed
    }
}