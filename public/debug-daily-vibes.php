<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// Set content type to HTML with UTF-8
header('Content-Type: text/html; charset=utf-8');

// Include the bootstrap file
require_once __DIR__ . '/../app/bootstrap.php';

// Start output buffering
ob_start();

try {
    echo "<h1>Daily Vibe Debug</h1>";
    
    // Check if controller file exists
    $controllerFile = __DIR__ . '/../app/controllers/DailyVibeController.php';
    if (!file_exists($controllerFile)) {
        throw new Exception("Controller file not found: " . $controllerFile);
    }
    
    // Include the controller
    require_once $controllerFile;
    
    // Check if class exists
    if (!class_exists('DailyVibeController')) {
        throw new Exception("Class DailyVibeController not found in file: " . $controllerFile);
    }
    
    // Create a new instance
    $controller = new DailyVibeController();
    
    // Test database connection
    echo "<h2>Testing Database Connection</h2>";
    $db = new SQLite3(__DIR__ . '/../database/database.sqlite');
    if (!$db) {
        throw new Exception("Failed to connect to database: " . $db->lastErrorMsg());
    }
    echo "<p>✅ Successfully connected to database</p>";
    
    // Check if table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='daily_vibes'");
    if (!$result->fetchArray()) {
        throw new Exception("Table 'daily_vibes' does not exist in the database");
    }
    echo "<p>✅ Daily vibes table exists</p>";
    
    // Test query
    $result = $db->query("SELECT COUNT(*) as count FROM daily_vibes");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    echo "<p>✅ Found " . $row['count'] . " records in daily_vibes table</p>";
    
    // Test controller method
    echo "<h2>Testing Controller Method</h2>";
    if (!method_exists($controller, 'index')) {
        throw new Exception("Method 'index' not found in DailyVibeController");
    }
    
    // Call the index method
    echo "<p>Calling index method...</p>";
    ob_flush();
    $controller->index();
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid #f00; margin: 10px 0;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h4>File:</h4>";
    echo "<p>" . htmlspecialchars($e->getFile()) . " on line " . $e->getLine() . "</p>";
    echo "<h4>Trace:</h4>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

// Get any output from the controller
$output = ob_get_clean();

// Display the output
echo $output;

// Self-delete this file
@unlink(__FILE__);
?>
