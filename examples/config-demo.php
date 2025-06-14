<?php

/**
 * Configuration Management System Demo
 * 
 * This script demonstrates the enhanced configuration management system
 * and how it handles environment-specific configurations.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers.php';

use App\Core\Config\ConfigurationManager;
use App\Core\Config\Loaders\FileLoader;
use App\Core\Config\Loaders\EnvironmentLoader;
use App\Core\Config\Cache\ConfigCache;
use App\Core\Config\Validation\ConfigValidator;

// Initialize the configuration system
$basePath = dirname(__DIR__);
$environment = $_ENV['APP_ENV'] ?? 'local';

$fileLoader = new FileLoader();
$environmentLoader = new EnvironmentLoader();
$configCache = new ConfigCache($basePath);
$configValidator = new ConfigValidator();

$config = new ConfigurationManager(
    $basePath,
    $environment,
    $fileLoader,
    $environmentLoader,
    $configCache,
    $configValidator
);

echo "=== Configuration Management System Demo ===\n\n";

// Load configuration
echo "Loading configuration for environment: {$environment}\n";
$config->load();

// Demonstrate basic configuration access
echo "\n--- Basic Configuration Access ---\n";
echo "App Name: " . $config->get('app.name', 'Unknown') . "\n";
echo "App Environment: " . $config->get('app.env', 'Unknown') . "\n";
echo "Debug Mode: " . ($config->get('app.debug', false) ? 'Enabled' : 'Disabled') . "\n";
echo "App URL: " . $config->get('app.url', 'Not set') . "\n";
echo "Timezone: " . $config->get('app.timezone', 'UTC') . "\n";
echo "Locale: " . $config->get('app.locale', 'en') . "\n";

// Demonstrate environment variables
echo "\n--- Environment Variables ---\n";
echo "Database Host: " . $config->env('DB_HOST', 'localhost') . "\n";
echo "Database Name: " . $config->env('DB_DATABASE', 'cosmichub') . "\n";
echo "Cache Driver: " . $config->env('CACHE_DRIVER', 'file') . "\n";
echo "Session Driver: " . $config->env('SESSION_DRIVER', 'file') . "\n";

// Demonstrate feature flags
echo "\n--- Feature Flags ---\n";
$features = $config->get('app.features', []);
foreach ($features as $feature => $enabled) {
    $status = $enabled ? 'Enabled' : 'Disabled';
    echo ucfirst(str_replace('_', ' ', $feature)) . ": {$status}\n";
}

// Demonstrate environment-specific settings
echo "\n--- Environment-Specific Settings ---\n";
if ($config->has('app.hot_reload')) {
    echo "Hot Reload: " . ($config->get('app.hot_reload') ? 'Enabled' : 'Disabled') . "\n";
}
if ($config->has('app.asset_versioning')) {
    echo "Asset Versioning: " . ($config->get('app.asset_versioning') ? 'Enabled' : 'Disabled') . "\n";
}
if ($config->has('app.query_log')) {
    echo "Query Logging: " . ($config->get('app.query_log') ? 'Enabled' : 'Disabled') . "\n";
}
if ($config->has('app.opcache')) {
    echo "OPcache: " . ($config->get('app.opcache') ? 'Enabled' : 'Disabled') . "\n";
}

// Demonstrate configuration groups
echo "\n--- Configuration Groups ---\n";
if ($config->hasGroup('app')) {
    $appConfig = $config->getGroup('app');
    echo "App configuration keys: " . implode(', ', array_keys($appConfig)) . "\n";
}

// Demonstrate cache status
echo "\n--- Cache Information ---\n";
echo "Configuration cached: " . ($config->isCached() ? 'Yes' : 'No') . "\n";
if ($config->isCached()) {
    echo "Cache file exists and is valid\n";
}

// Demonstrate validation
echo "\n--- Configuration Validation ---\n";
try {
    $requiredKeys = ['app.name', 'app.env', 'app.key'];
    $config->validateRequired($requiredKeys);
    echo "Required configuration validation: Passed\n";
} catch (Exception $e) {
    echo "Required configuration validation: Failed - " . $e->getMessage() . "\n";
}

try {
    $rules = [
        'app.debug' => 'boolean',
        'app.url' => 'url',
    ];
    $config->validateRules($rules);
    echo "Configuration rules validation: Passed\n";
} catch (Exception $e) {
    echo "Configuration rules validation: Failed - " . $e->getMessage() . "\n";
}

// Demonstrate utility methods
echo "\n--- Utility Methods ---\n";
echo "Is debug mode: " . ($config->isDebug() ? 'Yes' : 'No') . "\n";
echo "Current environment: " . $config->getEnvironment() . "\n";
echo "Is local environment: " . ($config->isEnvironment('local') ? 'Yes' : 'No') . "\n";
echo "Is production environment: " . ($config->isEnvironment('production') ? 'Yes' : 'No') . "\n";

// Demonstrate setting and getting values
echo "\n--- Dynamic Configuration ---\n";
$config->set('demo.test_value', 'Hello from configuration!');
echo "Set demo.test_value: " . $config->get('demo.test_value') . "\n";
echo "Has demo.test_value: " . ($config->has('demo.test_value') ? 'Yes' : 'No') . "\n";

// Show all configuration (truncated for demo)
echo "\n--- All Configuration (sample) ---\n";
$allConfig = $config->all();
echo "Total configuration groups: " . count($allConfig) . "\n";
echo "Available groups: " . implode(', ', array_keys($allConfig)) . "\n";

echo "\n=== Demo Complete ===\n";