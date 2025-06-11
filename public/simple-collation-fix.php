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
    
    flush();
    @ob_flush();
}

// Add some basic styling
echo "<style>
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
    pre { 
        background: #f5f5f5; 
        padding: 10px; 
        border-radius: 4px; 
        overflow-x: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
</style>";

echo "<h1>Simple Database Collation Fix</h1>";

// Database paths
$dbDir = __DIR__ . '/../database';
$dbFile = "$dbDir/database.sqlite";
$backupFile = "$dbFile.backup." . date('YmdHis');

// Step 1: Verify database exists
print_status("Verifying database file...");
if (!file_exists($dbFile)) {
    die("<div class='error'>Database file not found at: " . htmlspecialchars($dbFile) . "</div>");
}

// Step 2: Create backup
print_status("Creating backup...");
if (!copy($dbFile, $backupFile)) {
    die("<div class='error'>Failed to create backup. Please check file permissions.</div>");
}
print_status("Backup created at: " . htmlspecialchars($backupFile), 'success');

try {
    // Connect to database
    print_status("Connecting to database...");
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    print_status("Fetching table list...");
    $tables = $db->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
               ->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tables)) {
        throw new Exception("No tables found in the database");
    }
    
    print_status("Found " . count($tables) . " tables to process", 'success');
    
    // Process each table
    foreach ($tables as $table) {
        $tableName = $table['name'];
        print_status("Processing table: $tableName");
        
        // Begin transaction for this table
        $db->beginTransaction();
        
        try {
            // Get the original CREATE TABLE statement
            $createTable = $table['sql'];
            
            // Add COLLATE NOCASE to text columns
            $createTable = preg_replace(
                '/(TEXT|VARCHAR\s*\([^)]*\))([^,)]*)(,|$)/i',
                '$1 COLLATE NOCASE$2$3',
                $createTable
            );
            
            // Create temporary table with new schema
            $tempTable = $tableName . '_temp';
            $createTempTable = str_ireplace(
                "CREATE TABLE \"$tableName\"",
                "CREATE TABLE \"$tempTable\"",
                $createTable
            );
            
            $db->exec($createTempTable);
            
            // Get column info
            $columns = [];
            $stmt = $db->query("PRAGMA table_info('$tableName')");
            while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $col['name'];
            }
            
            // Copy data to temporary table
            $columnList = '"' . implode('", "', $columns) . '"';
            $db->exec("INSERT INTO \"$tempTable\" ($columnList) SELECT $columnList FROM \"$tableName\"");
            
            // Drop original table
            $db->exec("DROP TABLE \"$tableName\"");
            
            // Rename temporary table to original name
            $db->exec("ALTER TABLE \"$tempTable\" RENAME TO \"$tableName\"");
            
            // Recreate indexes
            $indexes = $db->query("SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name='$tableName' AND sql IS NOT NULL");
            foreach ($indexes as $index) {
                $db->exec($index['sql']);
            }
            
            // Commit transaction for this table
            $db->commit();
            print_status("Table $tableName updated successfully", 'success');
            
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception("Error processing table $tableName: " . $e->getMessage());
        }
    }
    
    print_status("All tables processed successfully!", 'success');
    
} catch (Exception $e) {
    print_status("Error: " . $e->getMessage(), 'error');
    
    // Try to restore from backup if possible
    if (file_exists($backupFile)) {
        print_status("Attempting to restore from backup...", 'warning');
        if (@copy($backupFile, $dbFile)) {
            print_status("Successfully restored from backup", 'success');
        } else {
            print_status("Failed to restore from backup. Please restore manually from: " . 
                        htmlspecialchars($backupFile), 'error');
        }
    }
    
    die();
}

// Show next steps
echo "<div style='margin-top: 30px; padding: 15px; background-color: #e3f2fd; border-left: 4px solid #2196F3;'>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li><a href='verify-final-collation.php'>Verify the collation changes</a></li>";
echo "<li>Test your application to ensure everything works correctly</li>";
echo "<li>If everything works, you can delete the backup file: <code>" . 
     htmlspecialchars($backupFile) . "</code></li>";
echo "<li>Delete this script when done: <code>rm public/simple-collation-fix.php</code></li>";
echo "</ol>";
echo "</div>";

// Self-delete the script after execution
@unlink(__FILE__);
?>
