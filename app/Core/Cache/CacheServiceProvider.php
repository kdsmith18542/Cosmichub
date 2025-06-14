<?php

namespace App\Core\Cache;

use App\Core\ServiceProvider;
use App\Core\Cache\CacheManager;
use App\Core\Cache\Contracts\CacheManagerInterface;

/**
 * Cache Service Provider
 * 
 * Registers and configures the cache system services.
 */
class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container
     * 
     * @return void
     */
    public function register(): void
    {
        $this->registerCacheManager();
        $this->registerCacheStore();
    }
    
    /**
     * Register the cache manager
     * 
     * @return void
     */
    protected function registerCacheManager(): void
    {
        $this->container->singleton('cache', function ($container) {
            $config = $container->get('config');
            $cacheConfig = $config->get('cache', []);
            
            return new CacheManager($cacheConfig);
        });
        
        // Alias for interface binding
        $this->container->alias(CacheManagerInterface::class, 'cache');
        $this->container->alias(CacheManager::class, 'cache');
    }
    
    /**
     * Register the default cache store
     * 
     * @return void
     */
    protected function registerCacheStore(): void
    {
        $this->container->singleton('cache.store', function ($container) {
            return $container->get('cache')->store();
        });
    }
    
    /**
     * Boot services
     * 
     * @return void
     */
    public function boot(): void
    {
        // Register cache cleanup command if running in CLI
        if (php_sapi_name() === 'cli') {
            $this->registerCacheCleanupCommand();
        }
    }
    
    /**
     * Register cache cleanup command
     * 
     * @return void
     */
    protected function registerCacheCleanupCommand(): void
    {
        // This would register a console command for cache cleanup
        // Implementation depends on the console system being used
    }
    
    /**
     * Get the services provided by this provider
     * 
     * @return array
     */
    public function provides(): array
    {
        return [
            'cache',
            'cache.store',
            CacheManager::class,
            CacheManagerInterface::class
        ];
    }
}