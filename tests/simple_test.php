<?php

/**
 * Simple test runner for the enhanced middleware system
 * This file tests the basic functionality without external dependencies
 */

echo "Middleware System Test Runner\n";
echo str_repeat('=', 50) . "\n";

// Test 1: Check if middleware files exist
echo "Test 1: Checking middleware files... ";
$middlewareFiles = [
    'app/Middlewares/AuthMiddleware.php',
    'app/Middlewares/ApiAuthMiddleware.php',
    'app/Middlewares/SecurityMiddleware.php',
    'app/Middlewares/RolePermissionMiddleware.php',
    'app/Core/Middleware/MiddlewareManager.php',
    'app/Core/Middleware/MiddlewarePipeline.php',
    'app/Core/Middleware/MiddlewareResolver.php',
    'app/Core/Middleware/MiddlewareServiceProvider.php',
];

$allExist = true;
foreach ($middlewareFiles as $file) {
    if (!file_exists($file)) {
        echo "FAILED - Missing file: $file\n";
        $allExist = false;
    }
}

if ($allExist) {
    echo "PASSED\n";
} else {
    echo "Some files are missing\n";
}

// Test 2: Check configuration file
echo "Test 2: Checking configuration file... ";
if (file_exists('config/middleware.php')) {
    $config = include 'config/middleware.php';
    if (is_array($config) && isset($config['aliases']) && isset($config['groups'])) {
        echo "PASSED\n";
    } else {
        echo "FAILED - Invalid configuration structure\n";
    }
} else {
    echo "FAILED - Configuration file missing\n";
}

// Test 3: Check documentation files
echo "Test 3: Checking documentation... ";
if (file_exists('docs/MIDDLEWARE_SYSTEM.md') && file_exists('README_MIDDLEWARE.md')) {
    echo "PASSED\n";
} else {
    echo "FAILED - Documentation files missing\n";
}

// Test 4: Basic syntax check
echo "Test 4: Basic syntax validation... ";
$syntaxErrors = [];
foreach ($middlewareFiles as $file) {
    if (file_exists($file)) {
        $output = [];
        $returnCode = 0;
        exec("php -l \"$file\" 2>&1", $output, $returnCode);
        if ($returnCode !== 0) {
            $syntaxErrors[] = $file;
        }
    }
}

if (empty($syntaxErrors)) {
    echo "PASSED\n";
} else {
    echo "FAILED - Syntax errors in: " . implode(', ', $syntaxErrors) . "\n";
}

// Test 5: Check middleware aliases in configuration
echo "Test 5: Checking middleware aliases... ";
if (file_exists('config/middleware.php')) {
    $config = include 'config/middleware.php';
    $expectedAliases = ['auth', 'api.auth', 'security', 'role', 'throttle'];
    $missingAliases = [];
    
    foreach ($expectedAliases as $alias) {
        if (!isset($config['aliases'][$alias])) {
            $missingAliases[] = $alias;
        }
    }
    
    if (empty($missingAliases)) {
        echo "PASSED\n";
    } else {
        echo "FAILED - Missing aliases: " . implode(', ', $missingAliases) . "\n";
    }
} else {
    echo "FAILED - Configuration file not found\n";
}

// Test 6: Check middleware groups
echo "Test 6: Checking middleware groups... ";
if (file_exists('config/middleware.php')) {
    $config = include 'config/middleware.php';
    $expectedGroups = ['web', 'api', 'admin', 'secure'];
    $missingGroups = [];
    
    foreach ($expectedGroups as $group) {
        if (!isset($config['groups'][$group])) {
            $missingGroups[] = $group;
        }
    }
    
    if (empty($missingGroups)) {
        echo "PASSED\n";
    } else {
        echo "FAILED - Missing groups: " . implode(', ', $missingGroups) . "\n";
    }
} else {
    echo "FAILED - Configuration file not found\n";
}

echo str_repeat('=', 50) . "\n";
echo "Basic middleware system validation completed.\n";
echo "For full functionality testing, ensure all dependencies are installed.\n";