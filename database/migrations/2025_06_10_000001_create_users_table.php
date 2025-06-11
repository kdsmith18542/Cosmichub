<?php

// This migration is now handled by 0001_initial_schema.sql
// Keeping this file to maintain migration history but making it a no-op

return function ($pdo) {
    // Check if users table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        // Create users table if it doesn't exist
        $pdo->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            credits INTEGER DEFAULT 0,
            subscription_status VARCHAR(50) DEFAULT 'inactive',
            subscription_ends_at DATETIME DEFAULT NULL,
            email_verified_at DATETIME DEFAULT NULL,
            remember_token VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // Add any missing columns from the original migration
    $columns = [
        'birthdate' => 'DATE NULL',
        'verification_token' => 'VARCHAR(100) NULL'
    ];
    
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
    
    foreach ($columns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
        }
    }
};
