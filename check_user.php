<?php
// Load configuration
$config = require __DIR__ . '/app/config/config.php';

// Connect to SQLite database
try {
    $db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all users
    $stmt = $db->query('SELECT id, name, email, credits, subscription_status FROM users');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Users in database:\n";
    print_r($users);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
