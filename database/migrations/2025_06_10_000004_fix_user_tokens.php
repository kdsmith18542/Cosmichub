<?php

// Create user_tokens table with raw SQL for SQLite compatibility
return function ($pdo) {
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
        $pdo->exec("CREATE INDEX idx_user_tokens_user_id ON user_tokens (user_id)");
        $pdo->exec("CREATE INDEX idx_user_tokens_token ON user_tokens (token)");
        $pdo->exec("CREATE INDEX idx_user_tokens_type ON user_tokens (type)");
        
        echo "<p>✅ Created user_tokens table and indexes</p>";
    } else {
        echo "<p>ℹ️ user_tokens table already exists</p>";
    }
};
