<?php
// Test script to debug bootstrap loading
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Bootstrap Debug Test</h1>";
echo "<p>Starting bootstrap test...</p>";

try {
    // Define paths like index.php does
    $documentRoot = __DIR__;
    $appRoot = dirname($documentRoot);
    
    echo "<p>Document Root: " . htmlspecialchars($documentRoot) . "</p>";
    echo "<p>App Root: " . htmlspecialchars($appRoot) . "</p>";
    
    // Check if bootstrap.php exists
    $bootstrapPath = $appRoot . '/bootstrap.php';
    echo "<p>Bootstrap Path: " . htmlspecialchars($bootstrapPath) . "</p>";
    
    if (!file_exists($bootstrapPath)) {
        throw new Exception("Bootstrap file not found at: " . $bootstrapPath);
    }
    
    echo "<p>Bootstrap file exists. Attempting to include...</p>";
    
    // Include bootstrap
    require_once $bootstrapPath;
    
    echo "<p style='color: green;'>✓ Bootstrap loaded successfully!</p>";
    
    // Test basic constants
    $constants = ['ROOT_DIR', 'APP_DIR', 'CONFIG_DIR', 'VIEWS_DIR', 'STORAGE_DIR', 'LOGS_DIR'];
    echo "<h2>Defined Constants:</h2>";
    foreach ($constants as $const) {
        if (defined($const)) {
            echo "<p>✓ {$const}: " . htmlspecialchars(constant($const)) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ {$const}: NOT DEFINED</p>";
        }
    }
    
    // Test database connection
    echo "<h2>Database Test:</h2>";
    if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
        echo "<p style='color: green;'>✓ Database connection established</p>";
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
    
    // Test router creation
    echo "<h2>Router Test:</h2>";
    if (class_exists('App\\Libraries\\PHPRouter')) {
        $router = new App\Libraries\PHPRouter();
        echo "<p style='color: green;'>✓ Router class loaded successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Router class not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Error $e) {
    echo "<p style='color: red; font-weight: bold;'>FATAL ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<p>Test completed.</p>";
?>