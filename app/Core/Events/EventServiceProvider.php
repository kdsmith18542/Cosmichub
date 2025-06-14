<?php

namespace App\Core\Events;

use App\Core\ServiceProvider;
use App\Core\Container\Container;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Enhanced EventServiceProvider for event management
 * 
 * This provider handles event dispatching, listening, and management
 * capabilities for the application, supporting both synchronous and
 * asynchronous event handling.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Services provided by this provider
     *
     * @var array
     */
    protected $provides = [
        'events',
        'event.dispatcher',
        EventDispatcher::class,
        EventDispatcherInterface::class,
        EventManager::class,
        'event.manager'
    ];

    /**
     * Singletons provided by this provider
     *
     * @var array
     */
    protected $singletons = [
        'events',
        'event.dispatcher',
        EventDispatcher::class,
        EventManager::class,
        'event.manager'
    ];

    /**
     * Service aliases
     *
     * @var array
     */
    protected $aliases = [
        'events' => EventDispatcher::class,
        'event.dispatcher' => EventDispatcher::class,
        'event.manager' => EventManager::class
    ];

    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Create a new service provider instance.
     *
     * @param \App\Core\Application $app
     * @param \Psr\Log\LoggerInterface $logger
     * @return void
     */
    public function __construct($app, LoggerInterface $logger)
    {
        parent::__construct($app);
        $this->logger = $logger;
    }

    /**
     * Default event listeners
     *
     * @var array
     */
    protected $defaultListeners = [
        'app.booting' => [],
        'app.booted' => [],
        'request.received' => [],
        'response.sending' => [],
        'exception.occurred' => [],
        'database.connecting' => [],
        'database.connected' => [],
        'cache.hit' => [],
        'cache.miss' => [],
        'session.started' => [],
        'user.login' => [],
        'user.logout' => []
    ];

    /**
     * Event subscribers
     *
     * @var array
     */
    protected $subscribers = [];

    /**
     * Event middleware
     *
     * @var array
     */
    protected $eventMiddleware = [];

    /**
     * Async event handlers
     *
     * @var array
     */
    protected $asyncHandlers = [];

    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        $this->registerServices();
    }

    /**
     * Boot the service provider
     *
     * @return void
     */
    public function boot()
    {
        $this->bootServices();
    }

    /**
     * Register event services
     *
     * @return void
     */
    protected function registerServices()
    {
        // Register EventDispatcher with PSR-14 support
        $this->app->singleton('events', function ($app) {
            $container = $app->getContainer();
            return new EventDispatcher(
                $app,
                $container instanceof Container ? $container : null
            );
        });

        $this->app->alias('events', EventDispatcher::class);
        $this->app->alias('events', EventDispatcherInterface::class);
        $this->app->alias('events', 'event.dispatcher');

        // Register EventManager
        $this->app->singleton('event.manager', function ($app) {
            return new EventManager($app->make('events'));
        });

        $this->app->alias('event.manager', EventManager::class);

        // Register event-related dependencies
        $this->registerEventDependencies();
        
        // Register event listeners from configuration
        $this->registerEventListeners();
        
        // Register event subscribers
        $this->registerEventSubscribers();
        
        // Register event middleware
        $this->registerEventMiddleware();
        
        // Register async event handlers
        $this->registerAsyncHandlers();
    }

    /**
     * Boot event services
     *
     * @return void
     */
    protected function bootServices()
    {
        // Set up event discovery
        $this->setupEventDiscovery();
        
        // Configure event priorities
        $this->configureEventPriorities();
        
        // Set up event logging
        $this->setupEventLogging();
        
        // Register application events
        $this->registerApplicationEvents();
        
        // Set up event queuing
        $this->setupEventQueuing();
        
        // Configure event serialization
        $this->configureEventSerialization();
        
        // Set up event debugging
        $this->setupEventDebugging();
    }

    /**
     * Register event-related dependencies
     *
     * @return void
     */
    protected function registerEventDependencies()
    {
        // Register event listener resolver
        $this->app->bind('event.listener.resolver', function ($app) {
            return new EventListenerResolver($app);
        });

        // Register event serializer
        $this->app->bind('event.serializer', function ($app) {
            return new EventSerializer();
        });

        // Register event queue
        if (class_exists('App\\Core\\Queue\\QueueManager')) {
            $this->app->bind('event.queue', function ($app) {
                return $app->make('App\\Core\\Queue\\QueueManager');
            });
        }

        // Register event logger
        $this->app->bind('event.logger', function ($app) {
            return new EventLogger($app->make('logger'));
        });
    }

    /**
     * Register event listeners from configuration
     *
     * @return void
     */
    protected function registerEventListeners()
    {
        $dispatcher = $this->app->make('events');
        
        // Register default listeners
        foreach ($this->defaultListeners as $event => $listeners) {
            foreach ($listeners as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }
        
        // Register listeners from configuration
        $configListeners = $this->app->config('events.listeners', []);
        foreach ($configListeners as $event => $listeners) {
            foreach ((array) $listeners as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }
        
        // Register wildcard listeners
        $wildcardListeners = $this->app->config('events.wildcard', []);
        foreach ($wildcardListeners as $pattern => $listeners) {
            foreach ((array) $listeners as $listener) {
                $dispatcher->listen($pattern, $listener);
            }
        }
    }

    /**
     * Register event subscribers
     *
     * @return void
     */
    protected function registerEventSubscribers()
    {
        $dispatcher = $this->app->make('events');
        
        // Register configured subscribers
        $subscribers = array_merge(
            $this->subscribers,
            $this->app->config('events.subscribers', [])
        );
        
        foreach ($subscribers as $subscriber) {
            $dispatcher->subscribe($subscriber);
        }
    }

    /**
     * Register event middleware
     *
     * @return void
     */
    protected function registerEventMiddleware()
    {
        $dispatcher = $this->app->make('events');
        
        // Register configured middleware
        $middleware = array_merge(
            $this->eventMiddleware,
            $this->app->config('events.middleware', [])
        );
        
        foreach ($middleware as $event => $middlewareList) {
            foreach ((array) $middlewareList as $middlewareClass) {
                $dispatcher->addMiddleware($event, $middlewareClass);
            }
        }
    }

    /**
     * Register async event handlers
     *
     * @return void
     */
    protected function registerAsyncHandlers()
    {
        if (!$this->app->has('event.queue')) {
            return;
        }
        
        $dispatcher = $this->app->make('events');
        
        // Register configured async handlers
        $asyncHandlers = array_merge(
            $this->asyncHandlers,
            $this->app->config('events.async', [])
        );
        
        foreach ($asyncHandlers as $event => $handlers) {
            foreach ((array) $handlers as $handler) {
                $dispatcher->listenAsync($event, $handler);
            }
        }
    }

    /**
     * Set up event discovery
     *
     * @return void
     */
    protected function setupEventDiscovery()
    {
        if (!$this->app->config('events.discovery.enabled', false)) {
            return;
        }
        
        $paths = $this->app->config('events.discovery.paths', [
            $this->app->path('app/Events'),
            $this->app->path('app/Listeners')
        ]);
        
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->discoverEventsInPath($path);
            }
        }
    }

    /**
     * Discover events in a given path
     *
     * @param string $path
     * @return void
     */
    protected function discoverEventsInPath($path)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->registerEventFromFile($file->getPathname());
            }
        }
    }

    /**
     * Register event from file
     *
     * @param string $filePath
     * @return void
     */
    protected function registerEventFromFile($filePath)
    {
        $content = file_get_contents($filePath);
        
        // Simple pattern matching for event classes
        if (preg_match('/class\s+(\w+)\s+implements\s+.*EventInterface/', $content, $matches)) {
            $className = $matches[1];
            $namespace = $this->extractNamespace($content);
            $fullClassName = $namespace ? $namespace . '\\' . $className : $className;
            
            // Auto-register the event
            $this->app->make('events')->registerEvent($fullClassName);
        }
    }

    /**
     * Extract namespace from file content
     *
     * @param string $content
     * @return string|null
     */
    protected function extractNamespace($content)
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Configure event priorities
     *
     * @return void
     */
    protected function configureEventPriorities()
    {
        $dispatcher = $this->app->make('events');
        $priorities = $this->app->config('events.priorities', []);
        
        foreach ($priorities as $event => $priority) {
            $dispatcher->setPriority($event, $priority);
        }
    }

    /**
     * Set up event logging
     *
     * @return void
     */
    protected function setupEventLogging()
    {
        if (!$this->app->config('events.logging.enabled', false)) {
            return;
        }
        
        $dispatcher = $this->app->make('events');
        $logger = $this->app->make('event.logger');
        
        // Log all events if configured
        if ($this->app->config('events.logging.log_all', false)) {
            $dispatcher->listen('*', function ($event, $payload) use ($logger) {
                $logger->logEvent($event, $payload);
            });
        }
        
        // Log specific events
        $logEvents = $this->app->config('events.logging.events', []);
        foreach ($logEvents as $event) {
            $dispatcher->listen($event, function ($payload) use ($logger, $event) {
                $logger->logEvent($event, $payload);
            });
        }
    }

    /**
     * Register application events
     *
     * @return void
     */
    protected function registerApplicationEvents()
    {
        $dispatcher = $this->app->make('events');
        
        // Register core application events
        $dispatcher->listen('app.booting', function () {
            if ($this->app->environment('development')) {
                $this->logger->info('Application is booting...');
            }
        });
        
        $dispatcher->listen('app.booted', function () {
            if ($this->app->environment('development')) {
                $this->logger->info('Application has booted successfully.');
            }
        });
        
        // Register exception events
        $dispatcher->listen('exception.occurred', function ($exception) {
            if ($this->app->config('app.debug', false)) {
                $this->logger->error('Exception occurred: ' . $exception->getMessage(), ['exception' => $exception]);
            }
        });
    }

    /**
     * Set up event queuing
     *
     * @return void
     */
    protected function setupEventQueuing()
    {
        if (!$this->app->has('event.queue') || !$this->app->config('events.queue.enabled', false)) {
            return;
        }
        
        $dispatcher = $this->app->make('events');
        $queue = $this->app->make('event.queue');
        
        // Set up queued event handling
        $queuedEvents = $this->app->config('events.queue.events', []);
        foreach ($queuedEvents as $event) {
            $dispatcher->listenQueued($event, $queue);
        }
    }

    /**
     * Configure event serialization
     *
     * @return void
     */
    protected function configureEventSerialization()
    {
        if (!$this->app->has('event.serializer')) {
            return;
        }
        
        $serializer = $this->app->make('event.serializer');
        $dispatcher = $this->app->make('events');
        
        // Configure serialization for specific events
        $serializableEvents = $this->app->config('events.serializable', []);
        foreach ($serializableEvents as $event) {
            $dispatcher->setSerializer($event, $serializer);
        }
    }

    /**
     * Set up event debugging
     *
     * @return void
     */
    protected function setupEventDebugging()
    {
        if (!$this->app->environment('development') || !$this->app->config('events.debug', false)) {
            return;
        }
        
        $dispatcher = $this->app->make('events');
        
        // Add debug listener for all events
        $dispatcher->listen('*', function ($event, $payload) {
            $this->logger->debug(sprintf(
                'Event fired: %s with payload: %s',
                $event,
                json_encode($payload, JSON_PRETTY_PRINT)
            ));
        });
    }

    /**
     * Get the events that trigger this service provider to register
     *
     * @return array
     */
    public function when()
    {
        return [
            'app.booting',
            'events.needed'
        ];
    }

    /**
     * Get configuration from file or environment
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig($key, $default = null)
    {
        // Try to get from config first
        if ($this->app->has('config')) {
            $config = $this->app->make('config');
            if ($config->has($key)) {
                return $config->get($key);
            }
        }
        
        // Fallback to environment variables
        $envKey = strtoupper(str_replace('.', '_', $key));
        return $this->env($envKey, $default);
    }

    /**
     * Get environment variable value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function env($key, $default = null)
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string representations to appropriate types
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        
        // Handle quoted strings
        if (strlen($value) > 1 && $value[0] === '"' && $value[-1] === '"') {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
}