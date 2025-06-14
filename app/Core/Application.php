<?php

namespace App\Core;

use App\Core\Config\EnhancedConfigServiceProvider;
use App\Core\Controller\ControllerServiceProvider;
use App\Core\Database\DatabaseServiceProvider;
use App\Core\Database\ModelServiceProvider;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareServiceProvider;
use App\Core\Repository\RepositoryServiceProvider;
use App\Core\Routing\RouteServiceProvider;
use App\Core\Service\ServiceServiceProvider;
use App\Core\Session\SessionServiceProvider;
use App\Core\View\ViewServiceProvider;
use App\Exceptions\ExceptionServiceProvider;

/**
 * Main application class
 */
class Application
{
    /**
     * @var string The application version
     */
    const VERSION = '1.0.0';
    
    /**
     * @var Application The application instance
     */
    private static $instance = null;
    
    /**
     * @var Container The service container
     */
    protected $container;
    
    /**
     * @var string The application base path
     */
    protected $basePath;
    
    /**
     * @var array The service providers
     */
    protected $serviceProviders = [];
    
    /**
     * @var array The registered service providers
     */
    protected $registeredServiceProviders = [];
    
    /**
     * @var array The booted service providers
     */
    protected $bootedServiceProviders = [];
    
    /**
     * @var bool Whether the application has been bootstrapped
     */
    protected $bootstrapped = false;
    
    /**
     * @var array The core service providers
     */
        protected $coreServiceProviders = [
            \App\Exceptions\ExceptionServiceProvider::class,
            \App\Core\Config\EnhancedConfigServiceProvider::class,

            \App\Core\Session\SessionServiceProvider::class,
            \App\Core\Database\DatabaseServiceProvider::class,
            \App\Core\Routing\RoutingServiceProvider::class,
            \App\Core\View\ViewServiceProvider::class,
            \App\Core\Middleware\MiddlewareServiceProvider::class,
            \App\Core\Validation\ValidationServiceProvider::class,
            \App\Core\Cache\CacheServiceProvider::class,
            \App\Core\Events\EventServiceProvider::class,
            // Add other core service providers here
        ];
    
    /**
     * Get the application instance
     * 
     * @return Application
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create a new application instance
     * 
     * @param string $basePath The application base path
     */
    private function __construct($basePath = null)
    {
        $this->basePath = $basePath ?: realpath(__DIR__ . '/../../');
        $this->container = Container::getInstance();
        
        $this->registerBaseBindings();
    }
    
    /**
     * Register the basic bindings into the container
     * 
     * @return void
     */
    protected function registerBaseBindings()
    {
        // Register the application instance
        $this->container->instance('app', $this);
        $this->container->instance(Application::class, $this);
        
        // Register the container as a singleton
        $this->container->instance('container', $this->container);
        $this->container->instance(Container::class, $this->container);
    }
    
    /**
     * Register a service provider
     * 
     * @param string|ServiceProvider $provider The service provider
     * @return ServiceProvider
     */
    public function register($provider)
    {
        $providerClass = is_string($provider) ? $provider : get_class($provider);

        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        if (isset($this->registeredServiceProviders[get_class($provider)])) {
            return $provider;
        }

        $this->registeredServiceProviders[get_class($provider)] = true;

        var_dump("Provider object before method_exists check:", $provider);
        var_dump('Provider class name: ' . $providerClass);
        var_dump('Checking method_exists for registerServices on: ' . get_class($provider) . ' Result: ' . (method_exists($provider, 'registerServices') ? 'true' : 'false'));
        if (method_exists($provider, 'registerServices')) {
            var_dump("Calling registerServices on: " . get_class($provider));
            $provider->registerServices();
            
        } else {
            $provider->register();
        }

        $this->serviceProviders[] = $provider;
        return $provider;
    }
    
    /**
     * Boot the service providers
     * 
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->serviceProviders as $provider) {
            var_dump('Attempting to register service provider: ' . get_class($provider));
            $this->bootProvider($provider);
        }

        $this->booted = true;
    }
    
    /**
     * Boot a service provider
     * 
     * @param ServiceProvider $provider The service provider
     * @return void
     */
    protected function bootProvider($provider)
    {
        $class = get_class($provider);
        
        if (isset($this->bootedServiceProviders[$class])) {
            return;
        }
        
        if (method_exists($provider, 'bootServices')) {
            $provider->bootServices();
        } else {
            $provider->boot();
        }
        
        $this->bootedServiceProviders[$class] = true;
    }
    
    /**
     * Register the core service providers
     * 
     * @return void
     */
    public function registerCoreProviders()
    {
        // Register LoggingServiceProvider first to ensure logger is available
        $loggingProvider = new \App\Core\Logging\LoggingServiceProvider($this);
        $this->register($loggingProvider);

        foreach ($this->coreServiceProviders as $provider) {
            var_dump("Attempting to register core service provider: " . $provider);
            $this->register($provider);
            var_dump("Finished registering core service provider: " . $provider);
        }
    }
    
    /**
     * Check if the application has been booted
     * 
     * @return bool
     */
    public function isBooted()
    {
        return $this->bootstrapped;
    }
    
    /**
     * Bootstrap the application
     * 
     * @return $this
     */
    public function bootstrap()
    {
        if ($this->bootstrapped) {
            return $this;
        }
        
        $this->registerCoreProviders();
        
        $this->boot();
        
        $this->bootstrapped = true;
        
        return $this;
    }
    
    /**
     * Run the application
     * 
     * @return void
     */
    public function run()
    {
        // Bootstrap the application if it hasn't been bootstrapped yet
        if (!$this->bootstrapped) {
            $this->bootstrap();
        }
        
        // Get the request
        $request = $this->container->make(Request::class);
        $this->container->instance('request', $request);
        
        // Dispatch the request to the router
        $router = $this->container->get('router');
        $response = $router->dispatch($request);
        
        // Send the response
        $response->send();
        
        return $response;
    }
    
    /**
     * Get the service container
     * 
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
    
    /**
     * Get the router
     * 
     * @return Router
     */
    public function getRouter()
    {
        return $this->container->get('router');
    }
    
    /**
     * Get the config repository
     * 
     * @return Config
     */
    public function getConfig()
    {
        return $this->container->get('config');
    }
    
    /**
     * Get the application base path
     * 
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }
    
    /**
     * Set the base path
     * 
     * @param string $basePath The base path
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/'); 
        
        return $this;
    }
    
    /**
     * Get a path relative to the application base path
     * 
     * @param string $path The path to append
     * @return string
     */
    public function path($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    /**
     * Get the application configuration path
     * 
     * @param string $path The path to append
     * @return string
     */
    public function configPath($path = '')
    {
        return $this->path('config') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    /**
     * Get the application storage path
     * 
     * @param string $path The path to append
     * @return string
     */
    public function storagePath($path = '')
    {
        return $this->path('storage') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    /**
     * Get the application public path
     * 
     * @param string $path The path to append
     * @return string
     */
    public function publicPath($path = '')
    {
        return $this->path('public_html') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $service The service
     * @param array $parameters The parameters
     * @return mixed
     */
    public function make($service, array $parameters = [])
    {
        return $this->container->make($service, $parameters);
    }
    
    /**
     * Register a binding with the container
     * 
     * @param string $abstract The abstract
     * @param mixed $concrete The concrete
     * @param bool $shared Whether the binding is shared
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->container->bind($abstract, $concrete, $shared);
    }
    
    /**
     * Register a shared binding with the container
     * 
     * @param string $abstract The abstract
     * @param mixed $concrete The concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->container->singleton($abstract, $concrete);
    }
    
    /**
     * Register an instance with the container
     * 
     * @param string $abstract The abstract
     * @param mixed $instance The instance
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        return $this->container->instance($abstract, $instance);
    }
    
    /**
     * Check if a service is bound
     * 
     * @param string $abstract The abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return $this->container->bound($abstract);
    }
    
    /**
     * Check if the application is in debug mode
     * 
     * @return bool
     */
    public function isDebug()
    {
        return env('APP_DEBUG', false) === 'true' || env('APP_ENV') === 'development';
    }
    
    /**
     * Get the application version
     * 
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent the instance from being unserialized
     */
    public function __wakeup()
    {
    }
}