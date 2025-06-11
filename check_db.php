<?php
// Load configuration
$config = require __DIR__ . '/app/config/config.php';

// Connect to SQLite database
try {
    $db = new PDO(
        'sqlite:' . __DIR__ . '/database/database.sqlite'
    );
    
    // Set error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful!\n\n";
    
    // List all tables
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        
        // Get table info
        $columns = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($columns)) {
            echo "  Columns:\n";
            foreach ($columns as $column) {
                echo "  - {$column['name']} ({$column['type']})\n";
            }
        }
        
        // Get row count
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "  Rows: $count\n\n";
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
