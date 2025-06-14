<?php

/**
 * CosmicHub Framework Entry Point
 * 
 * This file serves as the entry point for the CosmicHub framework.
 * It bootstraps the application and handles the incoming request.
 */

// Bootstrap the application
$app = require __DIR__ . '/../bootstrap/app.php';

// Run the application
$app->run();