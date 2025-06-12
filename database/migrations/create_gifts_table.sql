-- Create gifts table for gift report functionality
-- This table stores information about purchased gift credits

CREATE TABLE IF NOT EXISTS gifts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    gift_code VARCHAR(50) UNIQUE NOT NULL,
    sender_user_id INTEGER NOT NULL,
    sender_name VARCHAR(255) NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NOT NULL,
    gift_message TEXT,
    credits_amount INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    purchase_amount DECIMAL(10,2) NOT NULL,
    stripe_payment_intent_id VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending', -- pending, redeemed, expired
    redeemed_by_user_id INTEGER,
    redeemed_at DATETIME,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (redeemed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_gifts_gift_code ON gifts(gift_code);
CREATE INDEX IF NOT EXISTS idx_gifts_sender_user_id ON gifts(sender_user_id);
CREATE INDEX IF NOT EXISTS idx_gifts_recipient_email ON gifts(recipient_email);
CREATE INDEX IF NOT EXISTS idx_gifts_status ON gifts(status);
CREATE INDEX IF NOT EXISTS idx_gifts_expires_at ON gifts(expires_at);
CREATE INDEX IF NOT EXISTS idx_gifts_created_at ON gifts(created_at);
CREATE INDEX IF NOT EXISTS idx_gifts_redeemed_by_user_id ON gifts(redeemed_by_user_id);

-- Insert sample gift data for testing
INSERT OR IGNORE INTO gifts (
    gift_code, sender_user_id, sender_name, recipient_email, recipient_name,
    gift_message, credits_amount, plan_id, purchase_amount, status, expires_at
) VALUES 
(
    'COSMIC-SAMPLE01',
    1,
    'John Doe',
    'friend@example.com',
    'Jane Smith',
    'Happy Birthday! Hope you enjoy exploring your cosmic blueprint!',
    10,
    1,
    4.95,
    'pending',
    datetime('now', '+1 year')
),
(
    'COSMIC-SAMPLE02',
    1,
    'John Doe',
    'sister@example.com',
    'Sarah Doe',
    'Thought you might find this interesting! Love you sis!',
    25,
    2,
    9.95,
    'redeemed',
    datetime('now', '+1 year')
),
(
    'COSMIC-SAMPLE03',
    2,
    'Alice Johnson',
    'mom@example.com',
    'Mary Johnson',
    'For Mother\'s Day - discover your cosmic secrets!',
    50,
    3,
    19.95,
    'pending',
    datetime('now', '+1 year')
);

-- Update the redeemed gift with redemption details
UPDATE gifts 
SET 
    redeemed_by_user_id = 2,
    redeemed_at = datetime('now', '-5 days')
WHERE gift_code = 'COSMIC-SAMPLE02';