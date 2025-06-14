# Enhanced Configuration Management System

This document describes the enhanced configuration management system that provides foundation for environment-specific deployments in the Cosmichub application.

## Overview

The enhanced configuration management system provides:

- **Environment-specific configurations** - Different settings for local, staging, testing, and production environments
- **Type-safe configuration access** - Proper type conversion and validation
- **Configuration caching** - Performance optimization through intelligent caching
- **Configuration validation** - Ensure required settings are present and valid
- **Environment variable loading** - Comprehensive .env file parsing with variable expansion
- **Dot notation access** - Easy access to nested configuration values

## Architecture

### Core Components

1. **ConfigurationManager** (`app/Core/Config/ConfigurationManager.php`)
   - Main configuration management class
   - Handles loading, caching, and accessing configuration
   - Supports environment-specific overrides

2. **Configuration Loaders**
   - **FileLoader** (`app/Core/Config/Loaders/FileLoader.php`) - Loads configuration from PHP files
   - **EnvironmentLoader** (`app/Core/Config/Loaders/EnvironmentLoader.php`) - Loads and parses .env files

3. **ConfigCache** (`app/Core/Config/Cache/ConfigCache.php`)
   - Handles configuration caching for performance
   - Automatically invalidates cache when configuration files change

4. **ConfigValidator** (`app/Core/Config/Validation/ConfigValidator.php`)
   - Validates configuration values against rules
   - Ensures required configuration keys are present

5. **ConfigServiceProvider** (`app/Core/Config/ConfigServiceProvider.php`)
   - Registers all configuration services in the container
   - Initializes the configuration system during application boot

## Directory Structure

```
config/
├── app.php                    # Main application configuration
├── database.php               # Database configuration
├── cache.php                  # Cache configuration
└── environments/              # Environment-specific overrides
    ├── local/
    │   ├── app.php           # Local development overrides
    │   ├── database.php      # Local database settings
    │   └── ...
    ├── staging/
    │   ├── app.php           # Staging environment overrides
    │   └── ...
    ├── testing/
    │   ├── app.php           # Testing environment overrides
    │   └── ...
    └── production/
        ├── app.php           # Production environment overrides
        └── ...
```

## Usage

### Basic Configuration Access

```php
// Get configuration value
$appName = config('app.name');
$debugMode = config('app.debug', false); // with default value

// Check if configuration exists
if (config()->has('app.custom_setting')) {
    $customSetting = config('app.custom_setting');
}

// Get all configuration for a group
$appConfig = config()->getGroup('app');
```

### Environment Variables

```php
// Access environment variables
$dbHost = env('DB_HOST', 'localhost');
$apiKey = env('API_KEY');

// Environment variables are automatically loaded from .env files
// and can be accessed through the env() helper function
```

### Environment-Specific Configuration

Configuration files in `config/environments/{environment}/` automatically override base configuration:

```php
// config/app.php (base configuration)
return [
    'debug' => false,
    'url' => env('APP_URL', 'https://cosmichub.com'),
];

// config/environments/local/app.php (local overrides)
return [
    'debug' => true,
    'url' => env('APP_URL', 'http://localhost:8000'),
    'hot_reload' => true, // local-specific setting
];
```

### Configuration Validation

```php
// Validate required configuration keys
config()->validateRequired([
    'app.name',
    'app.key',
    'database.default',
]);

// Validate with custom rules
config()->validateRules([
    'app.debug' => 'boolean',
    'app.url' => 'url',
    'database.connections.mysql.port' => 'integer|min:1|max:65535',
]);
```

### Configuration Caching

Configuration is automatically cached for performance:

```php
// Check if configuration is cached
if (config()->isCached()) {
    echo "Configuration loaded from cache";
}

// Clear configuration cache
config()->clearCache();

// Reload configuration (clears cache and reloads)
config()->reload();
```

## Environment Setup

### 1. Environment Variables

Create appropriate `.env` files for each environment:

```bash
# .env (production)
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cosmichub.com

# .env.local (local development)
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# .env.testing (testing)
APP_ENV=testing
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### 2. Environment-Specific Configuration

Create environment-specific configuration files in `config/environments/{environment}/`:

- `config/environments/local/` - Local development settings
- `config/environments/staging/` - Staging environment settings
- `config/environments/testing/` - Testing environment settings
- `config/environments/production/` - Production environment settings

### 3. Application Environment Detection

The application environment is determined by:

1. `APP_ENV` environment variable
2. Application instance method `getEnvironment()`
3. Defaults to `'production'`

## Performance Considerations

### Configuration Caching

- Configuration is automatically cached after first load
- Cache is invalidated when configuration files are modified
- Cache files are stored in `storage/cache/config/`
- Use `config()->clearCache()` to manually clear cache

### Environment Variable Caching

- Environment variables are cached in memory during request
- `.env` files are parsed once per request
- Use `env()->clearCache()` to clear environment variable cache

### Best Practices

1. **Use environment variables for sensitive data** (API keys, passwords)
2. **Use configuration files for application settings** (features, defaults)
3. **Keep environment-specific overrides minimal** (only override what's necessary)
4. **Validate critical configuration** (ensure required settings are present)
5. **Use caching in production** (enable configuration caching for better performance)

## Migration from Legacy System

If migrating from an existing configuration system:

1. **Update service provider registration** - Ensure `ConfigServiceProvider` is registered
2. **Move configuration files** - Organize files in the new structure
3. **Update configuration access** - Use new `config()` helper and methods
4. **Add environment-specific overrides** - Create environment-specific configuration files
5. **Add validation** - Implement configuration validation for critical settings

## Troubleshooting

### Common Issues

1. **Configuration not loading**
   - Check file permissions
   - Verify file paths
   - Check for PHP syntax errors in configuration files

2. **Environment variables not working**
   - Verify `.env` file exists and is readable
   - Check for syntax errors in `.env` file
   - Ensure environment variables are properly quoted

3. **Cache issues**
   - Clear configuration cache: `config()->clearCache()`
   - Check cache directory permissions
   - Verify cache directory exists

4. **Validation errors**
   - Check error logs for validation messages
   - Verify required configuration keys are present
   - Ensure configuration values match validation rules

### Debug Mode

Enable debug mode to get detailed information about configuration loading:

```php
// In .env file
APP_DEBUG=true

// Or in configuration
config()->set('app.debug', true);
```

## Security Considerations

1. **Never commit sensitive data** to version control
2. **Use environment variables** for secrets and API keys
3. **Restrict file permissions** on configuration files
4. **Validate user input** before using in configuration
5. **Use HTTPS** for production deployments
6. **Regularly rotate** API keys and secrets

## Testing

The configuration system includes comprehensive testing support:

```php
// In tests, use testing environment
config()->setEnvironment('testing');

// Override configuration for tests
config()->set('database.default', 'sqlite');
config()->set('database.connections.sqlite.database', ':memory:');

// Test configuration validation
$this->expectException(ConfigurationException::class);
config()->validateRequired(['missing.key']);
```

This enhanced configuration management system provides a solid foundation for environment-specific deployments while maintaining performance, security, and ease of use.