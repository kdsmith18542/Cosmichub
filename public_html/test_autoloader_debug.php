<?php

// Enhanced autoloader debugging test
echo "<h1>Enhanced Autoloader Debug Test</h1>";
echo "<pre>";

// Test 1: Bootstrap loading
echo "=== Test 1: Bootstrap Loading ===\n";
try {
    require_once '../bootstrap.php';
    echo "✅ Bootstrap loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Bootstrap failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Check constants
echo "\n=== Test 2: Constants Check ===\n";
echo "APP_DIR: " . (defined('APP_DIR') ? APP_DIR : 'NOT DEFINED') . "\n";
echo "CONFIG_DIR: " . (defined('CONFIG_DIR') ? CONFIG_DIR : 'NOT DEFINED') . "\n";
echo "VIEWS_DIR: " . (defined('VIEWS_DIR') ? VIEWS_DIR : 'NOT DEFINED') . "\n";

// Test 3: Check file structure
echo "\n=== Test 3: File Structure Check ===\n";
$appDir = defined('APP_DIR') ? APP_DIR : '../app';
echo "App directory: $appDir\n";
echo "App directory exists: " . (is_dir($appDir) ? 'YES' : 'NO') . "\n";

$librariesDir = $appDir . '/libraries';
echo "Libraries directory: $librariesDir\n";
echo "Libraries directory exists: " . (is_dir($librariesDir) ? 'YES' : 'NO') . "\n";

$configFile = $librariesDir . '/Config.php';
echo "Config.php file: $configFile\n";
echo "Config.php exists: " . (file_exists($configFile) ? 'YES' : 'NO') . "\n";
echo "Config.php readable: " . (is_readable($configFile) ? 'YES' : 'NO') . "\n";

// Test 4: Check autoloader registration
echo "\n=== Test 4: Autoloader Registration ===\n";
$autoloaders = spl_autoload_functions();
echo "Number of registered autoloaders: " . count($autoloaders) . "\n";
foreach ($autoloaders as $i => $loader) {
    if (is_array($loader)) {
        echo "Autoloader $i: [" . get_class($loader[0]) . ", " . $loader[1] . "]\n";
    } elseif (is_object($loader)) {
        echo "Autoloader $i: " . get_class($loader) . "\n";
    } else {
        echo "Autoloader $i: " . gettype($loader) . "\n";
    }
}

// Test 5: Manual autoloader test
echo "\n=== Test 5: Manual Autoloader Test ===\n";
$testClass = 'App\\Libraries\\Config';
echo "Testing class: $testClass\n";

// Simulate the autoloader logic
$prefix = 'App\\';
$baseDir = $appDir . '/';
$len = strlen($prefix);

if (strncmp($prefix, $testClass, $len) === 0) {
    $relativeClass = substr($testClass, $len);
    echo "Relative class: $relativeClass\n";
    
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    echo "Expected file path: $file\n";
    echo "File exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n";
    echo "File readable: " . (is_readable($file) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($file)) {
        echo "File contents preview:\n";
        $content = file_get_contents($file);
        echo substr($content, 0, 200) . "...\n";
    }
} else {
    echo "Class does not match App namespace prefix\n";
}

// Test 6: Try to load the class
echo "\n=== Test 6: Class Loading Test ===\n";
try {
    echo "Attempting to check if class exists...\n";
    if (class_exists('App\\Libraries\\Config', true)) {
        echo "✅ App\\Libraries\\Config class loaded successfully\n";
        
        // Test instantiation
        $config = new App\Libraries\Config();
        echo "✅ Config class instantiated successfully\n";
        
        // Test method call
        if (method_exists($config, 'load')) {
            echo "✅ Config::load method exists\n";
        } else {
            echo "❌ Config::load method does not exist\n";
        }
    } else {
        echo "❌ App\\Libraries\\Config class could not be loaded\n";
    }
} catch (Exception $e) {
    echo "❌ Exception during class loading: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ Error during class loading: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 7: Check other App classes
echo "\n=== Test 7: Other App Classes Test ===\n";
$testClasses = [
    'App\\Libraries\\Database',
    'App\\Libraries\\Router',
    'App\\Controllers\\HomeController'
];

foreach ($testClasses as $className) {
    echo "Testing $className: ";
    try {
        if (class_exists($className, true)) {
            echo "✅ LOADED\n";
        } else {
            echo "❌ NOT LOADED\n";
        }
    } catch (Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    }
}

// Test 8: Include path and working directory
echo "\n=== Test 8: Environment Info ===\n";
echo "Current working directory: " . getcwd() . "\n";
echo "Include path: " . get_include_path() . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "__FILE__: " . __FILE__ . "\n";

echo "\n=== Test Complete ===\n";
echo "</pre>";
?>