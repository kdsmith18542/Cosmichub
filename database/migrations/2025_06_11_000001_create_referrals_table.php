<?php

use App\Libraries\Database;

class CreateReferralsTable {
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up() {
        $db = Database::getInstance();
        $pdo = $db->getPdo();
        
        // Create referrals table
        $pdo->exec("CREATE TABLE IF NOT EXISTS referrals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            referral_code VARCHAR(64) NOT NULL UNIQUE,
            type VARCHAR(50) NOT NULL DEFAULT 'rarity-score',
            successful_referrals INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // Create referral_conversions table to track individual referrals
        $pdo->exec("CREATE TABLE IF NOT EXISTS referral_conversions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            referral_id INTEGER NOT NULL,
            referred_user_id INTEGER NOT NULL,
            converted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (referral_id) REFERENCES referrals(id) ON DELETE CASCADE,
            FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // Create indexes
        $pdo->exec("CREATE INDEX idx_referrals_user_id ON referrals(user_id)");
        $pdo->exec("CREATE INDEX idx_referrals_referral_code ON referrals(referral_code)");
        $pdo->exec("CREATE INDEX idx_referral_conversions_referral_id ON referral_conversions(referral_id)");
        $pdo->exec("CREATE INDEX idx_referral_conversions_referred_user_id ON referral_conversions(referred_user_id)");
        
        return true;
    }
    
    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down() {
        $db = Database::getInstance();
        $pdo = $db->getPdo();
        
        // Drop tables
        $pdo->exec("DROP TABLE IF EXISTS referral_conversions");
        $pdo->exec("DROP TABLE IF EXISTS referrals");
        
        return true;
    }
}