<?php
/**
 * Run database migrations
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database configuration
$config = require __DIR__ . '/../app/config/database.php';

// Function to execute SQL with error handling
function executeSql($pdo, $sql) {
    $sql = trim($sql);
    if (empty($sql)) {
        return 0;
    }
    
    // Add semicolon if missing
    if (!preg_match('/;\s*$/', $sql)) {
        $sql .= ';';
    }
    
    echo "\nExecuting: " . substr($sql, 0, 120) . (strlen($sql) > 120 ? '...' : '') . "\n";
    
    $start = microtime(true);
    $result = $pdo->exec($sql);
    $time = round((microtime(true) - $start) * 1000, 2);
    
    if ($result === false) {
        $error = $pdo->errorInfo();
        throw new Exception("SQL Error: " . ($error[2] ?? 'Unknown error'));
    }
    
    echo "  -> Affected rows: " . $result . " (took {$time}ms)\n";
    return $result;
}

// Create database connection
try {
    $dsn = "sqlite:{$config['database']}";
    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "Connected to database successfully.\n";
    
    // Enable foreign key constraints
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // Read and execute the migration SQL
    $migrationFile = __DIR__ . '/migrations/2025_06_10_000003_add_email_verification.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Remove comments and empty lines
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sql = trim($sql);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;\s*(?=PRAGMA|CREATE|ALTER|DROP|INSERT|UPDATE|DELETE|TRUNCATE|BEGIN|COMMIT|SET)/i', $sql)
        ),
        'strlen'
    );
    
    // Execute each statement in a transaction
    $pdo->beginTransaction();
    
    try {
        foreach ($statements as $statement) {
            if (empty(trim($statement))) {
                continue;
            }
            
            try {
                executeSql($pdo, $statement);
            } catch (Exception $e) {
                // If it's a table already exists error, continue
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "  -> Notice: " . $e->getMessage() . "\n";
                    continue;
                }
                throw $e;
            }
        }
        
        $pdo->commit();
        echo "\nMigration completed successfully.\n";
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if (isset($pdo) && $pdo instanceof PDO) {
        $error = $pdo->errorInfo();
        if (!empty($error[2])) {
            echo "SQL Error: " . $error[2] . "\n";
        }
    }
    
    exit(1);
}
