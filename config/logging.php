<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */
    'default' => env('LOG_CHANNEL', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated functionality that is being used by your application.
    | This allows you to get your application ready for upcoming major versions.
    |
    */
    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, this package uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/cosmichub.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_RETENTION_DAYS', 14),
        ],

        'error' => [
            'driver' => 'single',
            'path' => storage_path('logs/error.log'),
            'level' => 'error',
        ],

        'security' => [
            'driver' => 'single',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
        ],

        'performance' => [
            'driver' => 'single',
            'path' => storage_path('logs/performance.log'),
            'level' => 'info',
        ],

        'query' => [
            'driver' => 'single',
            'path' => storage_path('logs/query.log'),
            'level' => 'debug',
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'CosmicHub',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => \Monolog\Handler\SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => \Monolog\Handler\StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/cosmichub.log'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Level
    |--------------------------------------------------------------------------
    |
    | This option defines the minimum log level that will be written to the
    | log files. Available levels: emergency, alert, critical, error,
    | warning, notice, info, debug
    |
    */
    'level' => env('LOG_LEVEL', 'debug'),

    /*
    |--------------------------------------------------------------------------
    | Log Retention
    |--------------------------------------------------------------------------
    |
    | This option defines how many days to keep log files when using the
    | daily log channel. Older files will be automatically deleted.
    |
    */
    'days' => env('LOG_RETENTION_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Emergency Contacts
    |--------------------------------------------------------------------------
    |
    | When critical errors occur, you may want to notify administrators.
    | Configure email addresses here for emergency notifications.
    |
    */
    'emergency_contacts' => [
        // 'admin@cosmichub.local',
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Context
    |--------------------------------------------------------------------------
    |
    | Additional context to include with every log entry. This can be useful
    | for tracking requests, user sessions, or other application state.
    |
    */
    'context' => [
        'include_request_id' => env('LOG_INCLUDE_REQUEST_ID', true),
        'include_user_id' => env('LOG_INCLUDE_USER_ID', true),
        'include_ip' => env('LOG_INCLUDE_IP', true),
        'include_user_agent' => env('LOG_INCLUDE_USER_AGENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Logging
    |--------------------------------------------------------------------------
    |
    | Configure performance logging thresholds. Slow operations will be
    | automatically logged when they exceed these thresholds.
    |
    */
    'performance' => [
        'slow_query_threshold' => env('LOG_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        'slow_request_threshold' => env('LOG_SLOW_REQUEST_THRESHOLD', 5000), // milliseconds
        'memory_usage_threshold' => env('LOG_MEMORY_THRESHOLD', 128), // MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Logging
    |--------------------------------------------------------------------------
    |
    | Configure what security events should be logged automatically.
    |
    */
    'security_events' => [
        'failed_logins' => env('LOG_FAILED_LOGINS', true),
        'successful_logins' => env('LOG_SUCCESSFUL_LOGINS', false),
        'password_changes' => env('LOG_PASSWORD_CHANGES', true),
        'permission_denied' => env('LOG_PERMISSION_DENIED', true),
        'suspicious_activity' => env('LOG_SUSPICIOUS_ACTIVITY', true),
    ],
];