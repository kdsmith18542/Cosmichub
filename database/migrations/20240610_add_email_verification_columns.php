<?php
/**
 * Migration to add email verification columns to users table
 */

class AddEmailVerificationColumns {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function up() {
        try {
            // Check if columns already exist
            $stmt = $this->db->query("PRAGMA table_info(users)");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            
            if (!in_array('email_verification_token', $columns)) {
                $this->db->exec("
                    ALTER TABLE users 
                    ADD COLUMN email_verification_token VARCHAR(100) NULL
                ");
                
                $this->db->exec("
                    ALTER TABLE users 
                    ADD COLUMN email_verification_sent_at DATETIME NULL
                ");
                
                $this->db->exec("
                    CREATE INDEX IF NOT EXISTS idx_email_verification_token 
                    ON users (email_verification_token)
                ");
                
                return true;
            }
            return false;
        } catch (PDOException $e) {
            throw new Exception("Migration failed: " . $e->getMessage());
        }
    }
    
    public function down() {
        try {
            // Check if columns exist before trying to drop them
            $stmt = $this->db->query("PRAGMA table_info(users)");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            
            if (in_array('email_verification_token', $columns)) {
                $this->db->exec("
                    CREATE TABLE users_backup AS 
                    SELECT id, username, email, password, created_at, updated_at 
                    FROM users
                ");
                
                $this->db->exec("DROP TABLE users");
                
                $this->db->exec("
                    CREATE TABLE users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        username VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                $this->db->exec("
                    INSERT INTO users (id, username, email, password, created_at, updated_at)
                    SELECT id, username, email, password, created_at, updated_at 
                    FROM users_backup
                ");
                
                $this->db->exec("DROP TABLE users_backup");
                
                return true;
            }
            return false;
        } catch (PDOException $e) {
            throw new Exception("Migration rollback failed: " . $e->getMessage());
        }
    }
}
