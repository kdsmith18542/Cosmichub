<?php
// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file location (common WAMP locations)
$logFiles = [
    'C:\\wamp64\\logs\\php_error.log',
    'C:\\wamp64\\apache2\\logs\\error.log',
    'C:\\wamp64\\logs\\apache_error.log',
    $_SERVER['DOCUMENT_ROOT'] . '/../logs/php_error.log',
    $_SERVER['DOCUMENT_ROOT'] . '/logs/error.log',
    dirname(__DIR__) . '/logs/error.log',
    ini_get('error_log')
];

// Try to find the log file
$logContent = '';
$logFile = '';

foreach ($logFiles as $file) {
    if (file_exists($file) && is_readable($file)) {
        $logFile = $file;
        $logContent = file_get_contents($file);
        // Get last 50 lines if the file is large
        if (strlen($logContent) > 50000) {
            $lines = file($file);
            $logContent = implode('', array_slice($lines, -50));
        }
        break;
    }
}

// Display the log content
?>
<!DOCTYPE html>
<html>
<head>
    <title>Error Logs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .error { color: #d32f2f; }
        .info { color: #1976d2; }
    </style>
</head>
<body>
    <h1>PHP Error Logs</h1>
    
    <?php if ($logFile): ?>
        <p><strong>Log file:</strong> <?php echo htmlspecialchars($logFile); ?></p>
        <p><strong>Last modified:</strong> <?php echo date('Y-m-d H:i:s', filemtime($logFile)); ?></p>
        <h2>Recent Errors:</h2>
        <pre><?php echo htmlspecialchars($logContent); ?></pre>
    <?php else: ?>
        <p class="error">Could not find any error log files. Checked the following locations:</p>
        <ul>
            <?php foreach ($logFiles as $file): ?>
                <li><?php echo htmlspecialchars($file); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <h2>PHP Info</h2>
    <p><a href="phpinfo.php" target="_blank">View PHP Info</a></p>
    
    <h2>Check Database Connection</h2>
    <p><a href="check-db.php">Check Database Status</a></p>
</body>
</html>
