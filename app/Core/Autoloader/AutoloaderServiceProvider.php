<?php

namespace App\Core\Autoloader;

use App\Core\ServiceProvider;
use App\Core\Autoloader\EnhancedAutoloader;

/**
 * Autoloader Service Provider
 * 
 * Registers and configures the enhanced autoloader service.
 */
class AutoloaderServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container
     * 
     * @return void
     */
    public function register(): void
    {
        $this->container->singleton('autoloader', function ($container) {
            $config = $container->get('config');
            $debug = $config->get('app.debug', false);
            
            $autoloader = new EnhancedAutoloader($debug);
            
            // Add any custom PSR-4 mappings from config
            $psr4Mappings = $config->get('autoloader.psr4', []);
            foreach ($psr4Mappings as $prefix => $paths) {
                $autoloader->addPsr4($prefix, $paths);
            }
            
            // Add class maps from config
            $classMaps = $config->get('autoloader.classmap', []);
            if (!empty($classMaps)) {
                $autoloader->addClassMaps($classMaps);
            }
            
            // Add fallback directories from config
            $fallbackDirs = $config->get('autoloader.fallback_dirs', []);
            foreach ($fallbackDirs as $dir) {
                $autoloader->addFallbackDir($dir);
            }
            
            return $autoloader;
        });
        
        // Alias for easier access
        $this->container->alias(EnhancedAutoloader::class, 'autoloader');
    }
    
    /**
     * Boot services
     * 
     * @return void
     */
    public function boot(): void
    {
        // The enhanced autoloader is registered but not activated by default
        // to avoid conflicts with Composer's autoloader
        // It can be activated manually if needed for specific use cases
    }
    
    /**
     * Get the services provided by this provider
     * 
     * @return array
     */
    public function provides(): array
    {
        return [
            'autoloader',
            EnhancedAutoloader::class
        ];
    }
}