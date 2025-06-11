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
    }
</style>";

echo "<h1>Fix Daily Vibes Table</h1>";

// Database paths
$dbDir = __DIR__ . '/../database';
$dbFile = "$dbDir/database.sqlite";
$backupFile = "$dbFile.backup." . date('YmdHis');
$tableName = 'daily_vibes';

// Step 1: Verify database exists
print_status("Verifying database file...");
if (!file_exists($dbFile)) {
    die("<div class='error'>Database file not found at: " . htmlspecialchars($dbFile) . "</div>");
}

try {
    // Connect to database
    print_status("Connecting to database...");
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get table info
    print_status("Checking table structure...");
    $tableInfo = $db->query("PRAGMA table_info($tableName)")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tableInfo)) {
        throw new Exception("Table '$tableName' not found in database");
    }
    
    // Display current structure
    print_status("Current table structure:", 'info');
    echo "<pre>";
    foreach ($tableInfo as $column) {
        echo "{$column['name']} | {$column['type']} | ";
        echo "Null: " . ($column['notnull'] ? 'NO' : 'YES') . " | ";
        echo "Default: " . ($column['dflt_value'] ?: 'NULL') . "\n";
    }
    echo "</pre>";
    
    // Check if we need to fix the table
    $needsFix = false;
    $hasVibeText = false;
    
    foreach ($tableInfo as $column) {
        if ($column['name'] === 'vibe_text') {
            $hasVibeText = true;
            if (stripos($column['type'], 'TEXT') === false) {
                $needsFix = true;
            }
        }
    }
    
    if (!$hasVibeText) {
        throw new Exception("Column 'vibe_text' not found in table '$tableName'");
    }
    
    if (!$needsFix) {
        print_status("The 'vibe_text' column already has the correct TEXT type.", 'success');
        echo "<p>No changes needed.</p>";
        exit;
    }
    
    // Create backup
    print_status("Creating backup...");
    if (!copy($dbFile, $backupFile)) {
        throw new Exception("Failed to create backup. Please check file permissions.");
    }
    print_status("Backup created at: " . htmlspecialchars($backupFile), 'success');
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Create a temporary table with the correct structure
        $tempTable = $tableName . '_temp';
        
        print_status("Creating temporary table...");
        $db->exec("CREATE TABLE \"$tempTable\" (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            vibe_text TEXT COLLATE NOCASE NOT NULL,
            date DATE NOT NULL,
            created_at TIMESTAMP,
            updated_at TIMESTAMP
        )");
        
        // Copy data to temporary table
        print_status("Copying data to temporary table...");
        $db->exec("INSERT INTO \"$tempTable\" (id, user_id, vibe_text, date, created_at, updated_at) 
                   SELECT id, user_id, vibe_text, date, created_at, updated_at FROM \"$tableName\"");
        
        // Drop original table
        print_status("Dropping original table...");
        $db->exec("DROP TABLE \"$tableName\"");
        
        // Rename temporary table
        print_status("Renaming temporary table...");
        $db->exec("ALTER TABLE \"$tempTable\" RENAME TO \"$tableName\"");
        
        // Recreate indexes
        print_status("Recreating indexes...");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_daily_vibes_user_id ON \"$tableName\" (user_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_daily_vibes_date ON \"$tableName\" (date)");
        
        // Commit transaction
        $db->commit();
        
        print_status("Table '$tableName' has been successfully updated!", 'success');
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
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
echo "<li>Delete this script when done: <code>rm public/fix-daily-vibes.php</code></li>";
echo "</ol>";
echo "</div>";

// Self-delete the script after execution
@unlink(__FILE__);
?>
