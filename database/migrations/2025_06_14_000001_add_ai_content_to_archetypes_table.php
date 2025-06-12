<?php

use App\Libraries\Database;

class AddAiContentToArchetypesTable
{
    private $db;

    public function __construct()
    {
        \$this->db = new Database();
    }

    public function up()
    {
        \$sql = "ALTER TABLE archetypes ADD COLUMN ai_generated_content TEXT";
        try {
            \$this->db->query(\$sql);
            \$this->db->execute();
            echo "Column 'ai_generated_content' added to 'archetypes' table successfully.\n";
        } catch (\PDOException \$e) {
            echo "Error adding column 'ai_generated_content' to 'archetypes' table: " . \$e->getMessage() . "\n";
            // Optionally, check if the column already exists if error indicates that
            // For SQLite, a common error for existing column is 'duplicate column name'
            if (strpos(\$e->getMessage(), 'duplicate column name') !== false) {
                echo "Column 'ai_generated_content' likely already exists.\n";
            } else {
                throw \$e; // Re-throw if it's not a 'duplicate column' error
            }
        }
    }

    public function down()
    {
        // SQLite does not directly support DROP COLUMN in older versions easily.
        // The common workaround is to recreate the table without the column.
        // However, for simplicity in this context, and assuming a modern SQLite version or 
        // accepting that 'down' might be complex, we'll provide a simple DROP COLUMN.
        // This might fail on some SQLite setups if the version is too old.
        // A more robust 'down' would involve:
        // 1. CREATE TABLE new_archetypes AS SELECT id, name, description, slug, created_at, updated_at FROM archetypes;
        // 2. DROP TABLE archetypes;
        // 3. ALTER TABLE new_archetypes RENAME TO archetypes;
        \$sql = "ALTER TABLE archetypes DROP COLUMN ai_generated_content";
        try {
            \$this->db->query(\$sql);
            \$this->db->execute();
            echo "Column 'ai_generated_content' dropped from 'archetypes' table successfully.\n";
        } catch (\PDOException \$e) {
            echo "Error dropping column 'ai_generated_content' from 'archetypes' table: " . \$e->getMessage() . "\n";
            // If the column doesn't exist, it might throw an error. We can choose to ignore it.
            // SQLite error for non-existent column in DROP might be 'no such column'
             if (strpos(\$e->getMessage(), 'no such column') !== false || strpos(\$e->getMessage(), 'Cannot drop column') !== false) {
                echo "Column 'ai_generated_content' likely does not exist or cannot be dropped directly. Manual check may be needed for older SQLite versions.\n";
            } else {
                throw \$e; // Re-throw if it's a different error
            }
        }
    }
}

// You would typically run this migration via a migration runner script.
// For direct execution (if needed for testing, not recommended for production flow):
// require_once __DIR__ . '/../../../bootstrap.php'; // Adjust path to bootstrap
// \$migration = new AddAiContentToArchetypesTable();
// \$migration->up();
// To rollback:
// \$migration->down();