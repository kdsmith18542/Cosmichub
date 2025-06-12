<?php
// Database connectivity test
echo "<h2>Database Connectivity Test</h2>";
echo "<p>Testing database connection...</p>";

try {
    // Include bootstrap to load environment and database configuration
    require_once __DIR__ . '/../bootstrap.php';
    
    echo "<p>✅ Bootstrap loaded successfully</p>";
    
    // Get database configuration
    $config = \App\Libraries\Config::getInstance();
    $dbConfig = $config->get('database');
    
    echo "<p>Database configuration loaded:</p>";
    echo "<ul>";
    echo "<li>Host: " . ($dbConfig['host'] ?? 'Not set') . "</li>";
    echo "<li>Database: " . ($dbConfig['database'] ?? 'Not set') . "</li>";
    echo "<li>Username: " . ($dbConfig['username'] ?? 'Not set') . "</li>";
    echo "<li>Password: " . (isset($dbConfig['password']) ? '[SET]' : '[NOT SET]') . "</li>";
    echo "</ul>";
    
    // Test database connection
    $database = \App\Libraries\Database::getInstance();
    $pdo = $database->getConnection();
    
    if ($pdo) {
        echo "<p>✅ Database connection successful!</p>";
        
        // Test a simple query
        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();
        
        if ($result && $result['test'] == 1) {
            echo "<p>✅ Database query test successful!</p>";
        } else {
            echo "<p>❌ Database query test failed</p>";
        }
        
        // Show database version
        $stmt = $pdo->query('SELECT VERSION() as version');
        $version = $stmt->fetch();
        if ($version) {
            echo "<p>Database version: " . $version['version'] . "</p>";
        }
        
    } else {
        echo "<p>❌ Database connection failed - PDO object is null</p>";
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
?>