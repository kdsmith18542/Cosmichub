-- Backup the database
.backup 'database.sqlite.backup'

-- Set PRAGMAs for better performance and compatibility
PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;
PRAGMA encoding = 'UTF-8';

-- Get list of all tables
CREATE TEMP TABLE IF NOT EXISTS tables_to_process AS
SELECT name FROM sqlite_master 
WHERE type = 'table' 
AND name NOT LIKE 'sqlite_%';

-- Process each table
CREATE TEMP TRIGGER IF NOT EXISTS process_tables
AFTER INSERT ON tables_to_process
BEGIN
    -- Create new table with proper collation
    EXECUTE IMMEDIATE 'CREATE TABLE ' || NEW.name || '_new AS SELECT * FROM ' || NEW.name || ' LIMIT 0';
    
    -- Get the CREATE TABLE statement and modify it to add COLLATE NOCASE to text columns
    WITH RECURSIVE
        sql_parts(part) AS (
            SELECT 'CREATE TABLE ' || NEW.name || '_new (' || 
                   GROUP_CONCAT(
                       CASE 
                           WHEN type LIKE '%TEXT%' OR type LIKE '%VARCHAR%' THEN 
                               name || ' ' || type || ' COLLATE NOCASE'
                           ELSE 
                               name || ' ' || type
                       END,
                       ', '
                   ) || 
                   CASE 
                       WHEN sql LIKE '%PRIMARY KEY%' THEN ', ' || 
                           substr(sql, instr(sql, 'PRIMARY KEY'))
                       ELSE ''
                   END || ')'
            FROM pragma_table_info(NEW.name)
        )
    SELECT part INTO @create_sql FROM sql_parts;
    
    -- Drop the temporary table and create the new one with proper schema
    EXECUTE IMMEDIATE 'DROP TABLE ' || NEW.name || '_new';
    EXECUTE IMMEDIATE @create_sql;
    
    -- Copy data to new table
    EXECUTE IMMEDIATE 'INSERT INTO ' || NEW.name || '_new SELECT * FROM ' || NEW.name;
    
    -- Drop old table and rename new one
    EXECUTE IMMEDIATE 'DROP TABLE ' || NEW.name;
    EXECUTE IMMEDIATE 'ALTER TABLE ' || NEW.name || '_new RENAME TO ' || NEW.name;
    
    -- Recreate indexes
    FOR index_row IN (SELECT sql FROM sqlite_master WHERE type = 'index' AND tbl_name = NEW.name AND sql IS NOT NULL)
    LOOP
        EXECUTE IMMEDIATE index_row.sql;
    END LOOP;
END;

-- Process all tables
INSERT INTO tables_to_process SELECT name FROM sqlite_master 
WHERE type = 'table' 
AND name NOT LIKE 'sqlite_%';

-- Clean up
DROP TRIGGER IF EXISTS process_tables;
DROP TABLE IF EXISTS tables_to_process;

-- Verify changes
SELECT name, sql FROM sqlite_master 
WHERE type = 'table' 
AND name NOT LIKE 'sqlite_%'
AND sql LIKE '%COLLATE%';

VACUUM;
