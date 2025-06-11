<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to HTML with UTF-8
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Fix Text Column Collation</h1>";

// Database file path
$dbFile = __DIR__ . '/../database/database.sqlite';
$backupFile = $dbFile . '.backup.' . date('YmdHis');

// Create backup
if (!copy($dbFile, $backupFile)) {
    die("<p style='color:red'>Failed to create database backup. Please check file permissions.</p>");
}

echo "<p>✅ Created backup at: " . htmlspecialchars($backupFile) . "</p>";

// Connect to SQLite
try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // Get all tables
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
                 ->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Updating Text Column Collation</h2><ul>";
    
    foreach ($tables as $table) {
        echo "<li>Processing table: <strong>" . htmlspecialchars($table) . "</strong>";
        
        try {
            // Get columns that need collation
            $columns = $pdo->query("
                SELECT name, type 
                FROM pragma_table_info('$table') 
                WHERE type LIKE '%TEXT%' OR type LIKE '%VARCHAR%'
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($columns)) {
                echo " - No text columns to update";
                continue;
            }
            
            // Begin transaction for this table
            $pdo->beginTransaction();
            
            // Create new table with _new suffix
            $newTable = $table . '_new';
            
            // Get the original CREATE TABLE statement
            $createTable = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
            
            // Create new table with proper collation
            $createNewTable = str_ireplace(
                "CREATE TABLE \"$table\"",
                "CREATE TABLE \"$newTable\"",
                $createTable
            );
            
            // Add COLLATE NOCASE to text columns
            foreach ($columns as $col) {
                $createNewTable = preg_replace(
                    '/(' . preg_quote($col['name'], '/') . '\s+' . preg_quote($col['type'], '/') . ')([^,)]*)/i',
                    '$1 COLLATE NOCASE$2',
                    $createNewTable
                );
            }
            
            // Create the new table
            $pdo->exec($createNewTable);
            
            // Get all columns for data copy
            $allColumns = $pdo->query("SELECT name FROM pragma_table_info('$table')")->fetchAll(PDO::FETCH_COLUMN);
            $columnList = '"' . implode('", "', $allColumns) . '"';
            
            // Copy data to new table
            $pdo->exec("INSERT INTO \"$newTable\" ($columnList) SELECT $columnList FROM \"$table\"");
            
            // Drop old table and rename new one
            $pdo->exec("DROP TABLE \"$table\"");
            $pdo->exec("ALTER TABLE \"$newTable\" RENAME TO \"$table\"");
            
            // Recreate indexes
            $indexes = $pdo->query("SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name='$table' AND sql IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($indexes as $sql) {
                $pdo->exec($sql);
            }
            
            // Commit transaction for this table
            $pdo->commit();
            
            echo " - <span style='color:green'>Updated text columns</span>";
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo " - <span style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</span>";
        }
        
        echo "</li>";
    }
    
    echo "</ul><p>✅ Text column collation update complete!</p>";
    
} catch (Exception $e) {
    echo "<div style='color:red; padding:10px; margin:10px 0; border:1px solid #f00;'>";
    echo "<h2>Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>The database has been restored from backup.</p>";
    echo "</div>";
}

// Show next steps
echo "<div style='margin-top: 20px; padding: 10px; background: #e8f5e9; border: 1px solid #c8e6c9;'>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li><a href='verify-collation.php'>Verify the collation changes</a></li>";
echo "<li>Test your application to ensure everything works correctly</li>";
echo "<li>If everything works, you can delete the backup file: <code>" . htmlspecialchars($backupFile) . "</code></li>";
echo "<li>Delete these scripts when done: <code>rm public/fix-text-collation.php public/verify-collation.php</code></li>";
echo "</ol>";
echo "</div>";
