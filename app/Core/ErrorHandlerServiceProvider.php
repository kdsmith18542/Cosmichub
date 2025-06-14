<?php

namespace App\Core;

use App\Core\ErrorHandler;
use App\Core\Logging\Logger;
use App\Core\ServiceProvider;

/**
 * Error Handler Service Provider
 * 
 * Registers the error handler service in the container and configures
 * error handling for the application.
 */
class ErrorHandlerServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container
     * 
     * @return void
     */
    public function register(): void
    {
        $this->container->singleton('error.handler', function ($container) {
            $logger = $container->has(\Psr\Log\LoggerInterface::class) ? $container->get(\Psr\Log\LoggerInterface::class) : null;
            $debug = $container->get('config')->get('app.debug', false);
            
            return new ErrorHandler($logger, $debug);
        });
        
        // Alias for easier access
        $this->container->alias(ErrorHandler::class, 'error.handler');
    }
    
    /**
     * Boot services
     * 
     * @return void
     */
    public function boot(): void
    {
        $errorHandler = $this->container->get('error.handler');
        $errorHandler->register();
    }
    
    /**
     * Get the services provided by this provider
     * 
     * @return array
     */
    public function provides(): array
    {
        return [
            'error.handler',
            ErrorHandler::class
        ];
    }
}