<?php

namespace App\Core\Events;

use App\Core\Application;
use App\Core\Container\Container;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Closure;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use Psr\Log\LoggerInterface;

/**
 * Enhanced EventDispatcher class for handling event dispatching and listening
 * 
 * This class has been enhanced following the refactoring plan to provide:
 * - PSR-14 compliant event dispatching
 * - Event listening and dispatching
 * - Wildcard event patterns
 * - Event priorities
 * - Middleware support
 * - Async event handling
 * - Event subscribers
 * - Container integration
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var Application The application instance
     */
    protected $app;

    /**
     * @var Container The container instance
     */
    protected $container;

    /**
     * @var ListenerProviderInterface The listener provider
     */
    protected $listenerProvider;

    /**
     * @var array Event listeners
     */
    protected $listeners = [];

    /**
     * @var array Wildcard listeners
     */
    protected $wildcards = [];

    /**
     * @var array Event priorities
     */
    protected $priorities = [];

    /**
     * @var array Event middleware
     */
    protected $middleware = [];

    /**
     * @var array Async event handlers
     */
    protected $asyncHandlers = [];

    /**
     * @var array Event serializers
     */
    protected $serializers = [];

    /**
     * @var array Registered events
     */
    protected $registeredEvents = [];

    /**
     * @var bool Whether to halt on first listener return
     */
    protected $halt = false;

    /**
     * @var array Event firing stack
     */
    protected $firing = [];

    /**
     * @var array Sorted listeners cache
     */
    protected $sorted = [];

    /**
     * Constructor
     *
     * @param Application $app
     * @param Container|null $container
     * @param ListenerProviderInterface|null $listenerProvider
     * @param LoggerInterface|null $logger
     */
    public function __construct(Application $app, Container $container = null, ListenerProviderInterface $listenerProvider = null, LoggerInterface $logger = null)
    {
        $this->app = $app;
        $this->container = $container ?: $app->getContainer();
        $this->listenerProvider = $listenerProvider;
        $this->logger = $logger;
    }

    /**
     * Dispatch an event (PSR-14 compliant)
     *
     * @param object $event
     * @return object
     */
    public function dispatch(object $event): object
    {
        $eventName = get_class($event);
        
        // Get listeners from provider if available
        if ($this->listenerProvider) {
            $listeners = $this->listenerProvider->getListenersForEvent($event);
            foreach ($listeners as $listener) {
                $this->callListenerForEvent($listener, $event);
                
                if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                    break;
                }
            }
        }
        
        // Fire the event using existing fire method for backward compatibility
        $this->fire($eventName, $event, $this->halt);
        
        return $event;
    }

    /**
     * Dispatch an event (legacy method)
     *
     * @param string|object $event
     * @param mixed $payload
     * @param bool $halt
     * @return array|null
     */
    public function dispatchLegacy($event, $payload = [], $halt = false)
    {
        return $this->fire($event, $payload, $halt);
    }

    /**
     * Call a listener for PSR-14 event
     *
     * @param callable|string $listener
     * @param object $event
     * @return mixed
     */
    protected function callListenerForEvent($listener, object $event)
    {
        if (is_string($listener)) {
            // Resolve from container
            if ($this->container && $this->container->has($listener)) {
                $listener = $this->container->get($listener);
            } elseif (class_exists($listener)) {
                $listener = $this->container ? $this->container->make($listener) : new $listener();
            }
        }
        
        if (is_object($listener) && method_exists($listener, 'handle')) {
            return $listener->handle($event);
        }
        
        if (is_callable($listener)) {
            return call_user_func($listener, $event);
        }
        
        throw new \InvalidArgumentException('Listener must be callable or have a handle method');
    }

    /**
     * Register an event listener
     *
     * @param string|array $events
     * @param mixed $listener
     * @param int $priority
     * @return void
     */
    public function listen($events, $listener, $priority = 0)
    {
        foreach ((array) $events as $event) {
            if (str_contains($event, '*')) {
                $this->setupWildcardListen($event, $listener, $priority);
            } else {
                $this->listeners[$event][$priority][] = $this->makeListener($listener);
                unset($this->sorted[$event]);
            }
        }
    }

    /**
     * Register a wildcard event listener
     *
     * @param string $event
     * @param mixed $listener
     * @param int $priority
     * @return void
     */
    protected function setupWildcardListen($event, $listener, $priority = 0)
    {
        $this->wildcards[$event][$priority][] = $this->makeListener($listener);
        unset($this->sorted[$event]);
    }

    /**
     * Register an event listener that should be queued
     *
     * @param string $event
     * @param mixed $listener
     * @return void
     */
    public function listenQueued($event, $listener)
    {
        $this->listen($event, function ($payload) use ($listener) {
            if ($this->app->has('event.queue')) {
                $queue = $this->app->make('event.queue');
                $queue->push($listener, $payload);
            } else {
                // Fallback to synchronous execution
                $this->resolveListener($listener)($payload);
            }
        });
    }

    /**
     * Register an async event listener
     *
     * @param string $event
     * @param mixed $listener
     * @return void
     */
    public function listenAsync($event, $listener)
    {
        $this->asyncHandlers[$event][] = $this->makeListener($listener);
    }

    /**
     * Register an event subscriber
     *
     * @param object|string $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        $subscriber = $this->resolveSubscriber($subscriber);
        
        if (method_exists($subscriber, 'subscribe')) {
            $subscriber->subscribe($this);
        } else {
            $this->registerSubscriberMethods($subscriber);
        }
    }

    /**
     * Register subscriber methods automatically
     *
     * @param object $subscriber
     * @return void
     */
    protected function registerSubscriberMethods($subscriber)
    {
        $reflection = new ReflectionClass($subscriber);
        
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (str_starts_with($method->getName(), 'handle')) {
                $event = $this->getEventFromMethodName($method->getName());
                $this->listen($event, [$subscriber, $method->getName()]);
            }
        }
    }

    /**
     * Get event name from method name
     *
     * @param string $methodName
     * @return string
     */
    protected function getEventFromMethodName($methodName)
    {
        // Convert handleUserLogin to user.login
        $event = substr($methodName, 6); // Remove 'handle'
        $event = preg_replace('/([a-z])([A-Z])/', '$1.$2', $event);
        return strtolower($event);
    }

    /**
     * Fire an event and call the listeners
     *
     * @param string|object $event
     * @param mixed $payload
     * @param bool $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        return $this->dispatchInternal($event, $payload, $halt);
    }

    /**
     * Internal dispatch method for legacy event handling
     *
     * @param string|object $event
     * @param mixed $payload
     * @param bool $halt
     * @return array|null
     */
    protected function dispatchInternal($event, $payload = [], $halt = false)
    {
        [$event, $payload] = $this->parseEventAndPayload($event, $payload);
        
        if ($this->shouldBroadcast($payload)) {
            $this->broadcastEvent($event, $payload);
        }
        
        $responses = [];
        
        // Check if we're already firing this event to prevent infinite loops
        if (in_array($event, $this->firing)) {
            return $halt ? null : $responses;
        }
        
        $this->firing[] = $event;
        
        try {
            // Apply middleware
            $payload = $this->applyMiddleware($event, $payload);
            
            // Get all listeners for this event
            $listeners = $this->getListeners($event);
            
            foreach ($listeners as $listener) {
                $response = $this->callListener($listener, $event, $payload);
                
                if ($halt && !is_null($response)) {
                    array_pop($this->firing);
                    return $response;
                }
                
                if ($response === false) {
                    break;
                }
                
                $responses[] = $response;
            }
            
            // Handle async listeners
            $this->handleAsyncListeners($event, $payload);
            
        } finally {
            array_pop($this->firing);
        }
        
        return $halt ? null : $responses;
    }

    /**
     * Parse the event and payload from the given arguments
     *
     * @param mixed $event
     * @param mixed $payload
     * @return array
     */
    protected function parseEventAndPayload($event, $payload)
    {
        if (is_object($event)) {
            [$payload, $event] = [[$event], get_class($event)];
        }
        
        return [$event, $payload];
    }

    /**
     * Determine if the payload should be broadcasted
     *
     * @param mixed $payload
     * @return bool
     */
    protected function shouldBroadcast($payload)
    {
        return isset($payload[0]) && 
               is_object($payload[0]) && 
               method_exists($payload[0], 'broadcastOn');
    }

    /**
     * Broadcast the event
     *
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    protected function broadcastEvent($event, $payload)
    {
        // Implementation for broadcasting events (WebSockets, etc.)
        // This would integrate with a broadcasting system
    }

    /**
     * Apply middleware to the event
     *
     * @param string $event
     * @param mixed $payload
     * @return mixed
     */
    protected function applyMiddleware($event, $payload)
    {
        if (!isset($this->middleware[$event])) {
            return $payload;
        }
        
        foreach ($this->middleware[$event] as $middleware) {
            $middlewareInstance = $this->resolveMiddleware($middleware);
            $payload = $middlewareInstance->handle($event, $payload);
        }
        
        return $payload;
    }

    /**
     * Resolve middleware instance
     *
     * @param mixed $middleware
     * @return object
     */
    protected function resolveMiddleware($middleware)
    {
        if (is_string($middleware)) {
            return $this->app->make($middleware);
        }
        
        return $middleware;
    }

    /**
     * Get all listeners for an event
     *
     * @param string $eventName
     * @return array
     */
    public function getListeners($eventName)
    {
        $listeners = $this->listeners[$eventName] ?? [];
        
        // Add wildcard listeners
        $listeners = array_merge($listeners, $this->getWildcardListeners($eventName));
        
        return $this->sortListeners($eventName, $listeners);
    }

    /**
     * Get wildcard listeners for an event
     *
     * @param string $eventName
     * @return array
     */
    protected function getWildcardListeners($eventName)
    {
        $wildcards = [];
        
        foreach ($this->wildcards as $key => $listeners) {
            if ($this->eventMatches($key, $eventName)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }
        
        return $wildcards;
    }

    /**
     * Check if event matches wildcard pattern
     *
     * @param string $pattern
     * @param string $eventName
     * @return bool
     */
    protected function eventMatches($pattern, $eventName)
    {
        return fnmatch($pattern, $eventName);
    }

    /**
     * Sort listeners by priority
     *
     * @param string $eventName
     * @param array $listeners
     * @return array
     */
    protected function sortListeners($eventName, $listeners)
    {
        if (empty($listeners)) {
            return [];
        }
        
        krsort($listeners); // Sort by priority (highest first)
        
        return array_merge(...$listeners);
    }

    /**
     * Call a listener (legacy format)
     *
     * @param mixed $listener
     * @param string $event
     * @param mixed $payload
     * @return mixed
     */
    protected function callListener($listener, $event, $payload)
    {
        try {
            // Try to resolve from container if it's a string
            if (is_string($listener) && $this->container && $this->container->has($listener)) {
                $listener = $this->container->get($listener);
            }
            
            // Handle object with handle method
            if (is_object($listener) && method_exists($listener, 'handle')) {
                return $listener->handle($event, $payload);
            }
            
            return $listener($event, $payload);
        } catch (Exception $e) {
            $this->handleListenerException($e, $listener, $event, $payload);
            return null;
        }
    }

    /**
     * Handle listener exception
     *
     * @param Exception $e
     * @param mixed $listener
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    protected function handleListenerException($e, $listener, $event, $payload)
    {
        if ($this->app->has('exception.handler')) {
            $handler = $this->app->make('exception.handler');
            $handler->report($e);
        } else {
            if ($this->logger) {
                $this->logger->error(sprintf(
                    'EventDispatcher: Error processing event %s with listener %s: %s',
                    $event, $listener, $e->getMessage()
                ), ['exception' => $e]);
             } else {
                 // Fallback to error_log if no logger is set
                  \App\Support\Log::error(sprintf(
                     'EventDispatcher: Error processing event %s with listener %s: %s', 
                     $event, $listener, $e->getMessage()
                 ));
             }
        }
    }

    /**
     * Handle async listeners
     *
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    protected function handleAsyncListeners($event, $payload)
    {
        if (!isset($this->asyncHandlers[$event])) {
            return;
        }
        
        foreach ($this->asyncHandlers[$event] as $handler) {
            // In a real implementation, this would use a queue or background job system
            // For now, we'll just call them synchronously
            $this->callListener($handler, $event, $payload);
        }
    }

    /**
     * Make a listener from the given value
     *
     * @param mixed $listener
     * @return Closure
     */
    protected function makeListener($listener)
    {
        if (is_string($listener)) {
            return $this->createClassListener($listener);
        }
        
        if (is_callable($listener)) {
            return function ($event, $payload) use ($listener) {
                return $listener(...array_values($payload));
            };
        }
        
        throw new \InvalidArgumentException('Invalid event listener provided.');
    }

    /**
     * Create a class-based listener
     *
     * @param string $listener
     * @return Closure
     */
    protected function createClassListener($listener)
    {
        return function ($event, $payload) use ($listener) {
            [$class, $method] = $this->parseClassCallable($listener);
            
            $instance = $this->app->make($class);
            
            return $instance->{$method}($event, $payload);
        };
    }

    /**
     * Parse a class callable
     *
     * @param string $listener
     * @return array
     */
    protected function parseClassCallable($listener)
    {
        if (str_contains($listener, '@')) {
            return explode('@', $listener, 2);
        }
        
        return [$listener, 'handle'];
    }

    /**
     * Resolve a subscriber instance
     *
     * @param mixed $subscriber
     * @return object
     */
    protected function resolveSubscriber($subscriber)
    {
        if (is_string($subscriber)) {
            return $this->app->make($subscriber);
        }
        
        return $subscriber;
    }

    /**
     * Resolve a listener instance
     *
     * @param mixed $listener
     * @return callable
     */
    protected function resolveListener($listener)
    {
        if (is_string($listener)) {
            [$class, $method] = $this->parseClassCallable($listener);
            $instance = $this->app->make($class);
            return [$instance, $method];
        }
        
        return $listener;
    }

    /**
     * Add middleware for an event
     *
     * @param string $event
     * @param mixed $middleware
     * @return void
     */
    public function addMiddleware($event, $middleware)
    {
        $this->middleware[$event][] = $middleware;
    }

    /**
     * Set priority for an event
     *
     * @param string $event
     * @param int $priority
     * @return void
     */
    public function setPriority($event, $priority)
    {
        $this->priorities[$event] = $priority;
    }

    /**
     * Set serializer for an event
     *
     * @param string $event
     * @param mixed $serializer
     * @return void
     */
    public function setSerializer($event, $serializer)
    {
        $this->serializers[$event] = $serializer;
    }

    /**
     * Register an event
     *
     * @param string $event
     * @return void
     */
    public function registerEvent($event)
    {
        $this->registeredEvents[] = $event;
    }

    /**
     * Remove a listener
     *
     * @param string $event
     * @param mixed $listener
     * @return void
     */
    public function forget($event, $listener = null)
    {
        if (is_null($listener)) {
            unset($this->listeners[$event]);
        } else {
            // Remove specific listener (more complex implementation needed)
            // This is a simplified version
            $this->listeners[$event] = array_filter(
                $this->listeners[$event] ?? [],
                function ($l) use ($listener) {
                    return $l !== $listener;
                }
            );
        }
    }

    /**
     * Remove all listeners
     *
     * @return void
     */
    public function flush()
    {
        $this->listeners = [];
        $this->wildcards = [];
        $this->middleware = [];
        $this->asyncHandlers = [];
    }

    /**
     * Check if there are listeners for an event
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return !empty($this->getListeners($eventName));
    }

    /**
     * Get all registered events
     *
     * @return array
     */
    public function getEvents()
    {
        return array_unique(array_merge(
            array_keys($this->listeners),
            array_keys($this->wildcards),
            $this->registeredEvents
        ));
    }
}