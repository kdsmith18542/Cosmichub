<?php
/**
 * Environment Loading Diagnostic Test
 * This file tests the .env loading process specifically
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Environment Loading Diagnostic Test</h1>";
echo "<pre>";

echo "=== Basic PHP Info ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current working directory: " . getcwd() . "\n";
echo "Script directory (__DIR__): " . __DIR__ . "\n";
echo "Script file (__FILE__): " . __FILE__ . "\n\n";

echo "=== Path Analysis ===\n";
$rootDir = __DIR__;
echo "Root directory: $rootDir\n";

$envPath = $rootDir . '/.env';
echo "Expected .env path: $envPath\n";
echo ".env file exists: " . (file_exists($envPath) ? 'YES' : 'NO') . "\n";
echo ".env file readable: " . (is_readable($envPath) ? 'YES' : 'NO') . "\n";

if (file_exists($envPath)) {
    echo ".env file size: " . filesize($envPath) . " bytes\n";
    echo ".env file permissions: " . substr(sprintf('%o', fileperms($envPath)), -4) . "\n";
}

echo "\n=== Directory Listing ===\n";
$files = scandir($rootDir);
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $fullPath = $rootDir . '/' . $file;
    $type = is_dir($fullPath) ? 'DIR' : 'FILE';
    echo "$type: $file\n";
}

echo "\n=== Helpers.php Loading Test ===\n";
$helpersPath = $rootDir . '/app/helpers.php';
echo "Helpers path: $helpersPath\n";
echo "Helpers exists: " . (file_exists($helpersPath) ? 'YES' : 'NO') . "\n";

if (file_exists($helpersPath)) {
    try {
        require_once $helpersPath;
        echo "Helpers loaded successfully\n";
        
        if (function_exists('loadEnv')) {
            echo "loadEnv function available: YES\n";
        } else {
            echo "loadEnv function available: NO\n";
        }
        
        if (function_exists('env')) {
            echo "env function available: YES\n";
        } else {
            echo "env function available: NO\n";
        }
    } catch (Exception $e) {
        echo "Error loading helpers: " . $e->getMessage() . "\n";
    }
} else {
    echo "Helpers file not found!\n";
}

echo "\n=== .env Loading Test ===\n";
if (file_exists($envPath) && function_exists('loadEnv')) {
    try {
        $result = loadEnv($envPath);
        echo "loadEnv result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
        
        // Test some environment variables
        $testVars = ['DB_CONNECTION', 'APP_KEY', 'GEMINI_API_KEY', 'STRIPE_PUBLIC_KEY'];
        echo "\n=== Environment Variables Test ===\n";
        foreach ($testVars as $var) {
            $value = function_exists('env') ? env($var) : getenv($var);
            if ($value !== false && $value !== null) {
                // Mask sensitive values
                if (strpos($var, 'KEY') !== false || strpos($var, 'SECRET') !== false) {
                    $displayValue = substr($value, 0, 10) . '...[MASKED]';
                } else {
                    $displayValue = $value;
                }
                echo "$var: $displayValue\n";
            } else {
                echo "$var: NOT SET\n";
            }
        }
        
        // Show first few lines of .env file
        echo "\n=== .env File Content (first 10 lines) ===\n";
        $envContent = file($envPath, FILE_IGNORE_NEW_LINES);
        for ($i = 0; $i < min(10, count($envContent)); $i++) {
            $line = $envContent[$i];
            // Mask sensitive lines
            if (strpos($line, 'KEY') !== false || strpos($line, 'SECRET') !== false || strpos($line, 'PASSWORD') !== false) {
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    echo $parts[0] . '=[MASKED]' . "\n";
                } else {
                    echo $line . "\n";
                }
            } else {
                echo $line . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "Error loading .env: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    if (!file_exists($envPath)) {
        echo "Cannot test .env loading: .env file not found\n";
    }
    if (!function_exists('loadEnv')) {
        echo "Cannot test .env loading: loadEnv function not available\n";
    }
}

echo "\n=== Config Loading Test ===\n";
$configPath = $rootDir . '/app/config/config.php';
echo "Config path: $configPath\n";
echo "Config exists: " . (file_exists($configPath) ? 'YES' : 'NO') . "\n";

if (file_exists($configPath)) {
    try {
        $config = require $configPath;
        echo "Config loaded successfully\n";
        echo "Config is array: " . (is_array($config) ? 'YES' : 'NO') . "\n";
        
        if (is_array($config)) {
            echo "Config sections: " . implode(', ', array_keys($config)) . "\n";
            
            if (isset($config['app'])) {
                echo "App config loaded: YES\n";
                echo "App name: " . ($config['app']['name'] ?? 'NOT SET') . "\n";
                echo "App env: " . ($config['app']['env'] ?? 'NOT SET') . "\n";
                echo "App debug: " . ($config['app']['debug'] ? 'TRUE' : 'FALSE') . "\n";
            }
        }
    } catch (Exception $e) {
        echo "Error loading config: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "If you see this message, PHP is working and the diagnostic completed.\n";
echo "</pre>";
?>