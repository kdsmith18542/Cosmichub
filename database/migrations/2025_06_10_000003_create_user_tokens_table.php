<?php

// Create user_tokens table with raw SQL for SQLite compatibility
return function ($pdo) {
    // Enable foreign key constraints
    $pdo->exec('PRAGMA foreign_keys = ON;');
    
    // Check if table already exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user_tokens'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        // Create user_tokens table
        $pdo->exec("CREATE TABLE user_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            type VARCHAR(50) NOT NULL DEFAULT 'email_verification',
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        )");
        
        // Create indexes
        $pdo->exec("CREATE INDEX idx_user_tokens_type ON user_tokens (type)");
        $pdo->exec("CREATE INDEX idx_user_tokens_expires_at ON user_tokens (expires_at)");
        $pdo->exec("CREATE INDEX idx_user_tokens_used_at ON user_tokens (used_at)");
        
        // Check if email_verified_at column exists in users table
        $stmt = $pdo->query("PRAGMA table_info(users)");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        
        if (!in_array('email_verified_at', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME DEFAULT NULL");
        }
    }
    
    return true;
};
