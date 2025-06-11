<?php

// Possible PHP executable paths
$possiblePaths = [
    'C:\\wamp\\bin\\php\\php8.2.13\\php.exe',
    'C:\\wamp64\\bin\\php\\php8.2.13\\php.exe',
    'C:\\wamp\\bin\\php\\php8.3.14\\php.exe',
    'C:\\wamp64\\bin\\php\\php8.3.14\\php.exe',
    'C:\\xampp\\php\\php.exe'
];

$phpPath = null;

// Find the first existing PHP executable
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $phpPath = $path;
        break;
    }
}

if ($phpPath === null) {
    die("Could not find PHP executable in common locations. Please ensure WAMP/XAMPP is installed correctly.\n");
}

echo "Found PHP executable at: {$phpPath}\n";

// Run the migration
$migrationScript = __DIR__ . '/database/migrate.php';
if (!file_exists($migrationScript)) {
    die("Migration script not found at: {$migrationScript}\n");
}

echo "Running migration...\n";
$output = [];
$returnVar = 0;

exec("\"{$phpPath}\" \"{$migrationScript}\"", $output, $returnVar);

echo "Migration completed with status code: {$returnVar}\n";
if (!empty($output)) {
    echo "Output:\n" . implode("\n", $output) . "\n";
}