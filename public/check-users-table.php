<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to HTML with UTF-8
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Users Table Structure</h1>";

// Include the bootstrap file
require_once __DIR__ . '/../bootstrap.php';

// Get database configuration
$config = require __DIR__ . '/../app/config/database.php';

try {
    // Connect to the database
    $pdo = new PDO("sqlite:" . $config['database']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get users table info
    echo "<h2>Users Table Structure</h2>";
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "<p>No columns found in users table.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Name</th><th>Type</th><th>Not Null</th><th>Default</th><th>Primary Key</th></tr>";
        foreach ($columns as $column) {
            echo sprintf(
                "<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>",
                htmlspecialchars($column['name']),
                htmlspecialchars($column['type']),
                $column['notnull'] ? 'Yes' : 'No',
                $column['dflt_value'] ?? 'NULL',
                $column['pk'] ? 'Yes' : 'No'
            );
        }
        echo "</table>";
    }
    
    // Show sample user data
    echo "<h2>Sample User Data</h2>";
    $stmt = $pdo->query("SELECT * FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p>No users found in the database.</p>";
    } else {
        echo "<pre>" . print_r($users, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red; margin: 10px 0; padding: 10px; background: #ffebee;'>"
       . "<h2>Error Details:</h2>"
       . "<p>" . htmlspecialchars($e->getMessage()) . "</p>"
       . "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>"
       . "</div>";
}
