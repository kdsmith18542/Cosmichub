<?php
// Show all information, defaults to INFO_ALL
phpinfo(INFO_VARIABLES | INFO_ENVIRONMENT | INFO_CONFIGURATION);

// Display error log location and recent errors
echo "<h2>Error Log Information</h2>";
$errorLog = ini_get('error_log');
echo "<p>Error Log Location: " . ($errorLog ? $errorLog : 'Not set') . "</p>";

// Display last 20 lines of error log if it exists and is readable
if ($errorLog && file_exists($errorLog) && is_readable($errorLog)) {
    echo "<h3>Last 20 lines of error log:</h3>";
    echo "<pre>";
    $logContent = file($errorLog);
    $lastLines = array_slice($logContent, -20);
    echo htmlspecialchars(implode("", $lastLines));
    echo "</pre>";
} else {
    echo "<p>Error log not found or not readable at: " . htmlspecialchars($errorLog) . "</p>";
}
?>
