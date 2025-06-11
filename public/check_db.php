<?php
// Script to check database structure

try {
    // Use absolute path to the database
    $dbPath = realpath(__DIR__ . '/../database/database.sqlite');
    echo "Database path: $dbPath\n";
    
    if (!file_exists($dbPath)) {
        echo "Database file does not exist!\n";
        exit(1);
    }
    
    echo "File size: " . filesize($dbPath) . " bytes\n";
    
    // Connect to the database
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // List all tables
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    echo "\nTables in database:\n";
    $tableNames = [];
    while ($table = $tables->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$table['name']}\n";
        $tableNames[] = $table['name'];
    }
    
    // Check if migrations table exists and show its content
    if (in_array('migrations', $tableNames)) {
        $stmt = $pdo->query("SELECT * FROM migrations ORDER BY id");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nMigrations in database:\n";
        if (empty($rows)) {
            echo "No migrations found in the database.\n";
        } else {
            foreach ($rows as $row) {
                echo "ID: {$row['id']}, Migration: {$row['migration']}, Batch: {$row['batch']}, Created: {$row['created_at']}\n";
            }
        }
    } else {
        echo "\nMigrations table does not exist!\n";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
?>