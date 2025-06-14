<?php

/**
 * Event Configuration
 *
 * This configuration file defines event listeners, subscribers, and middleware
 * for the enhanced event system following the refactoring plan.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Event Listeners
    |--------------------------------------------------------------------------
    |
    | Here you may define event listeners that should be registered with the
    | event dispatcher. You can specify the event name and the listener class
    | or closure. Listeners can also have priorities.
    |
    */
    'listeners' => [
        'app.booting' => [
            // Add application booting listeners here
        ],
        
        'app.booted' => [
            // Add application booted listeners here
        ],
        
        'request.received' => [
            // Add request received listeners here
        ],
        
        'response.sending' => [
            // Add response sending listeners here
        ],
        
        'exception.occurred' => [
            // Add exception handling listeners here
        ],
        
        'database.connecting' => [
            // Add database connection listeners here
        ],
        
        'database.connected' => [
            // Add database connected listeners here
        ],
        
        'cache.hit' => [
            // Add cache hit listeners here
        ],
        
        'cache.miss' => [
            // Add cache miss listeners here
        ],
        
        'session.started' => [
            // Add session started listeners here
        ],
        
        'user.login' => [
            // Add user login listeners here
        ],
        
        'user.logout' => [
            // Add user logout listeners here
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Subscribers
    |--------------------------------------------------------------------------
    |
    | Event subscribers are classes that may subscribe to multiple events
    | within a single class. Subscribers should define a "subscribe" method
    | that accepts an event dispatcher instance.
    |
    */
    'subscribers' => [
        // Add event subscribers here
        // Example: App\Listeners\UserEventSubscriber::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Middleware
    |--------------------------------------------------------------------------
    |
    | Event middleware allows you to filter or modify events before they
    | reach their listeners. Middleware is executed in the order defined.
    |
    */
    'middleware' => [
        // Add global event middleware here
        // Example: App\Middleware\LogEventMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Async Event Handlers
    |--------------------------------------------------------------------------
    |
    | Define which events should be handled asynchronously. This is useful
    | for events that don't need to block the current request/response cycle.
    |
    */
    'async' => [
        // Add async event patterns here
        // Example: 'mail.*', 'notification.*'
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Broadcasting
    |--------------------------------------------------------------------------
    |
    | Configure which events should be broadcasted to external systems
    | like WebSockets, message queues, or other applications.
    |
    */
    'broadcast' => [
        'enabled' => env('EVENT_BROADCAST_ENABLED', false),
        'driver' => env('EVENT_BROADCAST_DRIVER', 'log'),
        'events' => [
            // Add events to broadcast here
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Serialization
    |--------------------------------------------------------------------------
    |
    | Configure how events should be serialized when stored in queues
    | or transmitted to external systems.
    |
    */
    'serialization' => [
        'default' => 'json',
        'serializers' => [
            'json' => \App\Core\Events\Serializers\JsonEventSerializer::class,
            // Add custom serializers here
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Debugging
    |--------------------------------------------------------------------------
    |
    | Enable debugging features for the event system. This is useful
    | during development to track event flow and performance.
    |
    */
    'debug' => [
        'enabled' => env('EVENT_DEBUG_ENABLED', env('APP_DEBUG', false)),
        'log_events' => env('EVENT_LOG_EVENTS', false),
        'track_performance' => env('EVENT_TRACK_PERFORMANCE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Priorities
    |--------------------------------------------------------------------------
    |
    | Define default priorities for different types of events. Higher
    | numbers indicate higher priority (executed first).
    |
    */
    'priorities' => [
        'security' => 1000,
        'authentication' => 900,
        'authorization' => 800,
        'validation' => 700,
        'business_logic' => 500,
        'logging' => 100,
        'cleanup' => 50,
    ],
];