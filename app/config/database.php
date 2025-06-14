<?php
/**
 * Database configuration for SQLite
 */

// Ensure database directory exists
$dbDir = __DIR__ . '/../../database';
if (!is_dir($dbDir)) {
    // Check if $logger is available from the bootstrapping scope
    if (isset($logger) && $logger instanceof \App\Core\Logging\LoggerInterface) {
        $logger->error('Failed to create database directory: ' . $dbDir);
    } else {
        \App\Support\Log::error('Failed to create database directory: ' . $dbDir);
    }
    die('Database configuration error. Please contact support.');
}

// Database file path
$databaseFile = $dbDir . '/database.sqlite';

// Create database file if it doesn't exist
if (!file_exists($databaseFile)) {
    if (!touch($databaseFile)) {
        // Check if $logger is available from the bootstrapping scope
        if (isset($logger) && $logger instanceof \App\Core\Logging\LoggerInterface) {
            $logger->error('Failed to create database file: ' . $databaseFile);
        } else {
            \App\Support\Log::error('Failed to create database file: ' . $databaseFile);
        }
        die('Database configuration error. Please contact support.');
    }
    // Set secure permissions for shared hosting
    chmod($databaseFile, 0640);
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
