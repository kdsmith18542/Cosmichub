<?php
// Load database configuration
$config = require __DIR__ . '/app/config/database.php';

try {
    // Create a new PDO instance
    $db = new PDO(
        'sqlite:' . $config['database'],
        null,
        null,
        $config['options'] ?? []
    );
    
    // Set error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful!\n\n";
    
    // Check if users table exists
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchAll();
    
    if (empty($tables)) {
        die("Error: 'users' table does not exist in the database.\n");
    }
    
    echo "Users table structure:\n";
    echo "======================\n";
    
    // Get table info
    $stmt = $db->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        die("Error: Could not retrieve column information for 'users' table.\n");
    }
    
    // Display column information
    printf("%-25s %-10s %-10s %-10s %s\n", "Name", "Type", "Not Null", "Default", "Primary Key");
    echo str_repeat("-", 70) . "\n";
    
    foreach ($columns as $column) {
        printf("%-25s %-10s %-10s %-10s %s\n",
            $column['name'],
            $column['type'],
            $column['notnull'] ? 'YES' : 'NO',
            $column['dflt_value'] ?? 'NULL',
            $column['pk'] ? 'YES' : 'NO'
        );
    }
    
    // Check for indexes
    echo "\nIndexes on users table:\n";
    echo "========================\n";
    
    $indexes = $db->query("PRAGMA index_list('users')")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($indexes)) {
        echo "No indexes found.\n";
    } else {
        foreach ($indexes as $index) {
            echo "- " . $index['name'] . " (" . ($index['unique'] ? 'UNIQUE' : 'NON-UNIQUE') . ")\n";
            
            // Get index columns
            $indexInfo = $db->prepare("PRAGMA index_info(?)");
            $indexInfo->execute([$index['name']]);
            $columns = $indexInfo->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $col) {
                echo "  • " . $col['name'] . "\n";
            }
        }
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
