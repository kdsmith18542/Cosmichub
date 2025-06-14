# Cosmichub Enhanced Middleware System

A comprehensive and flexible middleware system for the Cosmichub PHP framework, providing powerful request/response processing capabilities with advanced features.

## 🚀 Quick Start

### Basic Usage

```php
// Apply middleware to a route
$router->get('/dashboard', 'DashboardController@index')->middleware('auth');

// Multiple middleware
$router->get('/admin', 'AdminController@index')
    ->middleware(['auth', 'role:admin']);

// Middleware groups
$router->group(['middleware' => 'web'], function($router) {
    $router->get('/', 'HomeController@index');
});
```

### API Authentication

```php
// JWT-based API authentication
$router->group(['middleware' => 'api.auth'], function($router) {
    $router->get('/user', 'UserController@profile');
    $router->post('/posts', 'PostController@store');
});

// With required scopes
$router->get('/admin/users', 'AdminController@users')
    ->middleware('api.auth:admin,users:read');
```

## 🛡️ Security Features

### Built-in Security Middleware

- **IP Filtering**: Whitelist/blacklist IP addresses
- **User Agent Checking**: Block suspicious user agents
- **Request Size Limits**: Prevent oversized requests
- **XSS Protection**: Sanitize input and add security headers
- **SQL Injection Protection**: Detect and block malicious queries
- **Honeypot Detection**: Catch automated spam attempts
- **File Upload Validation**: Secure file upload handling

### Rate Limiting

```php
// Basic rate limiting (60 requests per minute)
$router->get('/api/data', 'ApiController@data')->middleware('throttle:60,1');

// Different limits for different endpoints
$router->post('/api/upload', 'ApiController@upload')->middleware('throttle:10,1');
```

## 🔐 Authentication & Authorization

### Session-based Authentication

```php
// Basic authentication
$router->get('/profile', 'UserController@profile')->middleware('auth');

// With role requirement
$router->get('/admin', 'AdminController@index')->middleware('auth:admin');

// Email verification required
$router->get('/verified-only', 'Controller@method')->middleware('auth:any,verified');
```

### Role & Permission System

```php
// Role-based access
$router->get('/admin', 'AdminController@index')->middleware('role:admin');

// Permission-based access
$router->post('/posts', 'PostController@store')->middleware('permission:posts:create');

// Multiple roles
$router->get('/moderator', 'ModeratorController@index')
    ->middleware('role:admin,moderator');

// Ownership check
$router->put('/posts/{id}', 'PostController@update')
    ->middleware('permission:posts:edit,owner');
```

## ⚙️ Configuration

### Middleware Aliases

```php
// config/middleware.php
'aliases' => [
    'auth' => \App\Middlewares\AuthMiddleware::class,
    'api.auth' => \App\Middlewares\ApiAuthMiddleware::class,
    'security' => \App\Middlewares\SecurityMiddleware::class,
    'role' => \App\Middlewares\RolePermissionMiddleware::class,
    'throttle' => \App\Middlewares\RateLimitMiddleware::class,
],
```

### Middleware Groups

```php
'groups' => [
    'web' => ['security', 'cors', 'csrf', 'logging'],
    'api' => ['security', 'cors', 'throttle:60,1', 'logging'],
    'admin' => ['auth', 'role:admin', 'throttle:200,1'],
    'secure' => ['security', 'csrf', 'throttle:30,1'],
],
```

### Global Middleware

```php
'global' => ['security', 'cors', 'logging'],
```

### Middleware Priorities

```php
'priorities' => [
    'security' => 1000,    // Highest priority
    'cors' => 900,
    'csrf' => 800,
    'auth' => 700,
    'role' => 600,
    'throttle' => 500,
    'logging' => 100,      // Lowest priority
],
```

## 🔧 Advanced Features

### Conditional Middleware

```php
// Apply middleware based on conditions
$pipeline = $manager->when(function($request) {
    return $request->getMethod() === 'POST';
});

// Route-specific conditions
$pipeline = $manager->forRoutes(['/admin/*', '/dashboard/*']);

// Method-specific conditions
$pipeline = $manager->forMethods(['POST', 'PUT', 'DELETE']);
```

### Custom Middleware Pipeline

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

// Returns detailed information about:
// - Number of registered aliases
// - Number of middleware groups
// - Global middleware count
// - Priority configurations
```

## 📝 Creating Custom Middleware

### Basic Middleware

```php
use App\Core\Middleware\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;

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

### Parameterized Middleware

```php
class ParameterizedMiddleware implements MiddlewareInterface
{
    private $parameters;
    
    public function handle(Request $request, callable $next, ...$parameters): Response
    {
        $this->parameters = $parameters;
        
        // Use parameters in your logic
        $requiredRole = $parameters[0] ?? 'user';
        $permission = $parameters[1] ?? null;
        
        // Your middleware logic here
        
        return $next($request);
    }
}
```

## 🧪 Testing

### Running Tests

```bash
# Run all middleware tests
php tests/MiddlewareSystemTest.php

# Test specific middleware
php tests/AuthMiddlewareTest.php
php tests/SecurityMiddlewareTest.php
```

### Test Example

```php
class CustomMiddlewareTest extends TestCase
{
    public function testMiddlewareExecution()
    {
        $middleware = new CustomMiddleware();
        $request = new Request();
        
        $response = $middleware->handle($request, function($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-Custom-Header'));
    }
}
```

## 🔍 Debugging

### Enable Debug Mode

```php
// Enable middleware debugging
$manager->enableDebug();

// Get execution statistics
$stats = $manager->getExecutionStats();

// Enable logging
$manager->enableLogging();
```

### Common Issues

1. **Middleware Not Executing**
   - Check middleware registration
   - Verify alias spelling
   - Ensure class exists and is autoloaded

2. **Incorrect Execution Order**
   - Review middleware priorities
   - Check global vs. route-specific middleware

3. **Authentication Issues**
   - Verify session configuration
   - Check JWT token validation
   - Review middleware parameters

## 📚 Available Middleware

| Middleware | Alias | Description |
|------------|-------|-------------|
| AuthMiddleware | `auth` | Session-based authentication |
| ApiAuthMiddleware | `api.auth` | JWT-based API authentication |
| SecurityMiddleware | `security` | Comprehensive security features |
| RolePermissionMiddleware | `role` | Role and permission-based access |
| RateLimitMiddleware | `throttle` | Request rate limiting |
| ValidationMiddleware | `validate` | Request validation |
| CorsMiddleware | `cors` | Cross-Origin Resource Sharing |
| CsrfMiddleware | `csrf` | CSRF protection |
| LoggingMiddleware | `logging` | Request/response logging |

## 🛠️ Configuration Examples

### Security Configuration

```php
'security' => [
    'ip_whitelist' => ['127.0.0.1', '192.168.1.0/24'],
    'ip_blacklist' => ['10.0.0.0/8'],
    'blocked_user_agents' => ['bot', 'crawler'],
    'max_request_size' => 10485760, // 10MB
    'honeypot_field' => '_honeypot',
    'enable_xss_protection' => true,
    'enable_sql_injection_protection' => true,
    'security_headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
    ],
],
```

### API Authentication Configuration

```php
'api.auth' => [
    'jwt_secret' => env('JWT_SECRET'),
    'jwt_algorithm' => 'HS256',
    'token_header' => 'Authorization',
    'token_prefix' => 'Bearer ',
    'check_blacklist' => true,
    'require_verified_email' => false,
    'cache_user' => true,
    'cache_ttl' => 3600,
],
```

### Rate Limiting Configuration

```php
'throttle' => [
    'default_limit' => 60,
    'default_window' => 1,
    'cache_driver' => 'redis',
    'ip_whitelist' => ['127.0.0.1'],
    'route_limits' => [
        'api/upload' => [10, 1],
        'api/search' => [100, 1],
        'auth/login' => [5, 1],
    ],
],
```

## 📖 Documentation

For detailed documentation, see:
- [Full Documentation](docs/MIDDLEWARE_SYSTEM.md)
- [API Reference](docs/api/middleware.md)
- [Examples](examples/middleware/)

## 🤝 Contributing

Contributions are welcome! Please read our contributing guidelines and submit pull requests for any improvements.

## 📄 License

This middleware system is part of the Cosmichub framework and is licensed under the MIT License.