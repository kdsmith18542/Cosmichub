<?php
/**
 * Debug script to identify the cause of 'Service Temporarily Unavailable' error
 * This file helps diagnose issues on the remote EC2 server
 */

// Enable error reporting for debugging
ini_set('display_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

echo "<h1>CosmicHub Debug Information</h1>";
echo "<h2>Basic PHP Information</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Current Working Directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Script Directory:</strong> " . __DIR__ . "</p>";

echo "<h2>File System Check</h2>";
$appRoot = dirname(__DIR__);
echo "<p><strong>App Root:</strong> $appRoot</p>";
echo "<p><strong>Bootstrap exists:</strong> " . (file_exists($appRoot . '/bootstrap.php') ? 'YES' : 'NO') . "</p>";

if (file_exists($appRoot . '/bootstrap.php')) {
    echo "<h2>Bootstrap Test</h2>";
    try {
        require_once $appRoot . '/bootstrap.php';
        echo "<p style='color: green;'><strong>Bootstrap loaded successfully!</strong></p>";
        
        echo "<h3>Constants Defined:</h3>";
        $constants = ['ROOT_DIR', 'APP_DIR', 'CONFIG_DIR', 'VIEWS_DIR', 'STORAGE_DIR', 'LOGS_DIR'];
        foreach ($constants as $const) {
            if (defined($const)) {
                echo "<p><strong>$const:</strong> " . constant($const) . "</p>";
            } else {
                echo "<p style='color: red;'><strong>$const:</strong> NOT DEFINED</p>";
            }
        }
        
        echo "<h3>Database Connection Test</h3>";
        
        // Check PDO availability
        echo "<h3>PDO Check:</h3>";
        if (class_exists('PDO')) {
            echo "✓ PDO class is available<br>";
            $drivers = PDO::getAvailableDrivers();
            echo "Available PDO drivers: " . implode(', ', $drivers) . "<br>";
            
            // Check specifically for SQLite
            if (in_array('sqlite', $drivers)) {
                echo "✓ PDO SQLite driver is available<br>";
            } else {
                echo "✗ PDO SQLite driver is NOT available<br>";
                echo "<h3>🔧 Solution for Ubuntu 24 on EC2:</h3>";
                echo "<pre>";
                echo "sudo apt update\n";
                echo "sudo apt install php-pdo php-sqlite3\n";
                echo "sudo systemctl restart apache2  # For Apache\n";
                echo "# OR\n";
                echo "sudo systemctl restart nginx    # For Nginx\n";
                echo "\n# Verify installation:\n";
                echo "php -m | grep -i pdo\n";
                echo "php -m | grep -i sqlite\n";
                echo "</pre>";
                
                echo "<h3>🔧 Alternative for older Ubuntu versions:</h3>";
                echo "<pre>";
                echo "sudo apt-get update\n";
                echo "sudo apt-get install php-pdo php-sqlite3\n";
                echo "sudo systemctl restart apache2\n";
                echo "</pre>";
            }
        } else {
            echo "✗ PDO class is NOT available<br>";
            echo "<strong>SOLUTION FOR EC2:</strong><br>";
            echo "Run: sudo yum install php-pdo (Amazon Linux)<br>";
            echo "Or: sudo apt-get install php-pdo php-sqlite3 (Ubuntu)<br>";
            echo "Then restart web server: sudo systemctl restart httpd/nginx<br>";
        }
        
        if (isset($pdo) && $pdo instanceof PDO) {
            echo "<p style='color: green;'><strong>Database:</strong> Connected successfully</p>";
        } else {
            echo "<p style='color: red;'><strong>Database:</strong> Not connected</p>";
        }
        
        echo "<h3>Router Test</h3>";
        if (file_exists(APP_DIR . '/libraries/PHPRouter.php')) {
            echo "<p><strong>PHPRouter file:</strong> EXISTS</p>";
            try {
                require_once APP_DIR . '/libraries/PHPRouter.php';
                $router = new \App\Libraries\PHPRouter();
                echo "<p style='color: green;'><strong>PHPRouter:</strong> Instantiated successfully</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'><strong>PHPRouter Error:</strong> " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'><strong>PHPRouter file:</strong> NOT FOUND</p>";
        }
        
        echo "<h3>Routes File Test</h3>";
        if (file_exists(APP_DIR . '/routes/web.php')) {
            echo "<p><strong>Routes file:</strong> EXISTS</p>";
            try {
                require_once APP_DIR . '/routes/web.php';
                echo "<p style='color: green;'><strong>Routes:</strong> Loaded successfully</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'><strong>Routes Error:</strong> " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'><strong>Routes file:</strong> NOT FOUND</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>Bootstrap Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Error File:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Error Line:</strong> " . $e->getLine() . "</p>";
        echo "<h3>Stack Trace:</h3>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p style='color: red;'><strong>Bootstrap file not found!</strong></p>";
}

echo "<h2>Server Environment</h2>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "</p>";

echo "<h2>Memory and Limits</h2>";
echo "<p><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . "</p>";
echo "<p><strong>Current Memory Usage:</strong> " . round(memory_get_usage() / 1024 / 1024, 2) . " MB</p>";
echo "<p><strong>Peak Memory Usage:</strong> " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB</p>";

echo "<h2>Error Log Information</h2>";
echo "<p><strong>Error Log:</strong> " . ini_get('error_log') . "</p>";
echo "<p><strong>Log Errors:</strong> " . ini_get('log_errors') . "</p>";
echo "<p><strong>Display Errors:</strong> " . ini_get('display_errors') . "</p>";

echo "<p><em>Debug completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>