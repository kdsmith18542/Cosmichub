<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to HTML with UTF-8
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Verify Text Column Collation</h1>";

// Database file path
$dbFile = __DIR__ . '/../database/database.sqlite';

try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
                 ->fetchAll(PDO::FETCH_COLUMN);
    
    $allGood = true;
    
    foreach ($tables as $table) {
        echo "<h3>Table: " . htmlspecialchars($table) . "</h3>";
        
        // Get the CREATE TABLE statement
        $createTable = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
        
        // Get text columns
        $columns = $pdo->query("
            SELECT name, type 
            FROM pragma_table_info('$table') 
            WHERE type LIKE '%TEXT%' OR type LIKE '%VARCHAR%'
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($columns)) {
            echo "<p>No text columns in this table.</p>";
            continue;
        }
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Collation</th><th>Status</th></tr>";
        
        foreach ($columns as $col) {
            $hasCollation = strpos($createTable, $col['name'] . ' ' . $col['type'] . ' COLLATE NOCASE') !== false;
            $status = $hasCollation ? '✅' : '❌';
            $statusText = $hasCollation ? 'NOCASE' : 'Missing';
            
            if (!$hasCollation) {
                $allGood = false;
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['name']) . "</td>";
            echo "<td>" . htmlspecialchars($col['type']) . "</td>";
            echo "<td>" . $statusText . "</td>";
            echo "<td>" . $status . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    if ($allGood) {
        echo "<div style='padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0;'>";
        echo "<h3>✅ All text columns have been updated with COLLATE NOCASE!</h3>";
        echo "</div>";
    } else {
        echo "<div style='padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0;'>";
        echo "<h3>❌ Some columns are missing collation. Please check the table above.</h3>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red; padding:10px; margin:10px 0; border:1px solid #f00;'>";
    echo "<h2>Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Show next steps
echo "<div style='margin-top: 20px; padding: 15px; background-color: #e2e3e5; border: 1px solid #d6d8db; border-radius: 4px;'>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Test your application to ensure everything works correctly</li>";
if ($allGood) {
    echo "<li>Delete the backup file: <code>" . htmlspecialchars(dirname($dbFile) . '/database.sqlite.backup.20250611061502') . "</code></li>";
    echo "<li>Delete these scripts: <code>rm public/verify-text-collation.php public/verify-collation.php public/fix-text-collation.php</code></li>";
}
echo "</ol>";
echo "</div>";
