<?php

namespace App\Core\Services;

use App\Core\Container\Container;
use App\Core\Config\Configuration;
use App\Core\Events\EventDispatcher;
use App\Core\Logging\Logger;
use App\Core\Cache\Cache;
use App\Core\Validation\Validator;
use App\Core\Exceptions\ServiceException;

/**
 * Service Factory Class
 * 
 * Provides convenient factory methods for creating and configuring services
 * with common patterns and best practices built-in
 */
class ServiceFactory
{
    /**
     * @var Container The container instance
     */
    protected $container;
    
    /**
     * @var Configuration The configuration instance
     */
    protected $config;
    
    /**
     * @var array Default service configurations
     */
    protected $defaults = [
        'logging' => [
            'enabled' => true,
            'level' => 'info',
            'channel' => 'default'
        ],
        'events' => [
            'enabled' => true,
            'async' => false
        ],
        'validation' => [
            'enabled' => true,
            'strict' => false
        ],
        'caching' => [
            'enabled' => true,
            'ttl' => 3600,
            'driver' => 'file'
        ]
    ];
    
    /**
     * @var array Service templates
     */
    protected $templates = [];
    
    /**
     * Create a new service factory instance
     * 
     * @param Container $container
     * @param Configuration|null $config
     */
    public function __construct(Container $container, Configuration $config = null)
    {
        $this->container = $container;
        $this->config = $config;
        
        $this->loadDefaultTemplates();
    }
    
    /**
     * Create a new service instance
     * 
     * @param string $serviceClass
     * @param array $config
     * @param array $dependencies
     * @return Service
     * @throws ServiceException
     */
    public function create($serviceClass, array $config = [], array $dependencies = [])
    {
        if (!class_exists($serviceClass)) {
            throw ServiceException::notFound("Service class {$serviceClass} not found");
        }
        
        if (!is_subclass_of($serviceClass, Service::class)) {
            throw ServiceException::invalidArgument("Class {$serviceClass} must extend Service");
        }
        
        try {
            // Merge with default configuration
            $config = $this->mergeConfig($config);
            
            // Resolve dependencies
            $resolvedDependencies = $this->resolveDependencies($dependencies);
            
            // Create service instance
            $service = new $serviceClass(
                $this->container,
                $this->getLogger($config),
                $this->getEventDispatcher($config),
                $this->getValidator($config),
                $this->getConfiguration($config)
            );
            
            // Configure the service
            $this->configureService($service, $config);
            
            // Inject additional dependencies
            $this->injectDependencies($service, $resolvedDependencies);
            
            return $service;
        } catch (\Exception $e) {
            throw ServiceException::operationFailed("Failed to create service {$serviceClass}", $e);
        }
    }
    
    /**
     * Create a service from a template
     * 
     * @param string $template
     * @param array $config
     * @return Service
     * @throws ServiceException
     */
    public function createFromTemplate($template, array $config = [])
    {
        if (!isset($this->templates[$template])) {
            throw ServiceException::notFound("Service template '{$template}' not found");
        }
        
        $templateConfig = $this->templates[$template];
        $serviceClass = $templateConfig['class'];
        $defaultConfig = $templateConfig['config'] ?? [];
        $dependencies = $templateConfig['dependencies'] ?? [];
        
        // Merge template config with provided config
        $mergedConfig = array_merge_recursive($defaultConfig, $config);
        
        return $this->create($serviceClass, $mergedConfig, $dependencies);
    }
    
    /**
     * Create a CRUD service
     * 
     * @param string $entityClass
     * @param array $config
     * @return Service
     */
    public function createCrudService($entityClass, array $config = [])
    {
        $config = array_merge([
            'entity' => $entityClass,
            'validation' => ['enabled' => true, 'strict' => true],
            'events' => ['enabled' => true],
            'caching' => ['enabled' => true, 'ttl' => 1800]
        ], $config);
        
        return $this->createFromTemplate('crud', $config);
    }
    
    /**
     * Create an API service
     * 
     * @param string $apiClass
     * @param array $config
     * @return Service
     */
    public function createApiService($apiClass, array $config = [])
    {
        $config = array_merge([
            'api_class' => $apiClass,
            'logging' => ['enabled' => true, 'level' => 'debug'],
            'validation' => ['enabled' => true, 'strict' => true],
            'caching' => ['enabled' => true, 'ttl' => 300]
        ], $config);
        
        return $this->createFromTemplate('api', $config);
    }
    
    /**
     * Create a background job service
     * 
     * @param string $jobClass
     * @param array $config
     * @return Service
     */
    public function createJobService($jobClass, array $config = [])
    {
        $config = array_merge([
            'job_class' => $jobClass,
            'logging' => ['enabled' => true, 'level' => 'info'],
            'events' => ['enabled' => true, 'async' => true],
            'validation' => ['enabled' => true]
        ], $config);
        
        return $this->createFromTemplate('job', $config);
    }
    
    /**
     * Create a notification service
     * 
     * @param array $channels
     * @param array $config
     * @return Service
     */
    public function createNotificationService(array $channels = [], array $config = [])
    {
        $config = array_merge([
            'channels' => $channels,
            'logging' => ['enabled' => true],
            'events' => ['enabled' => true],
            'caching' => ['enabled' => false]
        ], $config);
        
        return $this->createFromTemplate('notification', $config);
    }
    
    /**
     * Register a service template
     * 
     * @param string $name
     * @param string $serviceClass
     * @param array $config
     * @param array $dependencies
     * @return $this
     */
    public function registerTemplate($name, $serviceClass, array $config = [], array $dependencies = [])
    {
        $this->templates[$name] = [
            'class' => $serviceClass,
            'config' => $config,
            'dependencies' => $dependencies
        ];
        
        return $this;
    }
    
    /**
     * Get a service template
     * 
     * @param string $name
     * @return array|null
     */
    public function getTemplate($name)
    {
        return $this->templates[$name] ?? null;
    }
    
    /**
     * Get all registered templates
     * 
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }
    
    /**
     * Set default configuration
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setDefault($key, $value)
    {
        $this->defaults[$key] = $value;
        return $this;
    }
    
    /**
     * Get default configuration
     * 
     * @param string|null $key
     * @return mixed
     */
    public function getDefaults($key = null)
    {
        if ($key === null) {
            return $this->defaults;
        }
        
        return $this->defaults[$key] ?? null;
    }
    
    /**
     * Merge configuration with defaults
     * 
     * @param array $config
     * @return array
     */
    protected function mergeConfig(array $config)
    {
        return array_merge_recursive($this->defaults, $config);
    }
    
    /**
     * Resolve dependencies
     * 
     * @param array $dependencies
     * @return array
     */
    protected function resolveDependencies(array $dependencies)
    {
        $resolved = [];
        
        foreach ($dependencies as $key => $dependency) {
            if (is_string($dependency)) {
                $resolved[$key] = $this->container->get($dependency);
            } elseif (is_callable($dependency)) {
                $resolved[$key] = $dependency($this->container);
            } else {
                $resolved[$key] = $dependency;
            }
        }
        
        return $resolved;
    }
    
    /**
     * Configure a service instance
     * 
     * @param Service $service
     * @param array $config
     * @return void
     */
    protected function configureService(Service $service, array $config)
    {
        // Configure logging
        if (isset($config['logging'])) {
            $service->setLoggingEnabled($config['logging']['enabled'] ?? true);
        }
        
        // Configure events
        if (isset($config['events'])) {
            $service->setEventsEnabled($config['events']['enabled'] ?? true);
        }
        
        // Configure validation
        if (isset($config['validation'])) {
            $service->setValidationEnabled($config['validation']['enabled'] ?? true);
        }
        
        // Configure caching
        if (isset($config['caching']) && method_exists($service, 'setCachingEnabled')) {
            $service->setCachingEnabled($config['caching']['enabled'] ?? true);
        }
        
        // Set custom configuration
        if (method_exists($service, 'setConfig')) {
            $service->setConfig($config);
        }
    }
    
    /**
     * Inject additional dependencies
     * 
     * @param Service $service
     * @param array $dependencies
     * @return void
     */
    protected function injectDependencies(Service $service, array $dependencies)
    {
        foreach ($dependencies as $key => $dependency) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($service, $setter)) {
                $service->$setter($dependency);
            }
        }
    }
    
    /**
     * Get logger instance
     * 
     * @param array $config
     * @return Logger|null
     */
    protected function getLogger(array $config)
    {
        if (!($config['logging']['enabled'] ?? true)) {
            return null;
        }
        
        if ($this->container->has(Logger::class)) {
            return $this->container->get(Logger::class);
        }
        
        return Logger::createFileLogger();
    }
    
    /**
     * Get event dispatcher instance
     * 
     * @param array $config
     * @return EventDispatcher|null
     */
    protected function getEventDispatcher(array $config)
    {
        if (!($config['events']['enabled'] ?? true)) {
            return null;
        }
        
        if ($this->container->has(EventDispatcher::class)) {
            return $this->container->get(EventDispatcher::class);
        }
        
        return new EventDispatcher($this->container);
    }
    
    /**
     * Get validator instance
     * 
     * @param array $config
     * @return Validator|null
     */
    protected function getValidator(array $config)
    {
        if (!($config['validation']['enabled'] ?? true)) {
            return null;
        }
        
        if ($this->container->has(Validator::class)) {
            return $this->container->get(Validator::class);
        }
        
        return new Validator();
    }
    
    /**
     * Get configuration instance
     * 
     * @param array $config
     * @return Configuration|null
     */
    protected function getConfiguration(array $config)
    {
        if ($this->config) {
            return $this->config;
        }
        
        if ($this->container->has(Configuration::class)) {
            return $this->container->get(Configuration::class);
        }
        
        return new Configuration();
    }
    
    /**
     * Load default service templates
     * 
     * @return void
     */
    protected function loadDefaultTemplates()
    {
        // CRUD service template
        $this->registerTemplate('crud', Service::class, [
            'validation' => ['enabled' => true, 'strict' => true],
            'events' => ['enabled' => true],
            'caching' => ['enabled' => true, 'ttl' => 1800],
            'logging' => ['enabled' => true, 'level' => 'info']
        ]);
        
        // API service template
        $this->registerTemplate('api', Service::class, [
            'validation' => ['enabled' => true, 'strict' => true],
            'events' => ['enabled' => true],
            'caching' => ['enabled' => true, 'ttl' => 300],
            'logging' => ['enabled' => true, 'level' => 'debug']
        ]);
        
        // Job service template
        $this->registerTemplate('job', Service::class, [
            'validation' => ['enabled' => true],
            'events' => ['enabled' => true, 'async' => true],
            'caching' => ['enabled' => false],
            'logging' => ['enabled' => true, 'level' => 'info']
        ]);
        
        // Notification service template
        $this->registerTemplate('notification', Service::class, [
            'validation' => ['enabled' => true],
            'events' => ['enabled' => true],
            'caching' => ['enabled' => false],
            'logging' => ['enabled' => true, 'level' => 'info']
        ]);
        
        // Data service template
        $this->registerTemplate('data', Service::class, [
            'validation' => ['enabled' => true, 'strict' => false],
            'events' => ['enabled' => false],
            'caching' => ['enabled' => true, 'ttl' => 3600],
            'logging' => ['enabled' => true, 'level' => 'warning']
        ]);
    }
    
    /**
     * Create a service builder
     * 
     * @param string $serviceClass
     * @return ServiceBuilder
     */
    public function builder($serviceClass)
    {
        return new ServiceBuilder($this, $serviceClass);
    }
    
    /**
     * Get the container instance
     * 
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
    
    /**
     * Get the configuration instance
     * 
     * @return Configuration|null
     */
    public function getConfig()
    {
        return $this->config;
    }
}

/**
 * Service Builder Class
 * 
 * Provides a fluent interface for building services
 */
class ServiceBuilder
{
    /**
     * @var ServiceFactory The factory instance
     */
    protected $factory;
    
    /**
     * @var string The service class
     */
    protected $serviceClass;
    
    /**
     * @var array The configuration
     */
    protected $config = [];
    
    /**
     * @var array The dependencies
     */
    protected $dependencies = [];
    
    /**
     * Create a new service builder
     * 
     * @param ServiceFactory $factory
     * @param string $serviceClass
     */
    public function __construct(ServiceFactory $factory, $serviceClass)
    {
        $this->factory = $factory;
        $this->serviceClass = $serviceClass;
    }
    
    /**
     * Set configuration
     * 
     * @param array $config
     * @return $this
     */
    public function config(array $config)
    {
        $this->config = array_merge_recursive($this->config, $config);
        return $this;
    }
    
    /**
     * Enable logging
     * 
     * @param string $level
     * @param string $channel
     * @return $this
     */
    public function withLogging($level = 'info', $channel = 'default')
    {
        $this->config['logging'] = [
            'enabled' => true,
            'level' => $level,
            'channel' => $channel
        ];
        return $this;
    }
    
    /**
     * Enable events
     * 
     * @param bool $async
     * @return $this
     */
    public function withEvents($async = false)
    {
        $this->config['events'] = [
            'enabled' => true,
            'async' => $async
        ];
        return $this;
    }
    
    /**
     * Enable validation
     * 
     * @param bool $strict
     * @return $this
     */
    public function withValidation($strict = false)
    {
        $this->config['validation'] = [
            'enabled' => true,
            'strict' => $strict
        ];
        return $this;
    }
    
    /**
     * Enable caching
     * 
     * @param int $ttl
     * @param string $driver
     * @return $this
     */
    public function withCaching($ttl = 3600, $driver = 'file')
    {
        $this->config['caching'] = [
            'enabled' => true,
            'ttl' => $ttl,
            'driver' => $driver
        ];
        return $this;
    }
    
    /**
     * Add a dependency
     * 
     * @param string $key
     * @param mixed $dependency
     * @return $this
     */
    public function dependency($key, $dependency)
    {
        $this->dependencies[$key] = $dependency;
        return $this;
    }
    
    /**
     * Build the service
     * 
     * @return Service
     */
    public function build()
    {
        return $this->factory->create($this->serviceClass, $this->config, $this->dependencies);
    }
}