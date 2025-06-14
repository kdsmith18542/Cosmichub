<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */
    'default' => env('CACHE_DRIVER', 'file'),
    
    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing a RAM based store such as APC or Memcached, there might
    | be other applications utilizing the same cache. So, we'll specify a
    | value to get prefixed to all our keys so we can avoid collisions.
    |
    */
    'prefix' => env('CACHE_PREFIX', 'cosmichub_cache'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Enhanced with CosmicHub-specific stores and configurations.
    |
    */
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache'),
            'permissions' => 0755,
        ],
        
        'array' => [
            'driver' => 'array',
            'max_items' => 1000,
            'serialize' => false,
        ],
        
        'null' => [
            'driver' => 'null',
        ],
        
        // High-performance store for frequently accessed data
        'memory' => [
            'driver' => 'array',
            'max_items' => 500,
        ],
        
        // Long-term storage for less frequently accessed data
        'persistent' => [
            'driver' => 'file',
            'path' => storage_path('cache/persistent'),
            'permissions' => 0755,
        ],
        
        // Session-specific cache
        'session' => [
            'driver' => 'array',
            'max_items' => 100,
        ],
        
        // API response cache
        'api' => [
            'driver' => 'file',
            'path' => storage_path('cache/api'),
            'permissions' => 0755,
        ],
        
        // View cache for compiled templates
        'views' => [
            'driver' => 'file',
            'path' => storage_path('cache/views'),
            'permissions' => 0755,
        ],
        
        // Configuration cache
        'config' => [
            'driver' => 'file',
            'path' => storage_path('cache/config'),
            'permissions' => 0755,
        ],
        
        // Legacy stores for compatibility
        'apc' => [
            'driver' => 'apc',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Additional cache settings and behaviors.
    |
    */
    'settings' => [
        // Default TTL in seconds (1 hour)
        'default_ttl' => 3600,
        
        // Enable cache statistics collection
        'collect_stats' => env('CACHE_COLLECT_STATS', true),
        
        // Automatically clear expired items
        'auto_cleanup' => env('CACHE_AUTO_CLEANUP', true),
        
        // Cleanup interval in seconds (1 hour)
        'cleanup_interval' => 3600,
        
        // Maximum cache size per store (in MB, 0 = unlimited)
        'max_size' => env('CACHE_MAX_SIZE', 100),
        
        // Enable cache compression for file stores
        'compress' => env('CACHE_COMPRESS', false),
        
        // Serialization method (serialize, json, igbinary)
        'serializer' => env('CACHE_SERIALIZER', 'serialize'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Define cache tag groups for easier cache invalidation.
    |
    */
    'tags' => [
        'user' => ['user_data', 'user_preferences', 'user_sessions'],
        'content' => ['posts', 'pages', 'comments'],
        'api' => ['api_responses', 'external_data'],
        'system' => ['config', 'routes', 'permissions'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize cache performance.
    |
    */
    'performance' => [
        // Enable cache warming on application boot
        'warm_cache' => env('CACHE_WARM_ON_BOOT', false),
        
        // Items to warm up (key => ttl)
        'warm_items' => [
            // 'config.app' => 86400,
            // 'routes.compiled' => 86400,
        ],
        
        // Enable cache preloading for frequently accessed items
        'preload' => env('CACHE_PRELOAD', false),
        
        // Preload patterns
        'preload_patterns' => [
            // 'user.*',
            // 'config.*',
        ],
        
        // Enable cache write-behind for better performance
        'write_behind' => env('CACHE_WRITE_BEHIND', false),
        
        // Write-behind queue size
        'write_behind_queue_size' => 100,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to development environment.
    |
    */
    'development' => [
        // Disable caching in development
        'disable_in_dev' => env('CACHE_DISABLE_IN_DEV', false),
        
        // Log cache operations for debugging
        'log_operations' => env('CACHE_LOG_OPERATIONS', false),
        
        // Cache debug information
        'debug' => env('CACHE_DEBUG', false),
        
        // Show cache statistics in debug bar
        'show_stats' => env('CACHE_SHOW_STATS', true),
    ],
];