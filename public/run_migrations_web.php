<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Determine if running from CLI or web
$isCli = (php_sapi_name() === 'cli');

// If running from CLI, skip token check and use plain text output
if (!$isCli) {
    // Basic security check - require a specific query parameter
    if (!isset($_GET['secret_token']) || $_GET['secret_token'] !== 'YOUR_VERY_SECRET_TOKEN_CHANGE_ME') {
        header('HTTP/1.0 403 Forbidden');
        echo '<h1>403 Forbidden</h1>';
        echo '<p>Access denied. Please provide the correct secret token.</p>';
        exit;
    }
    
    // Start output buffering to capture any errors
    ob_start();
    
    // Output HTML header
    echo '<!DOCTYPE html>';
    echo '<html><head><title>Database Migrations</title>';
    echo '<style>';
    echo 'body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; }';
    echo 'h1 { color: #333; }';
    echo '.container { background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }';
    echo '.log { border: 1px solid #ddd; padding: 10px; margin-top: 15px; background-color: #f9f9f9; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto; }';
    echo '.success { color: green; }';
    echo '.error { color: red; }';
    echo '</style>';
    echo '</head><body>';
    echo '<div class="container">';
    echo '<h1>Database Migration Runner</h1>';
}

// Helper function to output messages based on environment
function output($message, $class = '') {
    global $isCli;
    
    if ($isCli) {
        echo $message . "\n";
    } else {
        if ($class) {
            echo "<p class='{$class}'>{$message}</p>";
        } else {
            echo "<p>{$message}</p>";
        }
    }
}

try {
    // Include bootstrap file
    output("Loading bootstrap file...");
    require_once __DIR__ . '/../bootstrap.php';
    
    // Get database configuration
    output("Loading database configuration...");
    $config = require __DIR__ . '/../app/config/database.php';
    
    // Create SQLite database if it doesn't exist
    if ($config['driver'] === 'sqlite' && !file_exists($config['database'])) {
        touch($config['database']);
        output("SQLite database file created at: " . $config['database'], 'success');
    } else {
        output("Using existing database at: " . $config['database']);
    }
    
    // Create PDO connection
    output("Creating database connection...");
    $dsn = "sqlite:" . $config['database'];
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Include the DatabaseMigrator class
    output("Loading DatabaseMigrator class...");
    require_once __DIR__ . '/../database/migrate.php';
    
    // Check if DatabaseMigrator class exists
    if (!class_exists('DatabaseMigrator')) {
        throw new Exception("DatabaseMigrator class not found. Check the migrate.php file.");
    }
    
    // Create migrator instance with the correct migrations path
    $migrationsPath = __DIR__ . '/../database/migrations';
    output("Creating migrator with migrations path: {$migrationsPath}");
    $migrator = new DatabaseMigrator($pdo, $migrationsPath);
    
    if (!$isCli) {
        echo '<div class="log">';
    }
    
    // Get applied migrations before running
    $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id");
    $appliedBefore = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!$isCli) {
        echo "<strong>Applied migrations before this run:</strong><br>";
        if (empty($appliedBefore)) {
            echo "- None<br>";
        } else {
            foreach ($appliedBefore as $mig) {
                echo "- " . htmlspecialchars($mig) . "<br>";
            }
        }
        echo "<hr>";
    } else {
        output("Applied migrations before this run:");
        if (empty($appliedBefore)) {
            output("- None");
        } else {
            foreach ($appliedBefore as $mig) {
                output("- {$mig}");
            }
        }
        output("----------------------------");
    }

    // Capture output from migrate method
    ob_start();
    $migrator->migrate();
    $migrateOutput = ob_get_clean();
    
    if (!$isCli) {
        echo nl2br(htmlspecialchars($migrateOutput));
    } else {
        echo $migrateOutput;
    }
    
    if (!$isCli) {
        echo "<hr>";
    } else {
        output("----------------------------");
    }
    
    // Get applied migrations after running
    $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id");
    $appliedAfter = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!$isCli) {
        echo "<strong>Applied migrations after this run:</strong><br>";
        if (empty($appliedAfter)) {
            echo "- None<br>";
        } else {
            foreach ($appliedAfter as $mig) {
                echo "- " . htmlspecialchars($mig) . "<br>";
            }
        }
    } else {
        output("Applied migrations after this run:");
        if (empty($appliedAfter)) {
            output("- None");
        } else {
            foreach ($appliedAfter as $mig) {
                output("- {$mig}");
            }
        }
    }

    if (!$isCli) {
        echo '</div>'; // End log div
    }

} catch (PDOException $e) {
    $errorMsg = "PDO Error: " . $e->getMessage();
    $stackTrace = $e->getTraceAsString();
    
    if (!$isCli) {
        echo "<p class='error'>{$errorMsg}</p>";
        echo "<p class='error'>Stack trace: <pre>{$stackTrace}</pre></p>";
    } else {
        output($errorMsg, 'error');
        output("Stack trace:\n{$stackTrace}", 'error');
    }
} catch (Exception $e) {
    $errorMsg = "General Error: " . $e->getMessage();
    $stackTrace = $e->getTraceAsString();
    
    if (!$isCli) {
        echo "<p class='error'>{$errorMsg}</p>";
        echo "<p class='error'>Stack trace: <pre>{$stackTrace}</pre></p>";
    } else {
        output($errorMsg, 'error');
        output("Stack trace:\n{$stackTrace}", 'error');
    }
}

if (!$isCli) {
    // Get any buffered output
    $output = ob_get_clean();
    echo $output;

    echo '<p><a href="?secret_token=YOUR_VERY_SECRET_TOKEN_CHANGE_ME">Run Migrations Again</a></p>';
    echo '<p><strong>Important:</strong> Remember to change `YOUR_VERY_SECRET_TOKEN_CHANGE_ME` in the script and in the URL to something unique and secret!</p>';
    echo '</div>'; // End container
    echo '</body></html>';
}
?>