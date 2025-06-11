<?php
// Script to configure PHP for SMTP email sending

// Path to php.ini
$phpIniPath = 'C:\\wamp64\\bin\\php\\php8.1.31\\php.ini';

// Backup the original php.ini
if (!file_exists($phpIniPath . '.backup')) {
    copy($phpIniPath, $phpIniPath . '.backup');
    echo "✅ Created backup of php.ini\n";
} else {
    echo "ℹ️ Backup of php.ini already exists\n";
}

// Read the current php.ini
$config = file_get_contents($phpIniPath);

// SMTP configuration
$smtpConfig = [
    'SMTP' => 'smtp.mailtrap.io',
    'smtp_port' => '2525',
    'sendmail_from' => 'admin@cosmichub.online',
    'mail.add_x_header' => 'On',
    'mail.log' => 'C:\\wamp64\\logs\\php_mail.log',
];

// Update configuration
$updated = false;
foreach ($smtpConfig as $key => $value) {
    $pattern = "/^;?\\s*" . preg_quote($key, '/') . "\s*=[^\r\n]*/m";
    $newSetting = "$key = \"$value\"";
    
    if (preg_match($pattern, $config)) {
        $config = preg_replace($pattern, $newSetting, $config);
        echo "✅ Updated: $newSetting\n";
        $updated = true;
    } else {
        $config .= "\n$newSetting";
        echo "✅ Added: $newSetting\n";
        $updated = true;
    }
}

// Save the updated configuration
if ($updated) {
    file_put_contents($phpIniPath, $config);
    echo "\n✅ php.ini has been updated.\n";
    echo "Please restart your WAMP server for changes to take effect.\n";
} else {
    echo "\nℹ️ No changes were made to php.ini\n";
}

echo "\nNext steps:\n";
echo "1. Restart WAMP server\n";
echo "2. Run test-mail.php again to test the configuration\n";
echo "3. Check the mail log at C:\\wamp64\\logs\\php_mail.log for any errors\n\n";

echo "Note: You'll need to provide SMTP credentials in your .env file.\n";
echo "For testing, you can use Mailtrap (https://mailtrap.io) with these settings:\n";
echo "SMTP Host: smtp.mailtrap.io\n";
echo "SMTP Port: 2525\n";
echo "Username: your_mailtrap_username\n";
echo "Password: your_mailtrap_password\n";
