# Enhanced Middleware System Documentation

The Cosmichub framework features a comprehensive and flexible middleware system that provides powerful request/response processing capabilities with advanced features like priority management, conditional execution, and pipeline management.

## Table of Contents

1. [Overview](#overview)
2. [Core Components](#core-components)
3. [Middleware Types](#middleware-types)
4. [Configuration](#configuration)
5. [Usage Examples](#usage-examples)
6. [Advanced Features](#advanced-features)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

## Overview

The middleware system in Cosmichub provides a layered approach to handling HTTP requests and responses. It allows you to:

- Filter and modify requests before they reach your application logic
- Process and modify responses before they're sent to the client
- Implement cross-cutting concerns like authentication, logging, and security
- Create reusable components that can be applied to multiple routes
- Manage complex middleware chains with priorities and conditions

## Core Components

### MiddlewareManager

The central component that manages middleware registration, aliases, groups, and execution.

```php
use App\Core\Middleware\MiddlewareManager;

$manager = new MiddlewareManager($container);
```

### MiddlewarePipeline

Manages complex middleware chains and conditional middleware execution.

```php
use App\Core\Middleware\MiddlewarePipeline;

$pipeline = new MiddlewarePipeline($container, $resolver);
```

### MiddlewareResolver

Resolves middleware from various formats (strings, classes, closures, objects).

```php
use App\Core\Middleware\MiddlewareResolver;

$resolver = new MiddlewareResolver($container);
```

## Middleware Types

### 1. Authentication Middleware

#### AuthMiddleware
Handles traditional session-based authentication.

```php
// Usage in routes
$router->get('/dashboard', 'DashboardController@index')->middleware('auth');

// With parameters
$router->get('/admin', 'AdminController@index')->middleware('auth:admin,verified');
```

#### ApiAuthMiddleware
Handles JWT-based API authentication.

```php
// API routes
$router->group(['middleware' => 'api.auth'], function($router) {
    $router->get('/user', 'UserController@profile');
    $router->post('/posts', 'PostController@store');
});

// With required scopes
$router->get('/admin/users', 'AdminController@users')->middleware('api.auth:admin,users:read');
```

### 2. Security Middleware

#### SecurityMiddleware
Provides comprehensive security features.

```php
// Applied globally
'global' => ['security']

// Features include:
// - IP filtering (whitelist/blacklist)
// - User agent checking
// - Request size limits
// - Honeypot field detection
// - XSS and SQL injection protection
// - Security headers
```

#### CorsMiddleware
Handles Cross-Origin Resource Sharing.

```php
// Configuration in config/middleware.php
'cors' => [
    'allowed_origins' => ['https://example.com'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
]
```

### 3. Authorization Middleware

#### RolePermissionMiddleware
Handles role and permission-based access control.

```php
// Role-based access
$router->get('/admin', 'AdminController@index')->middleware('role:admin');

// Permission-based access
$router->post('/posts', 'PostController@store')->middleware('permission:posts:create');

// Multiple roles
$router->get('/moderator', 'ModeratorController@index')->middleware('role:admin,moderator');

// Combined role and permission
$router->delete('/posts/{id}', 'PostController@destroy')
    ->middleware(['role:admin', 'permission:posts:delete']);
```

### 4. Rate Limiting Middleware

#### RateLimitMiddleware
Provides request rate limiting and throttling.

```php
// Basic rate limiting
$router->get('/api/data', 'ApiController@data')->middleware('throttle:60,1');

// Different limits for different routes
$router->post('/api/upload', 'ApiController@upload')->middleware('throttle:10,1');

// Named rate limiters
$router->group(['middleware' => 'throttle:api'], function($router) {
    // API routes
});
```

### 5. Validation Middleware

#### ValidationMiddleware
Provides request validation.

```php
// Route-specific validation
$router->post('/contact', 'ContactController@store')->middleware('validate:contact');

// Validation rules defined in config/middleware.php
'validation' => [
    'rules' => [
        'contact' => [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email',
            'message' => 'required|string|min:10|max:1000'
        ]
    ]
]
```

## Configuration

### Basic Configuration

Middleware is configured in `config/middleware.php`:

```php
return [
    // Middleware aliases
    'aliases' => [
        'auth' => \App\Middlewares\AuthMiddleware::class,
        'api.auth' => \App\Middlewares\ApiAuthMiddleware::class,
        'security' => \App\Middlewares\SecurityMiddleware::class,
    ],
    
    // Middleware groups
    'groups' => [
        'web' => ['security', 'cors', 'csrf', 'logging'],
        'api' => ['security', 'cors', 'throttle:60,1', 'logging'],
        'admin' => ['auth', 'role:admin', 'throttle:200,1'],
    ],
    
    // Global middleware
    'global' => ['security', 'cors', 'logging'],
    
    // Middleware priorities
    'priorities' => [
        'security' => 1000,
        'cors' => 900,
        'csrf' => 800,
        'auth' => 700,
        'role' => 600,
        'throttle' => 500,
        'logging' => 100,
    ],
];
```

### Advanced Configuration

```php
// Conditional middleware
'conditional' => [
    'csrf' => [
        'condition' => function($request) {
            return !$request->is('api/*');
        },
    ],
],

// Route-specific middleware
'route_specific' => [
    'admin/*' => ['throttle:50,1', 'role:admin'],
    'api/*' => ['throttle:60,1'],
],
```

## Usage Examples

### Basic Route Middleware

```php
// Single middleware
$router->get('/profile', 'UserController@profile')->middleware('auth');

// Multiple middleware
$router->get('/admin', 'AdminController@index')
    ->middleware(['auth', 'role:admin']);

// Middleware with parameters
$router->post('/api/data', 'ApiController@store')
    ->middleware('throttle:30,1');
```

### Middleware Groups

```php
// Apply middleware group to routes
$router->group(['middleware' => 'web'], function($router) {
    $router->get('/', 'HomeController@index');
    $router->get('/about', 'PageController@about');
});

// API routes with authentication
$router->group(['middleware' => 'api.auth'], function($router) {
    $router->get('/user', 'UserController@profile');
    $router->post('/posts', 'PostController@store');
});
```

### Conditional Middleware

```php
// Apply middleware based on conditions
$pipeline = $manager->when(function($request) {
    return $request->getMethod() === 'POST';
});

// Apply to specific routes
$pipeline = $manager->forRoutes(['/admin/*', '/dashboard/*']);

// Apply to specific HTTP methods
$pipeline = $manager->forMethods(['POST', 'PUT', 'DELETE']);
```

### Custom Middleware

```php
class CustomMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Pre-processing
        if (!$this->shouldProcess($request)) {
            return new Response('Forbidden', 403);
        }
        
        // Continue to next middleware
        $response = $next($request);
        
        // Post-processing
        $response->headers->set('X-Custom-Header', 'value');
        
        return $response;
    }
    
    private function shouldProcess(Request $request): bool
    {
        // Custom logic
        return true;
    }
}
```

## Advanced Features

### Priority Management

Middleware execution order is controlled by priorities:

```php
// Set custom priorities
$manager->setPriority('custom', 750);

// Get all priorities
$priorities = $manager->getPriorities();
```

### Global Middleware Management

```php
// Add global middleware
$manager->addGlobalMiddleware('security', 1000);
$manager->addGlobalMiddleware('logging', 100);

// Remove global middleware
$manager->removeGlobalMiddleware('logging');

// Get global middleware
$global = $manager->getGlobalMiddleware();
```

### Pipeline Management

```php
// Create custom pipeline
$pipeline = $manager->createPipeline();

// Add middleware with priorities
$pipeline->add('security', 1000);
$pipeline->add('auth', 700);

// Add conditional middleware
$pipeline->addIf('csrf', function($request) {
    return $request->getMethod() === 'POST';
}, 800);

// Execute pipeline
$response = $pipeline->execute($request, $finalHandler);
```

### Middleware Statistics

```php
// Get system statistics
$stats = $manager->getStats();

// Returns:
// [
//     'aliases_count' => 10,
//     'groups_count' => 6,
//     'global_middleware_count' => 3,
//     'priorities_count' => 8,
//     'registered_aliases' => [...],
//     'registered_groups' => [...]
// ]
```

## Best Practices

### 1. Middleware Organization

- Keep middleware focused on a single responsibility
- Use descriptive names for middleware aliases
- Group related middleware together
- Document middleware parameters and behavior

### 2. Performance Optimization

- Set appropriate priorities to minimize unnecessary processing
- Use conditional middleware to avoid running unnecessary checks
- Cache middleware instances when possible
- Profile middleware performance in production

### 3. Security Considerations

- Always apply security middleware with high priority
- Validate and sanitize middleware parameters
- Log security-related middleware actions
- Regularly review and update security middleware

### 4. Error Handling

- Implement proper error handling in custom middleware
- Use appropriate HTTP status codes
- Log middleware errors for debugging
- Provide fallback behavior when possible

### 5. Testing

- Write unit tests for custom middleware
- Test middleware integration with routes
- Test error conditions and edge cases
- Use the provided test utilities

## Troubleshooting

### Common Issues

#### 1. Middleware Not Executing

- Check middleware registration in configuration
- Verify middleware alias spelling
- Ensure middleware class exists and is autoloaded
- Check middleware priorities

#### 2. Incorrect Execution Order

- Review middleware priorities
- Check global vs. route-specific middleware
- Verify middleware group definitions

#### 3. Performance Issues

- Profile middleware execution times
- Check for unnecessary middleware on routes
- Optimize middleware logic
- Use conditional middleware appropriately

#### 4. Authentication Issues

- Verify session configuration
- Check JWT token validation
- Review authentication middleware parameters
- Ensure proper error handling

### Debugging

```php
// Enable middleware debugging
$manager->enableDebug();

// Get execution statistics
$stats = $manager->getExecutionStats();

// Log middleware execution
$manager->enableLogging();
```

### Error Messages

- `MiddlewareNotFoundException`: Middleware alias not found
- `InvalidMiddlewareException`: Middleware class doesn't implement interface
- `CircularDependencyException`: Circular dependency in middleware chain
- `MiddlewareExecutionException`: Error during middleware execution

## Conclusion

The enhanced middleware system in Cosmichub provides a powerful and flexible foundation for handling HTTP requests and responses. With features like priority management, conditional execution, and comprehensive security middleware, you can build robust and secure web applications.

For more information, see the API documentation and example implementations in the codebase.