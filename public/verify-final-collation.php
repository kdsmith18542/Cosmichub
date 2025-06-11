<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to HTML with UTF-8
header('Content-Type: text/html; charset=utf-8');

function print_status($message, $status = 'info') {
    $icons = [
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    
    $icon = $icons[$status] ?? '';
    echo "<div class='status {$status}'>";
    echo "<span class='icon'>{$icon}</span>";
    echo "<span class='message'>{$message}</span>";
    echo "</div>\n";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Database Collation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .status { 
            padding: 10px; 
            margin: 5px 0; 
            border-left: 4px solid #ccc;
            display: flex;
            align-items: center;
        }
        .status .icon { margin-right: 10px; font-size: 1.2em; }
        .success { border-color: #4CAF50; background-color: #e8f5e9; }
        .error { border-color: #f44336; background-color: #ffebee; }
        .warning { border-color: #ff9800; background-color: #fff3e0; }
        .info { border-color: #2196F3; background-color: #e3f2fd; }
        .table-info { 
            margin: 20px 0; 
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { 
            padding: 10px; 
            text-align: left; 
            border-bottom: 1px solid #ddd;
        }
        th { background-color: #f5f5f5; font-weight: bold; }
        .success-text { color: #4CAF50; }
        .error-text { color: #f44336; }
        pre { 
            background: #f5f5f5; 
            padding: 10px; 
            border-radius: 4px; 
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <h1>Database Collation Verification</h1>
    
    <?php
    try {
        // Database file path
        $dbFile = __DIR__ . '/../database/database.sqlite';
        
        // Connect to SQLite
        $pdo = new PDO("sqlite:$dbFile");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get all tables
        $tables = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
                     ->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($tables)) {
            print_status('No tables found in the database', 'error');
            exit;
        }
        
        $allGood = true;
        
        foreach ($tables as $table) {
            $tableName = $table['name'];
            echo "<h2>Table: {$tableName}</h2>";
            
            // Show CREATE TABLE statement
            echo "<details>";
            echo "<summary>View CREATE TABLE statement</summary>";
            echo "<pre>" . htmlspecialchars($table['sql']) . "</pre>";
            echo "</details>";
            
            // Check columns
            $columns = $pdo->query("PRAGMA table_info('$tableName')")
                         ->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div class='table-info'>";
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Collation</th><th>Status</th></tr>";
            
            foreach ($columns as $col) {
                $isTextColumn = (stripos($col['type'], 'TEXT') !== false || 
                               stripos($col['type'], 'VARCHAR') !== false);
                $hasCollation = strpos($table['sql'], $col['name'] . ' ' . $col['type'] . ' COLLATE NOCASE') !== false;
                
                if ($isTextColumn) {
                    if ($hasCollation) {
                        $status = "<span class='success-text'>✓ NOCASE</span>";
                    } else {
                        $status = "<span class='error-text'>❌ Missing</span>";
                        $allGood = false;
                    }
                } else {
                    $status = "<span class='info'>N/A</span>";
                }
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($col['name']) . "</td>";
                echo "<td>" . htmlspecialchars($col['type']) . "</td>";
                echo "<td>" . ($isTextColumn ? 'NOCASE' : '') . "</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            echo "</div>";
        }
        
        // Show overall status
        echo "<div style='margin: 20px 0; padding: 15px; ' class='" . ($allGood ? 'success' : 'error') . "'>";
        if ($allGood) {
            print_status('✅ All text columns have been updated with COLLATE NOCASE!', 'success');
        } else {
            print_status('❌ Some columns are missing collation. Please check the tables above.', 'error');
        }
        echo "</div>";
        
    } catch (Exception $e) {
        print_status('Error: ' . $e->getMessage(), 'error');
    }
    ?>
    
    <div style='margin-top: 30px; padding: 15px; background-color: #e3f2fd; border-left: 4px solid #2196F3;'>
        <h3>Next Steps</h3>
        <ol>
            <li>Test your application to ensure everything works correctly</li>
            <li>If everything works, you can delete the backup file: 
                <code><?php echo htmlspecialchars(dirname($dbFile) . '/database.sqlite.backup.20250611062237'); ?></code>
            </li>
            <li>Delete this verification script: <code>rm public/verify-final-collation.php</code></li>
        </ol>
    </div>
</body>
</html>
