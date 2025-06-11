<?php
// Database configuration
define('DB_DRIVER', 'sqlite');
define('DB_PATH', __DIR__ . '/../database/database.sqlite');

// Application configuration
define('APP_NAME', 'CosmicHub');
define('APP_URL', 'http://cosmichub.local');

// Session configuration
define('SESSION_NAME', 'cosmichub_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Security
define('HASH_COST', 12); // bcrypt cost factor

// Email configuration (for production, use environment variables)
define('MAIL_FROM_ADDRESS', 'noreply@cosmichub.local');
define('MAIL_FROM_NAME', 'CosmicHub');
