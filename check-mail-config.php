<?php
// Display mail configuration
echo "PHP Version: " . phpversion() . "\n\n";

// Check if mail function is available
echo "Mail function available: " . (function_exists('mail') ? 'Yes' : 'No') . "\n";

// Display mail configuration
$mailSettings = [
    'SMTP',
    'smtp_port',
    'sendmail_from',
    'sendmail_path',
    'mail.force_extra_parameters',
    'mail.add_x_header',
    'mail.log'
];

echo "\nMail Configuration:\n";
echo "-----------------\n";
foreach ($mailSettings as $setting) {
    $value = ini_get($setting);
    echo str_pad($setting, 30) . ": " . (empty($value) ? '(not set)' : $value) . "\n";
}

// Check if we can open a socket to the mail server
$mailHost = ini_get('SMTP');
$mailPort = ini_get('smtp_port');

if ($mailHost && $mailPort) {
    echo "\nTesting connection to $mailHost:$mailPort...\n";
    $socket = @fsockopen($mailHost, $mailPort, $errno, $errstr, 10);
    if ($socket) {
        echo "Successfully connected to $mailHost:$mailPort\n";
        fclose($socket);
    } else {
        echo "Failed to connect to $mailHost:$mailPort\n";
        echo "Error $errno: $errstr\n";
    }
} else {
    echo "\nSMTP host or port not configured.\n";
}
?>
