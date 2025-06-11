<?php

// Create essential tables for Daily Vibe feature
return function ($pdo) {
    // Enable foreign key constraints
    $pdo->exec('PRAGMA foreign_keys = ON;');
    
    // Check if daily_vibes table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='daily_vibes'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        // Create daily_vibes table
        $pdo->exec("CREATE TABLE daily_vibes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            vibe_text TEXT NOT NULL,
            date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            UNIQUE(user_id, date)
        )");
        
        // Create indexes
        $pdo->exec("CREATE INDEX idx_daily_vibes_user_id ON daily_vibes (user_id)");
        $pdo->exec("CREATE INDEX idx_daily_vibes_date ON daily_vibes (date)");
        
        echo "Created daily_vibes table\n";
    } else {
        echo "daily_vibes table already exists\n";
    }
    
    // Check if notifications table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='notifications'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        // Create notifications table
        $pdo->exec("CREATE TABLE notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            url VARCHAR(255) DEFAULT NULL,
            is_read BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        )");
        
        // Create indexes
        $pdo->exec("CREATE INDEX idx_notifications_user_id ON notifications (user_id)");
        $pdo->exec("CREATE INDEX idx_notifications_is_read ON notifications (is_read)");
        
        echo "Created notifications table\n";
    } else {
        echo "notifications table already exists\n";
    }
    
    // Ensure users table has required columns
    $pdo->exec("PRAGMA table_info(users)");
    $columns = array_column($pdo->query("PRAGMA table_info(users)")->fetchAll(), 'name');
    
    if (!in_array('birthdate', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN birthdate DATE DEFAULT NULL");
        echo "Added birthdate column to users table\n";
    }
    
    if (!in_array('zodiac_sign', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN zodiac_sign VARCHAR(20) DEFAULT NULL");
        echo "Added zodiac_sign column to users table\n";
    }
    
    echo "\nDaily Vibe setup complete!\n";
    return true;
};
