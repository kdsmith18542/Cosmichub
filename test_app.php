<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Register a shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        echo "Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}\n";
    }
});

try {
    // Load bootstrap
    require 'bootstrap.php';
    echo "Bootstrap loaded successfully\n";
    
    // Try to load a controller
    require_once 'app/controllers/HomeController.php';
    echo "HomeController loaded successfully\n";
    
    // Try to load a model
    require_once 'app/models/User.php';
    echo "User model loaded successfully\n";
    
    // Try to load a view
    require_once 'app/libraries/View.php';
    echo "View library loaded successfully\n";
    
    // Try to load helpers
    require_once 'app/helpers.php';
    echo "Helpers loaded successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "PHP Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

echo "Script completed\n";