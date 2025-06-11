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
    
    echo "=== Database Schema Verification ===\n\n";
    
    // Check users table
    echo "Table: users\n";
    echo str_repeat("-", 80) . "\n";
    
    $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll();
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo sprintf("- %-20s %-15s %s\n", 
            $col['name'], 
            $col['type'],
            $col['notnull'] ? 'NOT NULL' : 'NULLABLE'
        );
    }
    
    // Check user_tokens table
    echo "\nTable: user_tokens\n";
    echo str_repeat("-", 80) . "\n";
    
    $columns = $pdo->query("PRAGMA table_info(user_tokens)")->fetchAll();
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo sprintf("- %-20s %-15s %s\n", 
            $col['name'], 
            $col['type'],
            $col['notnull'] ? 'NOT NULL' : 'NULLABLE'
        );
    }
    
    // Check foreign key constraints
    echo "\nForeign Key Constraints:\n";
    echo str_repeat("-", 80) . "\n";
    
    $fks = $pdo->query("PRAGMA foreign_key_list('user_tokens')")->fetchAll();
    if (empty($fks)) {
        echo "No foreign key constraints found for user_tokens table.\n";
    } else {
        foreach ($fks as $fk) {
            echo sprintf("- %s.%s -> %s(%s) ON DELETE %s\n",
                'user_tokens',
                $fk['from'],
                $fk['table'],
                $fk['to'],
                $fk['on_delete']
            );
        }
    }
    
    // Check if email_verified_at exists in users table
    $emailVerifiedExists = $pdo->query("
        SELECT 1 FROM pragma_table_info('users') 
        WHERE name = 'email_verified_at'
    ")->fetchColumn();
    
    echo "\nVerification:\n";
    echo str_repeat("-", 80) . "\n";
    echo "- email_verified_at column exists in users table: " . ($emailVerifiedExists ? 'YES' : 'NO') . "\n";
    
    // Count records in user_tokens table
    $tokenCount = $pdo->query("SELECT COUNT(*) FROM user_tokens")->fetchColumn();
    echo "- Number of records in user_tokens table: $tokenCount\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
