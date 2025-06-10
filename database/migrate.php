<?php
/**
 * Database Migrator
 * 
 * Applies database migrations in the correct order.
 */

require_once __DIR__ . '/../bootstrap.php';

class DatabaseMigrator
{
    private $db;
    private $migrationsDir;
    
    public function __construct(PDO $db, string $migrationsDir)
    {
        $this->db = $db;
        $this->migrationsDir = rtrim($migrationsDir, '/') . '/';
        
        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();
    }
    
    /**
     * Create the migrations table if it doesn't exist
     */
    private function createMigrationsTable()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    /**
     * Get all applied migrations
     */
    private function getAppliedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get all migration files
     */
    private function getMigrationFiles(): array
    {
        $files = [];
        
        if ($handle = opendir($this->migrationsDir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file !== "." && $file !== ".." && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $files[] = $file;
                }
            }
            closedir($handle);
        }
        
        // Sort files by name (which starts with a number)
        sort($files);
        
        return $files;
    }
    
    /**
     * Apply a migration
     */
    private function applyMigration(string $migration): bool
    {
        $sql = file_get_contents($this->migrationsDir . $migration);
        
        try {
            // Begin transaction
            $this->db->beginTransaction();
            
            // Execute the migration SQL
            $this->db->exec($sql);
            
            // Record the migration
            $stmt = $this->db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, 1)");
            $stmt->execute([$migration]);
            
            // Commit transaction
            $this->db->commit();
            
            return true;
        } catch (PDOException $e) {
            // Rollback on error
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Run all pending migrations
     */
    public function migrate(): void
    {
        $applied = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles();
        
        $pending = array_diff($files, $applied);
        
        if (empty($pending)) {
            echo "No migrations to run.\n";
            return;
        }
        
        echo "Running migrations...\n";
        
        foreach ($pending as $migration) {
            echo "- Applying $migration... ";
            
            try {
                $this->applyMigration($migration);
                echo "DONE\n";
            } catch (PDOException $e) {
                echo "FAILED: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
        
        echo "\nMigrations completed successfully.\n";
    }
}

// Run migrations
try {
    // Get database configuration
    $config = require __DIR__ . '/../app/config/database.php';
    
    // Create SQLite database if it doesn't exist
    if ($config['driver'] === 'sqlite' && !file_exists($config['database'])) {
        touch($config['database']);
    }
    
    // Create PDO connection
    $dsn = "sqlite:{$config['database']}";
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Run migrations
    $migrator = new DatabaseMigrator($pdo, __DIR__ . '/migrations');
    $migrator->migrate();
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
