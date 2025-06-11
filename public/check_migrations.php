<?php
// Script to check migrations table

try {
    // Use absolute path to the database
    $dbPath = realpath(__DIR__ . '/../database/database.sqlite');
    echo "Database path: $dbPath\n";
    
    if (!file_exists($dbPath)) {
        echo "Database file does not exist!\n";
        exit(1);
    }
    
    // Connect to the database
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query the migrations table
    $stmt = $pdo->query("SELECT * FROM migrations ORDER BY id");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Output the results
    echo "\nMigrations in database:\n";
    if (empty($rows)) {
        echo "No migrations found in the database.\n";
    } else {
        foreach ($rows as $row) {
            echo "ID: {$row['id']}, Migration: {$row['migration']}, Batch: {$row['batch']}, Created: {$row['created_at']}\n";
        }
    }
    
    // Also check what migration files exist
    $migrationsDir = realpath(__DIR__ . '/../database/migrations');
    echo "\nMigration Files in Directory ($migrationsDir):\n";
    $migrationFiles = scandir($migrationsDir);
    foreach ($migrationFiles as $file) {
        if ($file != '.' && $file != '..') {
            echo "$file\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
?>