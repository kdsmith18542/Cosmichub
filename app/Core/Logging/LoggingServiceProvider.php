<?php

namespace App\Core\Logging;

use App\Core\Application;
use App\Core\ServiceProvider;
use App\Core\Container as BaseContainer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;

class LoggingServiceProvider extends ServiceProvider
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function registerServices()
    {
        $this->container->singleton(LoggerInterface::class, function (BaseContainer $container) {
            $this->container->alias('logger', LoggerInterface::class);

            $config = $container->make('config');
             $logConfig = $config->get('logging', []);
 
             $logger = new Logger();
            
            return $logger;
        });





        }

    public function bootServices()
    {
        // Optionally, register Monolog to handle PHP errors and exceptions
        // This can be powerful but ensure it doesn't conflict with existing ExceptionHandler
        // if ($this->app->getContainer()->has(LoggerInterface::class)) {
        //     $logger = $this->app->getContainer()->get(LoggerInterface::class);
        //     \Monolog\ErrorHandler::register($logger);
        // }
    }

    public function provides()
    {
        return [LoggerInterface::class, 'logger'];
    }
}