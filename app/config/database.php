<?php
/**
 * Database configuration for SQLite
 */

// Ensure database directory exists
$dbDir = __DIR__ . '/../../database';
if (!is_dir($dbDir)) {
    if (!mkdir($dbDir, 0755, true)) {
        die('Failed to create database directory');
    }
}

// Database file path
$databaseFile = $dbDir . '/database.sqlite';

// Create database file if it doesn't exist
if (!file_exists($databaseFile)) {
    if (!touch($databaseFile)) {
        die('Failed to create database file');
    }
    chmod($databaseFile, 0666);
}

// Set SQLite PRAGMAs for proper character handling
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

return [
    'driver'    => 'sqlite',
    'database'  => $databaseFile,
    'prefix'    => '',
    'foreign_key_constraints' => true,
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'   => $pdoOptions,
];
