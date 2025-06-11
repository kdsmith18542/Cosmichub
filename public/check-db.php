<?php
/**
 * Database Check Script
 * 
 * This script checks the current database status and tables.
 * Access it through: http://localhost/your-project-folder/public/check-db.php
 */

// Only allow access from localhost for security
$allowed = ['127.0.0.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed)) {
    die('Access denied');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the bootstrap file
require_once __DIR__ . '/../bootstrap.php';

// Get database configuration
$config = require __DIR__ . '/../app/config/database.php';

// Create PDO connection
try {
    $pdo = new PDO("sqlite:{$config['database']}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Could not connect to database: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h4 mb-0">Database Status Check</h1>
                    </div>
                    <div class="card-body">
                        <h2 class="h5">Database Information</h2>
                        <ul class="list-group mb-4">
                            <li class="list-group-item">
                                <strong>Database File:</strong> <?php echo htmlspecialchars($config['database']); ?>
                            </li>
                            <li class="list-group-item">
                                <strong>File Exists:</strong> 
                                <?php echo file_exists($config['database']) ? '✅ Yes' : '❌ No'; ?>
                            </li>
                            <li class="list-group-item">
                                <strong>File Size:</strong> 
                                <?php 
                                if (file_exists($config['database'])) {
                                    echo number_format(filesize($config['database']) / 1024, 2) . ' KB';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </li>
                        </ul>

                        <h2 class="h5">Database Tables</h2>
                        <?php
                        try {
                            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                            if ($tables->rowCount() > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Table Name</th>
                                                <th>Row Count</th>
                                                <th>Columns</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            while ($table = $tables->fetch(PDO::FETCH_ASSOC)): 
                                                $tableName = $table['name'];
                                                $count = $pdo->query("SELECT COUNT(*) as count FROM " . $tableName)->fetch();
                                                $columns = $pdo->query("PRAGMA table_info($tableName)");
                                                $columnList = [];
                                                while ($col = $columns->fetch()) {
                                                    $columnList[] = $col['name'] . ' ' . $col['type'];
                                                }
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($tableName); ?></strong></td>
                                                    <td><?php echo $count['count']; ?></td>
                                                    <td>
                                                        <small class="text-muted"><?php echo htmlspecialchars(implode(', ', $columnList)); ?></small>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">No tables found in the database.</div>
                            <?php endif; 
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Error reading database: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        ?>

                        <div class="mt-4">
                            <a href="/" class="btn btn-primary">Back to Home</a>
                            <a href="sqlite-migrate.php" class="btn btn-secondary">Run Migrations</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
