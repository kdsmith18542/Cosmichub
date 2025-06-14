<?php

namespace App\Exceptions;

use App\Core\ServiceProvider;
use App\Core\ErrorHandler;

/**
 * Exception Service Provider for registering exception handling services
 */
class ExceptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider
     * 
     * @return void
     */
    public function register()
    {
        // Register the improved error handler
        $this->app->singleton('exception.handler', function ($container) {
            $logger = $container->has(\Psr\Log\LoggerInterface::class) ? $container->get(\Psr\Log\LoggerInterface::class) : null;
            $debug = $container->get('config')->get('app.debug', false);
            return new ErrorHandler($logger, $debug);
        });
        
        $this->app->singleton(ErrorHandler::class, function ($container) {
            return $container->make('exception.handler');
        });
    }
    
    /**
     * Boot the service provider
     * 
     * @return void
     */
    public function boot()
    {
        // Register the error handler globally
        $errorHandler = $this->app->make('exception.handler');
        $errorHandler->register();
    }
}