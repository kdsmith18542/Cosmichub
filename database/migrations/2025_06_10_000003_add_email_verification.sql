-- Disable foreign key checks temporarily
PRAGMA foreign_keys = OFF;

-- Create user_tokens table if it doesn't exist
CREATE TABLE IF NOT EXISTS user_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    type VARCHAR(50) NOT NULL DEFAULT 'email_verification',
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- Create indexes for user_tokens if they don't exist
CREATE INDEX IF NOT EXISTS idx_user_tokens_user_id ON user_tokens (user_id);
CREATE INDEX IF NOT EXISTS idx_user_tokens_token ON user_tokens (token);
CREATE INDEX IF NOT EXISTS idx_user_tokens_type ON user_tokens (type);
CREATE INDEX IF NOT EXISTS idx_user_tokens_expires_at ON user_tokens (expires_at);
CREATE INDEX IF NOT EXISTS idx_user_tokens_used_at ON user_tokens (used_at);

-- Add columns to users table if they don't exist
PRAGMA foreign_keys=off;

-- Add email_verification_token if it doesn't exist
BEGIN TRY
    ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(100) DEFAULT NULL;
    SELECT 'Added email_verification_token column' as result;
EXCEPTION
    SELECT 'email_verification_token column already exists' as result;
END TRY;

-- Add email_verification_sent_at if it doesn't exist
BEGIN TRY
    ALTER TABLE users ADD COLUMN email_verification_sent_at DATETIME DEFAULT NULL;
    SELECT 'Added email_verification_sent_at column' as result;
EXCEPTION
    SELECT 'email_verification_sent_at column already exists' as result;
END TRY;

-- Add email_verified_at if it doesn't exist
BEGIN TRY
    ALTER TABLE users ADD COLUMN email_verified_at DATETIME DEFAULT NULL;
    SELECT 'Added email_verified_at column' as result;
EXCEPTION
    SELECT 'email_verified_at column already exists' as result;
END TRY;

PRAGMA foreign_keys=on;

-- Recreate indexes for users table
CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);
CREATE INDEX IF NOT EXISTS idx_email_verification_token ON users (email_verification_token);

-- Re-enable foreign key checks
PRAGMA foreign_keys = ON;
