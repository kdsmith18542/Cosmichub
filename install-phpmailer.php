<?php
// Script to download and install PHPMailer

echo "Installing PHPMailer...\n";

// Create vendor directory if it doesn't exist
if (!file_exists('vendor')) {
    if (!mkdir('vendor', 0777, true)) {
        die("❌ Failed to create vendor directory\n");
    }
    echo "✅ Created vendor directory\n";
}

// Download PHPMailer
$phpmailerUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip';
$zipFile = 'phpmailer.zip';
$phpmailerDir = 'vendor/phpmailer';

// Download the file
if (!file_exists($zipFile)) {
    echo "Downloading PHPMailer...\n";
    $zipContent = file_get_contents($phpmailerUrl);
    if ($zipContent === false) {
        die("❌ Failed to download PHPMailer\n");
    }
    
    if (file_put_contents($zipFile, $zipContent) === false) {
        die("❌ Failed to save PHPMailer zip file\n");
    }
    echo "✅ Downloaded PHPMailer\n";
}

// Extract the zip file
if (!file_exists($phpmailerDir)) {
    echo "Extracting PHPMailer...\n";
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        // Create the target directory
        if (!file_exists($phpmailerDir)) {
            mkdir($phpmailerDir, 0777, true);
        }
        // Extract to the vendor directory
        $zip->extractTo($phpmailerDir);
        $zip->close();
        echo "✅ Extracted PHPMailer\n";
        
        // Clean up the zip file
        unlink($zipFile);
        echo "✅ Cleaned up installation files\n";
    } else {
        die("❌ Failed to extract PHPMailer\n");
    }
}

// Create a simple autoloader for PHPMailer
$autoloaderContent = <<<'AUTOLOADER'
<?php
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'PHPMailer\\PHPMailer\\';
    
    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/phpmailer/PHPMailer-6.9.1/src/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
AUTOLOADER;

file_put_contents('vendor/autoload.php', $autoloaderContent);
echo "✅ Created autoloader\n";

echo "\n✅ PHPMailer installation complete!\n";
echo "You can now use PHPMailer in your scripts.\n\n";
echo "To test the email functionality, run:\n";
echo 'C:\wamp64\bin\php\php8.1.31\php.exe test-phpmailer.php' . "\n\n";
echo "Don't forget to update the SMTP credentials in test-phpmailer.php with your Mailtrap credentials.\n";
