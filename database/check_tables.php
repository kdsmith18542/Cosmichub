<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database configuration
$config = require __DIR__ . '/../app/config/database.php';

try {
    // Create database connection
    $dsn = "sqlite:{$config['database']}";
    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "Connected to database successfully.\n\n";
    
    // List all tables
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll();
    
    echo "=== Tables in database ===\n";
    foreach ($tables as $table) {
        $tableName = $table['name'];
        echo "\nTable: {$tableName}\n";
        echo str_repeat("-", 80) . "\n";
        
        // Get table info
        $columns = $pdo->query("PRAGMA table_info({$tableName})")->fetchAll();
        
        if (empty($columns)) {
            echo "No columns found\n";
            continue;
        }
        
        // Display column info
        foreach ($columns as $column) {
            $flags = [];
            if ($column['pk']) $flags[] = 'PRIMARY KEY';
            if ($column['notnull']) $flags[] = 'NOT NULL';
            if ($column['dflt_value'] !== null) $flags[] = 'DEFAULT ' . $column['dflt_value'];
            
            echo sprintf("%-20s %-15s %s\n", 
                $column['name'], 
                $column['type'],
                implode(' ', $flags)
            );
        }
        
        // Show indexes
        $indexes = $pdo->query("PRAGMA index_list({$tableName})")->fetchAll();
        if (!empty($indexes)) {
            echo "\nIndexes:\n";
            foreach ($indexes as $index) {
                $indexName = $index['name'];
                $indexColumns = $pdo->query("PRAGMA index_info({$indexName})")->fetchAll();
                $columnNames = array_column($indexColumns, 'name');
                echo "- {$indexName} (" . implode(', ', $columnNames) . ")\n";
            }
        }
        
        // Show foreign keys
        $foreignKeys = $pdo->query("PRAGMA foreign_key_list({$tableName})")->fetchAll();
        if (!empty($foreignKeys)) {
            echo "\nForeign Keys:\n";
            foreach ($foreignKeys as $fk) {
                echo sprintf("- %s.%s -> %s(%s) ON DELETE %s\n",
                    $tableName,
                    $fk['from'],
                    $fk['table'],
                    $fk['to'],
                    $fk['on_delete']
                );
            }
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
