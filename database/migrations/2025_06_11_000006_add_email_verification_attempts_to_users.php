<?php

/**
 * Migration: Add email_verification_attempts column to users table
 * 
 * This migration adds tracking for email verification attempts to implement
 * rate limiting and security measures for email verification.
 */

class AddEmailVerificationAttemptsToUsers
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * Run the migration
     */
    public function up()
    {
        try {
            // Add email_verification_attempts column
            $sql = "ALTER TABLE users ADD COLUMN email_verification_attempts INTEGER DEFAULT 0";
            $this->db->exec($sql);
            
            echo "✓ Added email_verification_attempts column to users table\n";
            
            // Update existing users to have 0 attempts
            $updateSql = "UPDATE users SET email_verification_attempts = 0 WHERE email_verification_attempts IS NULL";
            $this->db->exec($updateSql);
            
            echo "✓ Updated existing users with default email_verification_attempts value\n";
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate column name') !== false) {
                echo "! Column email_verification_attempts already exists\n";
            } else {
                throw $e;
            }
        }
    }
    
    /**
     * Reverse the migration
     */
    public function down()
    {
        try {
            $sql = "ALTER TABLE users DROP COLUMN email_verification_attempts";
            $this->db->exec($sql);
            
            echo "✓ Removed email_verification_attempts column from users table\n";
            
        } catch (PDOException $e) {
            echo "! Error removing email_verification_attempts column: {$e->getMessage()}\n";
            throw $e;
        }
    }
}

// Run the migration if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $migration = new AddEmailVerificationAttemptsToUsers();
        $migration->up();
        echo "Migration completed successfully!\n";
    } catch (Exception $e) {
        echo "Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}