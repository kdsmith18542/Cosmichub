<?php
// Test bootstrap loading
echo "Testing bootstrap loading...\n";

try {
    // Check if bootstrap file exists
    if (!file_exists(__DIR__ . '/bootstrap.php')) {
        echo "ERROR: bootstrap.php not found\n";
        exit(1);
    }
    
    echo "Bootstrap file exists\n";
    
    // Try to include bootstrap
    require_once __DIR__ . '/bootstrap.php';
    
    echo "Bootstrap loaded successfully\n";
    echo "PHP version: " . PHP_VERSION . "\n";
    
} catch (Exception $e) {
    echo "ERROR loading bootstrap: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR loading bootstrap: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>