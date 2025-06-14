<?php

/**
 * Global helper functions for the application
 */

// env() function is defined in app/helpers.php and is more comprehensive.
// To avoid redeclaration errors, it's removed from here.
// Ensure app/helpers.php is loaded if env() is needed by other bootstrap files directly,
// or ensure that bootstrap/app.php loads environment variables sufficiently early
// for config files (which use app/helpers.php's env()).

/**
 * Helper function to get storage path
 */
if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        $storagePath = defined('STORAGE_DIR') ? STORAGE_DIR : __DIR__ . '/../storage';
        return $storagePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

/**
 * Helper function to get database path
 */
if (!function_exists('database_path')) {
    function database_path($path = '') {
        $databasePath = defined('DATABASE_DIR') ? DATABASE_DIR : __DIR__ . '/../database';
        return $databasePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

/**
 * Helper function to create URL-friendly slugs
 */
if (!function_exists('str_slug')) {
    function str_slug($title, $separator = '-') {
        // Convert to lowercase
        $title = strtolower($title);
        
        // Replace non-alphanumeric characters with separator
        $title = preg_replace('/[^a-z0-9]+/', $separator, $title);
        
        // Remove leading/trailing separators
        $title = trim($title, $separator);
        
        return $title;
    }
}