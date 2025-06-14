<?php

/**
 * Middleware Configuration
 * 
 * This file contains the configuration for middleware in the application.
 * You can define aliases, groups, global middleware, and route-specific middleware here.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | Here you can define aliases for middleware classes. This allows you to
    | reference middleware by a short name instead of the full class name.
    |
    */
    'aliases' => [
        'auth' => \App\Middlewares\AuthMiddleware::class,
        'api.auth' => \App\Middlewares\ApiAuthMiddleware::class,
        'cors' => \App\Middlewares\CorsMiddleware::class,
        'csrf' => \App\Middlewares\CsrfMiddleware::class,
        'rate_limit' => \App\Middlewares\RateLimitMiddleware::class,
        'throttle' => \App\Middlewares\RateLimitMiddleware::class,
        'logging' => \App\Middlewares\LoggingMiddleware::class,
        'validation' => \App\Middlewares\ValidationMiddleware::class,
        'role' => \App\Middlewares\RolePermissionMiddleware::class,
        'permission' => \App\Middlewares\RolePermissionMiddleware::class,
        'security' => \App\Middlewares\SecurityMiddleware::class,
        'verified' => \App\Middlewares\VerifyCsrfToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Groups
    |--------------------------------------------------------------------------
    |
    | Here you can define groups of middleware that can be applied together.
    | This is useful for applying multiple middleware to routes at once.
    |
    */
    'groups' => [
        'web' => [
            'security',
            'cors',
            'csrf',
            'logging'
        ],
        
        'api' => [
            'security',
            'cors',
            'throttle:60,1',
            'logging'
        ],
        
        'api.auth' => [
            'security',
            'cors',
            'api.auth',
            'throttle:120,1',
            'logging'
        ],
        
        'auth' => [
            'auth'
        ],
        
        'guest' => [
            'security',
            'cors',
            'csrf'
        ],
        
        'admin' => [
            'auth',
            'role:admin',
            'throttle:200,1',
            'logging'
        ],
        
        'secure' => [
            'security',
            'auth',
            'verified',
            'throttle:100,1',
            'logging'
        ],
        
        'protected' => [
            'security',
            'auth',
            'role',
            'permission'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Middleware
    |--------------------------------------------------------------------------
    |
    | Here you can define middleware that should run on every request.
    | These middleware will be executed before any route-specific middleware.
    |
    */
    'global' => [
        'security',
        'cors',
        'logging'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Middleware Priorities
    |--------------------------------------------------------------------------
    |
    | Define the execution priority for middleware. Higher numbers execute first.
    | This ensures that security middleware runs before authentication, etc.
    |
    */
    'priorities' => [
        'security' => 1000,
        'cors' => 900,
        'csrf' => 800,
        'auth' => 700,
        'api.auth' => 700,
        'role' => 600,
        'permission' => 600,
        'throttle' => 500,
        'rate_limit' => 500,
        'validation' => 400,
        'verified' => 300,
        'logging' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Here you can define middleware that should be applied to specific routes.
    | The key is the route pattern and the value is an array of middleware.
    |
    */
    'routes' => [
        // Authentication routes
        '/auth/*' => ['guest'],
        '/login' => ['guest'],
        '/register' => ['guest'],
        '/logout' => ['auth'],
        
        // API routes
        '/api/*' => ['api'],
        
        // Admin routes
        '/admin/*' => ['admin'],
        
        // User dashboard
        '/dashboard/*' => ['auth'],
        '/profile/*' => ['auth'],
        
        // Secure operations
        '/payment/*' => ['secure'],
        '/subscription/*' => ['secure'],
        
        // Forms that need CSRF protection
        '/contact' => ['web'],
        '/feedback' => ['web'],
        
        // Rate-limited endpoints
        '/api/reports/*' => ['rate_limit'],
        '/api/compatibility/*' => ['rate_limit'],
        
        // Validation-required endpoints
        '/auth/register' => ['validation'],
        '/auth/login' => ['validation'],
        '/contact' => ['validation'],
        '/feedback' => ['validation'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can define configuration for specific middleware.
    |
    */
    'config' => [
        'auth' => [
            'enabled' => true,
            'skip_routes' => [
                '/',
                '/home',
                '/about',
                '/contact',
                '/api/public/*',
                '/auth/*',
                '/assets/*',
                '/css/*',
                '/js/*',
                '/images/*'
            ],
            'redirect_to' => '/login',
            'session_key' => 'user_id',
            'token_header' => 'Authorization',
            'token_prefix' => 'Bearer '
        ],
        
        'cors' => [
            'enabled' => true,
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'exposed_headers' => [],
            'max_age' => 86400,
            'supports_credentials' => false,
            'skip_routes' => []
        ],
        
        'csrf' => [
            'enabled' => true,
            'token_name' => '_token',
            'header_name' => 'X-CSRF-TOKEN',
            'meta_name' => 'csrf-token',
            'session_key' => '_csrf_token',
            'token_lifetime' => 3600, // 1 hour
            'skip_routes' => [
                '/api/*',
                '/webhook/*'
            ],
            'skip_methods' => ['GET', 'HEAD', 'OPTIONS']
        ],
        
        'rate_limit' => [
            'enabled' => true,
            'default_limit' => 60,
            'default_window' => 60, // seconds
            'cache_directory' => 'storage/cache/rate_limit',
            'ip_whitelist' => [
                '127.0.0.1',
                '::1'
            ],
            'skip_routes' => [
                '/assets/*',
                '/css/*',
                '/js/*',
                '/images/*'
            ],
            'limits' => [
                '/api/auth/login' => ['limit' => 5, 'window' => 300], // 5 attempts per 5 minutes
                '/api/auth/register' => ['limit' => 3, 'window' => 3600], // 3 attempts per hour
                '/api/reports/*' => ['limit' => 100, 'window' => 3600], // 100 requests per hour
                '/api/compatibility/*' => ['limit' => 200, 'window' => 3600] // 200 requests per hour
            ]
        ],
        
        'logging' => [
            'enabled' => true,
            'log_requests' => true,
            'log_responses' => true,
            'log_slow_requests' => true,
            'slow_request_threshold' => 1000, // milliseconds
            'log_memory_usage' => true,
            'memory_threshold' => 50 * 1024 * 1024, // 50MB
            'skip_paths' => [
                '/assets/*',
                '/css/*',
                '/js/*',
                '/images/*',
                '/favicon.ico'
            ],
            'skip_user_agents' => [
                'bot',
                'crawler',
                'spider'
            ],
            'sensitive_headers' => [
                'authorization',
                'cookie',
                'x-api-key'
            ],
            'sensitive_fields' => [
                'password',
                'password_confirmation',
                'token',
                'secret',
                'api_key'
            ]
        ],
        
        'validation' => [
            'enabled' => true,
            'skip_get_requests' => true,
            'skip_paths' => [
                '/assets/*',
                '/css/*',
                '/js/*',
                '/images/*'
            ],
            'rules' => [
                '/auth/login' => [
                    'email' => 'required|email',
                    'password' => 'required|min:6'
                ],
                '/auth/register' => [
                    'name' => 'required|string|min:2|max:50',
                    'email' => 'required|email|unique:users',
                    'password' => 'required|min:8|confirmed',
                    'password_confirmation' => 'required'
                ],
                '/contact' => [
                    'name' => 'required|string|min:2|max:100',
                    'email' => 'required|email',
                    'subject' => 'required|string|min:5|max:200',
                    'message' => 'required|string|min:10|max:1000'
                ],
                '/feedback' => [
                    'rating' => 'required|integer|min:1|max:5',
                    'comment' => 'required|string|min:10|max:500',
                    'email' => 'email'
                ]
            ]
        ],
        
        'security' => [
            'enabled' => true,
            'ip_whitelist' => [],
            'ip_blacklist' => [],
            'max_request_size' => 10485760, // 10MB
            'allowed_file_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            'blocked_user_agents' => ['bot', 'crawler', 'spider'],
            'honeypot_field' => 'website',
            'enable_xss_protection' => true,
            'enable_sql_injection_protection' => true,
            'security_headers' => [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
                'X-XSS-Protection' => '1; mode=block',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Content-Security-Policy' => "default-src 'self'"
            ]
        ],
        
        'api.auth' => [
            'enabled' => true,
            'token_header' => 'Authorization',
            'token_prefix' => 'Bearer ',
            'jwt_secret' => 'your-secret-key-here', // Replace with actual secret in production
            'jwt_algorithm' => 'HS256',
            'token_ttl' => 3600, // 1 hour
            'refresh_ttl' => 604800, // 1 week
            'blacklist_enabled' => true,
            'required_scopes' => [],
            'skip_routes' => [
                '/api/public/*',
                '/api/auth/login',
                '/api/auth/register'
            ]
        ],
        
        'role' => [
            'enabled' => true,
            'cache_permissions' => true,
            'cache_ttl' => 3600,
            'super_admin_role' => 'super_admin',
            'default_role' => 'user',
            'check_ownership' => true,
            'ownership_field' => 'user_id'
        ]
    ]
];