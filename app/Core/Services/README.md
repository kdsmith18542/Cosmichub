# Service Layer Architecture

This directory contains the core service layer architecture for the Cosmichub application. The service layer provides a robust, flexible, and extensible foundation for managing application services, dependencies, and business logic.

## Overview

The service layer consists of several key components:

- **Container**: Dependency injection container with PSR-11 compliance
- **Service Manager**: High-level service and provider management
- **Service Registry**: Service registration, discovery, and lifecycle management
- **Service Factory**: Service creation and configuration with templates
- **Service Providers**: Service registration and bootstrapping
- **Base Service**: Abstract base class for all services
- **Contracts**: Interfaces defining service layer contracts

## Architecture Components

### 1. Container (`Container.php`)

The dependency injection container is the foundation of the service layer. It provides:

- **PSR-11 Compliance**: Implements `Psr\Container\ContainerInterface`
- **Singleton Pattern**: Ensures single instance throughout application
- **Automatic Resolution**: Resolves dependencies using reflection
- **Alias Support**: Create aliases for services
- **Shared Instances**: Singleton and instance management

```php
// Basic usage
$container = Container::getInstance();

// Bind a service
$container->bind('logger', LoggerService::class);

// Bind as singleton
$container->singleton('cache', CacheService::class);

// Bind an instance
$container->instance('config', new ConfigService());

// Resolve a service
$logger = $container->get('logger');
```

### 2. Service Manager (`ServiceManager.php`)

Manages service providers and handles the application bootstrap process:

- **Provider Registration**: Register and manage service providers
- **Deferred Loading**: Support for lazy-loaded services
- **Lifecycle Hooks**: Execute callbacks during bootstrap phases
- **Statistics**: Track service usage and performance

```php
$manager = new ServiceManager($container);

// Register a provider
$manager->register(DatabaseServiceProvider::class);

// Boot all providers
$manager->boot();

// Get a service
$database = $manager->get('database');
```

### 3. Service Registry (`ServiceRegistry.php`)

Provides advanced service registration and organization features:

- **Service Organization**: Tag and group services
- **Decorators**: Add functionality to existing services
- **Middleware**: Process service calls
- **Metadata**: Store additional service information

```php
$registry = new ServiceRegistry($container);

// Register with tags
$registry->register('user.repository', UserRepository::class)
    ->tag(['repository', 'user']);

// Get services by tag
$repositories = $registry->getByTag('repository');

// Add decorator
$registry->decorate('user.service', function($service) {
    return new CachedUserService($service);
});
```

### 4. Service Factory (`ServiceFactory.php`)

Provides service creation with templates and configuration:

- **Service Templates**: Pre-configured service blueprints
- **Configuration Management**: Merge and validate configurations
- **Dependency Resolution**: Automatic dependency injection
- **Fluent Builder**: Chain configuration methods

```php
$factory = new ServiceFactory($container);

// Create from template
$service = $factory->createFromTemplate('crud', [
    'model' => User::class,
    'repository' => UserRepository::class
]);

// Use builder
$service = $factory->builder(UserService::class)
    ->withConfig(['cache_ttl' => 3600])
    ->withTags(['user', 'service'])
    ->singleton()
    ->build('user.service');
```

### 5. Service Provider (`ServiceProvider.php`)

Base class for organizing service registrations:

- **Registration Phase**: Bind services to container
- **Boot Phase**: Initialize services after all providers are registered
- **Deferred Loading**: Register services only when needed
- **Configuration**: Access application configuration

```php
class UserServiceProvider extends ServiceProvider
{
    protected $provides = [
        'user.service',
        'user.repository'
    ];
    
    public function register()
    {
        $this->singleton('user.repository', UserRepository::class);
        
        $this->bind('user.service', function($container) {
            return new UserService(
                $container->get('user.repository'),
                $container->get('cache')
            );
        });
    }
    
    public function boot()
    {
        // Initialize after all services are registered
        $this->get('user.service')->initialize();
    }
}
```

### 6. Base Service (`Service.php`)

Abstract base class providing common service functionality:

- **Dependency Injection**: Automatic container integration
- **Logging**: Built-in logging capabilities
- **Events**: Event dispatching support
- **Validation**: Data validation with custom rules
- **Caching**: Service-level caching
- **Transactions**: Database transaction support

```php
class UserService extends Service
{
    protected $validationRules = [
        'create' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users'
        ]
    ];
    
    public function createUser(array $data)
    {
        return $this->execute('create_user', function() use ($data) {
            // Validate data
            $this->validate($data, 'create');
            
            // Create user in transaction
            return $this->transaction(function() use ($data) {
                $user = $this->service('user.repository')->create($data);
                
                // Dispatch event
                $this->dispatch('user.created', $user);
                
                return $user;
            });
        });
    }
}
```

## Service Templates

The factory includes several built-in templates:

### CRUD Template
For basic CRUD operations:
```php
$service = $factory->createFromTemplate('crud', [
    'model' => User::class,
    'repository' => UserRepository::class,
    'validation' => UserValidation::class
]);
```

### API Template
For API services:
```php
$service = $factory->createFromTemplate('api', [
    'base_url' => 'https://api.example.com',
    'timeout' => 30,
    'retry_attempts' => 3
]);
```

### Job Template
For background jobs:
```php
$service = $factory->createFromTemplate('job', [
    'queue' => 'default',
    'delay' => 0,
    'max_attempts' => 3
]);
```

## Exception Handling

The service layer includes comprehensive exception handling:

- **ServiceException**: Base service exception with context
- **ContainerException**: Container-specific errors
- **ContainerNotFoundException**: Service not found errors

```php
try {
    $service = $container->get('nonexistent');
} catch (ContainerNotFoundException $e) {
    // Handle service not found
} catch (ServiceException $e) {
    // Handle service-specific errors
    $context = $e->getContext();
    $errorType = $e->getErrorType();
}
```

## Configuration

Services can be configured through various methods:

### Environment Configuration
```php
// .env
SERVICE_CACHE_TTL=3600
SERVICE_LOG_LEVEL=info
```

### Configuration Files
```php
// config/services.php
return [
    'cache' => [
        'ttl' => env('SERVICE_CACHE_TTL', 3600),
        'driver' => 'redis'
    ],
    'logging' => [
        'level' => env('SERVICE_LOG_LEVEL', 'info'),
        'channels' => ['file', 'database']
    ]
];
```

### Runtime Configuration
```php
$service->setConfig([
    'cache_ttl' => 7200,
    'enable_logging' => true
]);
```

## Best Practices

### 1. Service Organization
- Group related services using tags
- Use meaningful service names
- Follow naming conventions (e.g., `module.service`)

### 2. Dependency Management
- Inject dependencies through constructor
- Use interfaces for loose coupling
- Avoid circular dependencies

### 3. Error Handling
- Use specific exception types
- Provide meaningful error messages
- Include context information

### 4. Performance
- Use singletons for expensive services
- Implement caching where appropriate
- Use deferred loading for optional services

### 5. Testing
- Mock dependencies in tests
- Use dependency injection for testability
- Test service registration and resolution

## Usage Examples

### Basic Service Registration
```php
// Register services
$container->singleton('logger', LoggerService::class);
$container->bind('mailer', MailerService::class);

// Use in other services
class UserService extends Service
{
    public function sendWelcomeEmail($user)
    {
        $mailer = $this->service('mailer');
        $mailer->send('welcome', $user->email, ['user' => $user]);
        
        $this->log('Welcome email sent to: ' . $user->email);
    }
}
```

### Advanced Service Configuration
```php
// Create complex service with factory
$userService = $factory->builder(UserService::class)
    ->withConfig([
        'cache_ttl' => 3600,
        'enable_notifications' => true
    ])
    ->withDependencies([
        'repository' => UserRepository::class,
        'cache' => CacheService::class,
        'notifications' => NotificationService::class
    ])
    ->withTags(['user', 'service', 'cached'])
    ->withMiddleware(function($service, $method, $args, $next) {
        // Log method calls
        Log::info("Calling {$method} on UserService");
        return $next($service, $method, $args);
    })
    ->singleton()
    ->build('user.service');
```

### Service Provider Example
```php
class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->singleton('db.connection', function($container) {
            return new DatabaseConnection(
                $this->config('database.default')
            );
        });
        
        $this->bind('db.query', QueryBuilder::class);
        $this->bind('db.schema', SchemaBuilder::class);
    }
    
    public function boot()
    {
        // Set up database event listeners
        $this->addEventListener('db.query.executed', function($event) {
            $this->log('Query executed: ' . $event->sql);
        });
    }
}
```

## Integration

The service layer integrates with other application components:

- **Controllers**: Inject services into controllers
- **Middleware**: Use services in middleware
- **Commands**: Access services in console commands
- **Jobs**: Use services in background jobs
- **Events**: Dispatch and listen to service events

## Extending the Service Layer

The service layer is designed to be extensible:

1. **Custom Containers**: Implement `ContainerInterface`
2. **Custom Providers**: Extend `ServiceProvider`
3. **Custom Services**: Extend `Service` base class
4. **Custom Factories**: Implement `ServiceFactoryInterface`
5. **Custom Registries**: Implement `ServiceRegistryInterface`

## Performance Considerations

- Services are resolved lazily by default
- Singletons are cached after first resolution
- Use deferred providers for optional services
- Monitor service resolution performance
- Implement caching for expensive operations

## Security

- Validate all service inputs
- Use proper authentication and authorization
- Sanitize data before processing
- Log security-related events
- Follow principle of least privilege

This service layer provides a solid foundation for building scalable, maintainable applications with proper separation of concerns and dependency management.