<?php
// Autoloader test
echo "<h2>Autoloader Test</h2>";
echo "<p>Testing autoloader functionality...</p>";

try {
    // Include bootstrap to test autoloader
    require_once __DIR__ . '/../bootstrap.php';
    
    echo "<p>✅ Bootstrap loaded successfully</p>";
    
    // Test if vendor autoloader is working
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        echo "<p>✅ Composer vendor/autoload.php exists</p>";
    } else {
        echo "<p>❌ Composer vendor/autoload.php NOT found</p>";
    }
    
    // Test if app directory exists
    if (is_dir(__DIR__ . '/../app')) {
        echo "<p>✅ App directory exists</p>";
    } else {
        echo "<p>❌ App directory NOT found</p>";
    }
    
    // Test if Config.php file exists
    $configFile = __DIR__ . '/../app/libraries/Config.php';
    if (file_exists($configFile)) {
        echo "<p>✅ Config.php file exists at: $configFile</p>";
    } else {
        echo "<p>❌ Config.php file NOT found at: $configFile</p>";
    }
    
    // Test manual include of Config class
    echo "<p>Testing manual include of Config class...</p>";
    require_once __DIR__ . '/../app/libraries/Config.php';
    echo "<p>✅ Config.php manually included successfully</p>";
    
    // Test if class exists after manual include
    if (class_exists('App\\Libraries\\Config')) {
        echo "<p>✅ App\\Libraries\\Config class exists after manual include</p>";
        
        // Try to use the class
        $config = \App\Libraries\Config::load(__DIR__ . '/../app/config/config.php');
        echo "<p>✅ Config class works! Loaded config successfully</p>";
        echo "<p>App name: " . ($config['app']['name'] ?? 'Not set') . "</p>";
        
    } else {
        echo "<p>❌ App\\Libraries\\Config class NOT found even after manual include</p>";
    }
    
    // Test autoloader registration
    echo "<p>Testing autoloader without manual include...</p>";
    
    // Clear any previously loaded classes (this won't work in PHP, but let's test)
    if (class_exists('App\\Libraries\\Database', false)) {
        echo "<p>Database class already loaded</p>";
    } else {
        echo "<p>Database class not yet loaded, testing autoloader...</p>";
        
        // Try to use Database class to test autoloader
        try {
            $database = \App\Libraries\Database::getInstance();
            echo "<p>✅ Database class loaded via autoloader</p>";
        } catch (Exception $e) {
            echo "<p>❌ Database class failed to load: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Current working directory: " . getcwd() . "</p>";
echo "<p>Script directory: " . __DIR__ . "</p>";
?>