<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to HTML with UTF-8
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Verify Database Collation</h1>";

// Database file path
$dbFile = __DIR__ . '/../database/database.sqlite';

// Connect to SQLite
try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
                 ->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Table Collation Status</h2>";
    
    foreach ($tables as $table) {
        echo "<h3>Table: " . htmlspecialchars($table) . "</h3>";
        
        // Get CREATE TABLE statement
        $createTable = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
        
        // Check if collation is set
        if (stripos($createTable, 'COLLATE') !== false) {
            echo "<p style='color:green'>✅ Collation is properly set</p>";
        } else {
            echo "<p style='color:orange'>⚠️ No collation found (this might be fine for non-text columns)</p>";
        }
        
        // Show columns and their types
        echo "<h4>Columns:</h4><ul>";
        $columns = $pdo->query("PRAGMA table_info('$table')")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "<li>" . htmlspecialchars($col['name']) . " (" . htmlspecialchars($col['type']) . ")";
            if (stripos($col['type'], 'TEXT') !== false || stripos($col['type'], 'VARCHAR') !== false) {
                if (stripos($createTable, $col['name'] . ' ' . $col['type'] . ' COLLATE') !== false) {
                    echo " <span style='color:green'>✓ COLLATE NOCASE</span>";
                } else {
                    echo " <span style='color:red'>⚠️ No collation set</span>";
                }
            }
            echo "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red; padding:10px; margin:10px 0; border:1px solid #f00;'>";
    echo "<h2>Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Show next steps
echo "<div style='margin-top: 20px; padding: 10px; background: #e8f5e9; border: 1px solid #c8e6c9;'>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Test your application to ensure everything works correctly</li>";
echo "<li>If everything works, you can delete the backup file: <code>" . htmlspecialchars(dirname($dbFile) . '/database.sqlite.backup.20250611061154') . "</code></li>";
echo "<li>Delete this script when done: <code>rm public/verify-collation.php</code></li>";
echo "</ol>";
echo "</div>";
