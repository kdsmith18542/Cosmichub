<?php
// Migration: Add is_unlocked and unlock_method columns to reports table

$pdo = require __DIR__ . '/../bootstrap.php';

// Add columns if they do not exist
$pdo->exec("ALTER TABLE reports ADD COLUMN is_unlocked BOOLEAN DEFAULT 0;");
$pdo->exec("ALTER TABLE reports ADD COLUMN unlock_method VARCHAR(32) DEFAULT NULL;");

// For rollback (optional)
// $pdo->exec("ALTER TABLE reports DROP COLUMN is_unlocked;");
// $pdo->exec("ALTER TABLE reports DROP COLUMN unlock_method;");