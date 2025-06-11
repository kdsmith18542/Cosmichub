<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require 'bootstrap.php';
    echo "Bootstrap loaded successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}