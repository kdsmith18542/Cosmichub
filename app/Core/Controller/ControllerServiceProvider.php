<?php

namespace App\Core\Controller;

use App\Core\ServiceProvider;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\UrlGenerator;
use App\Core\Config\Config;
use App\Core\View\ViewFactory;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * ControllerServiceProvider class for registering controller services
 */
class ControllerServiceProvider extends ServiceProvider
{
    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * ControllerServiceProvider constructor.
     *
     * @param \App\Core\Application $app
     * @param LoggerInterface|null $logger
     */
    public function __construct($app, LoggerInterface $logger = null)
    {
        parent::__construct($app);
        $this->logger = $logger;
    }

    /**
     * The provided services
     *
     * @var array
     */
    protected $provides = [
        Controller::class,
        'controller'
    ];

    /**
     * The services to be registered as singletons
     *
     * @var array
     */
    protected $singletons = [];

    /**
     * The service aliases
     *
     * @var array
     */
    protected $aliases = [
        'controller' => Controller::class
    ];

    /**
     * Controller namespace
     *
     * @var string
     */
    protected $controllerNamespace = 'App\\Controllers';

    /**
     * Register the controller services
     *
     * @return void
     */
    protected function registerServices()
    {
        // Register the base controller
        $this->container->bind(Controller::class, function ($container) {
            return new Controller($this->app);
        });

        // Register controller dependencies
        $this->registerControllerDependencies();

        // Register controller discovery if enabled
        if ($this->config('app.controller_discovery', false)) {
            $this->registerControllerDiscovery();
        }
    }

    /**
     * Boot the controller services
     *
     * @return void
     */
    protected function bootServices()
    {
        // Register controller middleware if enabled
        if ($this->config('app.controller_middleware', true)) {
            $this->registerControllerMiddleware();
        }

        // Register controller validation rules if enabled
        if ($this->config('app.controller_validation', true)) {
            $this->registerControllerValidation();
        }
    }

    /**
     * Register controller dependencies
     *
     * @return void
     */
    protected function registerControllerDependencies()
    {
        // Make sure required dependencies are available
        if (!$this->container->has(Request::class)) {
            $this->container->singleton(Request::class, function () {
                return Request::capture();
            });
        }

        if (!$this->container->has(Response::class)) {
            $this->container->bind(Response::class, function () {
                return new Response();
            });
        }

        if (!$this->container->has(UrlGenerator::class) && $this->container->has('router')) {
            $this->container->singleton(UrlGenerator::class, function ($container) {
                $router = $container->make('router');
                $request = $container->make(Request::class);
                return new UrlGenerator($router->getRoutes(), $request);
            });
        }
    }

    /**
     * Register controller discovery
     *
     * @return void
     */
    protected function registerControllerDiscovery()
    {
        // Get controller paths from config
        $controllerPaths = $this->config('app.controller_paths', [
            $this->app->getBasePath() . '/controllers',
            $this->app->getBasePath() . '/Controllers',
        ]);

        // Set controller namespace from config
        $this->controllerNamespace = $this->config('app.controller_namespace', $this->controllerNamespace);

        // Auto-discover controllers in the specified paths
        // This would be implemented to scan directories and register controllers
        // For now, we'll just log that discovery is enabled
         if ($this->app->isDebug()) {
             if ($this->logger) {
                 $this->logger->info("Controller discovery enabled. Paths: " . implode(", ", $controllerPaths));
             } else {
                 \App\Support\Log::info("Controller discovery enabled. Paths: " . implode(", ", $controllerPaths));
             }
         }
    }

    /**
     * Register controller middleware
     *
     * @return void
     */
    protected function registerControllerMiddleware()
    {
        // This would be implemented to scan controller methods for middleware attributes
         // and register them with the router
         if ($this->app->isDebug()) {
             if ($this->logger) {
                 $this->logger->info("Controller middleware registration enabled");
             } else {
                 \App\Support\Log::info("Controller middleware registration enabled");
             }
         }
    }

    /**
     * Register controller validation rules
     *
     * @return void
     */
    protected function registerControllerValidation()
    {
        // This would be implemented to scan controller methods for validation rules
         // and register them with the validator
         if ($this->app->isDebug()) {
             if ($this->logger) {
                 $this->logger->info("Controller validation registration enabled");
             } else {
                 \App\Support\Log::info("Controller validation registration enabled");
             }
         }
    }
}