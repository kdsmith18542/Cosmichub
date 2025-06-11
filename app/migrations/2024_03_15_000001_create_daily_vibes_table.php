<?php

class CreateDailyVibesTable {
    public function up($db) {
        $sql = "CREATE TABLE IF NOT EXISTS daily_vibes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            vibe_text TEXT NOT NULL,
            date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_date (user_id, date),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
    }

    public function down($db) {
        $db->exec("DROP TABLE IF EXISTS daily_vibes");
    }
}
