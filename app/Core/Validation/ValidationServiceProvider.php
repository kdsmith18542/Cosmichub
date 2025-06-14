<?php

namespace App\Core\Validation;

use App\Core\ServiceProvider;
use App\Core\Validation\Contracts\ValidationManagerInterface;
use App\Core\Validation\ValidationManager;

/**
 * Validation Service Provider
 * 
 * Registers validation services in the dependency injection container.
 */
class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register the service in the container
     * 
     * @return void
     */
    public function register(): void
    {
        $this->registerValidationManager();
        $this->registerValidationAlias();
    }
    
    /**
     * Boot the service provider
     * 
     * @return void
     */
    public function boot(): void
    {
        // Boot logic if needed
    }
    
    /**
     * Register the validation manager
     * 
     * @return void
     */
    protected function registerValidationManager(): void
    {
        $this->app->singleton(ValidationManagerInterface::class, function ($app) {
            return new ValidationManager($app);
        });
        
        $this->app->singleton(ValidationManager::class, function ($app) {
            return $app->make(ValidationManagerInterface::class);
        });
    }
    
    /**
     * Register validation alias
     * 
     * @return void
     */
    protected function registerValidationAlias(): void
    {
        $this->app->alias(ValidationManagerInterface::class, 'validator');
        $this->app->alias(ValidationManagerInterface::class, 'validation');
    }
    
    /**
     * Get the services provided by the provider
     * 
     * @return array
     */
    public function provides(): array
    {
        return [
            ValidationManagerInterface::class,
            ValidationManager::class,
            'validator',
            'validation'
        ];
    }
}