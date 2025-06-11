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
    .sql { 
        background: #f0f0f0; 
        padding: 5px 10px; 
        border-radius: 3px; 
        font-family: monospace;
        margin: 5px 0;
    }
</style>";

echo "<h1>Final Database Collation Fix</h1>";

// Database paths
$dbDir = __DIR__ . '/../database';
$dbFile = "$dbDir/database.sqlite";
$backupFile = "$dbFile.backup." . date('YmdHis');
$newDbFile = "$dbFile.new";

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
    // Connect to source database
    print_status("Connecting to source database...");
    $sourceDb = new PDO("sqlite:$dbFile");
    $sourceDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Remove new database if it exists
    if (file_exists($newDbFile)) {
        unlink($newDbFile);
    }
    
    // Create new database
    print_status("Creating new database with proper collation...");
    $newDb = new PDO("sqlite:$newDbFile");
    $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set up the new database
    $newDb->exec('PRAGMA journal_mode = WAL');
    $newDb->exec('PRAGMA foreign_keys = OFF');
    
    // Get all tables from source database
    $tables = $sourceDb->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
                     ->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tables)) {
        throw new Exception("No tables found in the source database");
    }
    
    print_status("Found " . count($tables) . " tables to process", 'success');
    
    // Begin transaction
    $newDb->beginTransaction();
    
    // Process each table
    foreach ($tables as $table) {
        $tableName = $table['name'];
        print_status("Processing table: $tableName");
        
        // Get the original CREATE TABLE statement
        $createTable = $table['sql'];
        
        // Add COLLATE NOCASE to all text columns
        $createTable = preg_replace(
            '/(TEXT|VARCHAR\s*\([^)]*\))([^,)]*)(,|$)/i',
            '$1 COLLATE NOCASE$2$3',
            $createTable
        );
        
        // Create the table in the new database
        $newDb->exec($createTable);
        
        // Get column info
        $columns = $sourceDb->query("PRAGMA table_info('$tableName')")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_map(function($col) { return '`' . $col['name'] . '`'; }, $columns);
        $columnList = implode(', ', $columnNames);
        
        // Copy data
        $insertSql = "INSERT INTO `$tableName` ($columnList) SELECT $columnList FROM main.`$tableName`";
        $newDb->exec("ATTACH DATABASE '$dbFile' AS main");
        $newDb->exec($insertSql);
        $newDb->exec("DETACH DATABASE main");
        
        print_status("Table $tableName processed successfully", 'success');
    }
    
    // Get and recreate indexes
    $indexes = $sourceDb->query("SELECT name, sql FROM sqlite_master WHERE type='index' AND sql IS NOT NULL")
                      ->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($indexes as $index) {
        try {
            $newDb->exec($index['sql']);
            print_status("Recreated index: {$index['name']}", 'success');
        } catch (Exception $e) {
            print_status("Skipping index {$index['name']}: " . $e->getMessage(), 'warning');
        }
    }
    
    // Commit transaction
    $newDb->commit();
    $newDb->exec('PRAGMA foreign_keys = ON');
    
    // Close connections
    $sourceDb = null;
    $newDb = null;
    
    // Replace old database with new one
    if (file_exists($dbFile . '.old')) {
        unlink($dbFile . '.old');
    }
    
    // Create final backup of old database
    rename($dbFile, $dbFile . '.old');
    
    // Rename new database
    if (!rename($newDbFile, $dbFile)) {
        throw new Exception("Failed to replace old database with new one");
    }
    
    // Remove old database backup
    unlink($dbFile . '.old');
    
    print_status("Database collation update complete!", 'success');
    
} catch (Exception $e) {
    // Try to restore from backup if possible
    if (file_exists($backupFile)) {
        print_status("Error occurred. Attempting to restore from backup...", 'warning');
        if (@copy($backupFile, $dbFile)) {
            print_status("Successfully restored from backup", 'success');
        } else {
            print_status("Failed to restore from backup. Please restore manually from: " . 
                        htmlspecialchars($backupFile), 'error');
        }
    }
    
    print_status("Error: " . $e->getMessage(), 'error');
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
echo "<li>Delete this script when done: <code>rm public/final-collation-fix.php</code></li>";
echo "</ol>";
echo "</div>";

// Self-delete the script after execution
@unlink(__FILE__);
?>
