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
    
    echo "=== Fixing Foreign Key Constraints ===\n\n";
    
    // Disable foreign key checks
    $pdo->exec('PRAGMA foreign_keys = OFF');
    
    // 1. Create a new user_tokens table with correct foreign key
    echo "Creating new user_tokens table...\n";
    
    $pdo->exec('DROP TABLE IF EXISTS user_tokens_new');
    
    $sql = "
    CREATE TABLE user_tokens_new (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        type VARCHAR(50) NOT NULL DEFAULT 'email_verification',
        expires_at DATETIME NOT NULL,
        used_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    
    // 2. Create indexes
    echo "Creating indexes...\n";
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_tokens_user_id ON user_tokens_new (user_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_tokens_token ON user_tokens_new (token)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_tokens_type ON user_tokens_new (type)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_tokens_expires_at ON user_tokens_new (expires_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_tokens_used_at ON user_tokens_new (used_at)');
    
    // 3. Copy data from old table if it exists
    echo "Copying data...\n";
    $pdo->exec('INSERT INTO user_tokens_new SELECT * FROM user_tokens');
    
    // 4. Drop old table and rename new one
    echo "Replacing tables...\n";
    $pdo->exec('DROP TABLE IF EXISTS user_tokens_old');
    $pdo->exec('ALTER TABLE user_tokens RENAME TO user_tokens_old');
    $pdo->exec('ALTER TABLE user_tokens_new RENAME TO user_tokens');
    
    // 5. Drop the old users_old table if it exists
    echo "Cleaning up...\n";
    $pdo->exec('DROP TABLE IF EXISTS users_old');
    
    // Re-enable foreign key checks
    $pdo->exec('PRAGMA foreign_key_check');
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    echo "\nForeign key constraints fixed successfully!\n";
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    exit(1);
}

// Verify the fix
echo "\nVerifying the fix...\n";
system('php database/verify_schema.php');
