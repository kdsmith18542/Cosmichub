<?php

use App\Libraries\Database;

class CreateCelebrityReportsTable
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        $db = Database::getInstance();
        $pdo = $db->getPdo();

        $pdo->exec("CREATE TABLE IF NOT EXISTS celebrity_reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            birth_date DATE NOT NULL,
            report_content TEXT NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE INDEX idx_celebrity_reports_name ON celebrity_reports (name)");
        $pdo->exec("CREATE INDEX idx_celebrity_reports_birth_date ON celebrity_reports (birth_date)");
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        $db = Database::getInstance();
        $pdo = $db->getPdo();

        $pdo->exec("DROP TABLE IF EXISTS celebrity_reports");
    }
}