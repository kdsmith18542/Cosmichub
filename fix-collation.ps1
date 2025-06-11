# Path to database
$dbPath = "$PSScriptRoot\database\database.sqlite"
$backupPath = "$dbPath.backup.$(Get-Date -Format 'yyyyMMddHHmmss')"

# Create backup
Write-Host "Creating backup at: $backupPath" -ForegroundColor Cyan
Copy-Item -Path $dbPath -Destination $backupPath

# SQL commands to fix collation
$sqlCommands = @"
-- Enable foreign keys
PRAGMA foreign_keys = OFF;

-- Create a temporary database
ATTACH DATABASE ':memory:' AS temp_db;

-- Get all tables from the main database
CREATE TABLE temp_db.tables AS SELECT name, sql FROM main.sqlite_master 
WHERE type = 'table' AND name NOT LIKE 'sqlite_%';

-- Process each table
CREATE TEMP TABLE temp_commands AS
WITH RECURSIVE
  table_list AS (
    SELECT name, sql FROM temp_db.tables
  ),
  table_columns AS (
    SELECT 
      t.name as table_name,
      p.*
    FROM table_list t, pragma_table_info(t.name) p
  ),
  table_indexes AS (
    SELECT 
      t.name as table_name,
      i.*
    FROM table_list t, pragma_index_list(t.name) i
  ),
  index_columns AS (
    SELECT 
      i.table_name,
      i.name as index_name,
      ic.*
    FROM table_indexes i, pragma_index_info(i.name) ic
  ),
  -- Generate new table definitions with COLLATE NOCASE for text columns
  new_tables AS (
    SELECT 
      t.name as table_name,
      'CREATE TABLE ' || t.name || '_new (' || 
      GROUP_CONCAT(
        c.name || ' ' || c.type || 
        CASE 
          WHEN UPPER(c.type) LIKE '%TEXT%' OR UPPER(c.type) LIKE '%VARCHAR%' THEN ' COLLATE NOCASE' 
          ELSE '' 
        END ||
        CASE WHEN c.pk = 1 THEN ' PRIMARY KEY' ELSE '' END ||
        CASE WHEN c.notnull = 1 THEN ' NOT NULL' ELSE '' END ||
        CASE WHEN c.dflt_value IS NOT NULL THEN ' DEFAULT ' || c.dflt_value ELSE '' END,
        ', '
      ) || 
      CASE 
        WHEN t.sql LIKE '%PRIMARY KEY%' THEN ', ' || 
          SUBSTR(t.sql, INSTR(t.sql, 'PRIMARY KEY'))
        ELSE ')'
      END as create_sql
    FROM table_list t
    JOIN table_columns c ON t.name = c.table_name
    GROUP BY t.name, t.sql
  ),
  -- Generate index creation statements
  new_indexes AS (
    SELECT 
      i.table_name,
      'CREATE ' || 
      CASE WHEN i.[unique] = 1 THEN 'UNIQUE ' ELSE '' END ||
      'INDEX ' || i.name || ' ON ' || i.table_name || 
      '(' || GROUP_CONCAT(
        c.name || 
        CASE WHEN c.desc = 1 THEN ' DESC' ELSE ' ASC' END,
        ', '
      ) || ')' as create_index_sql
    FROM table_indexes i
    JOIN index_columns c ON i.name = c.index_name
    GROUP BY i.table_name, i.name, i.[unique]
  )
SELECT 'BEGIN TRANSACTION;' as command FROM table_list
UNION ALL
-- Drop all foreign key constraints
SELECT 'PRAGMA foreign_keys = OFF;'
UNION ALL
-- Create new tables with proper collation
SELECT create_sql || ';' FROM new_tables
UNION ALL
-- Copy data to new tables
SELECT 'INSERT INTO ' || table_name || '_new SELECT * FROM ' || table_name || ';' 
FROM table_list
UNION ALL
-- Drop old tables
SELECT 'DROP TABLE ' || name || ';' FROM table_list
UNION ALL
-- Rename new tables
SELECT 'ALTER TABLE ' || name || '_new RENAME TO ' || name || ';' FROM table_list
UNION ALL
-- Recreate indexes
SELECT create_index_sql || ';' FROM new_indexes
UNION ALL
-- Re-enable foreign keys
SELECT 'PRAGMA foreign_keys = ON;'
UNION ALL
SELECT 'COMMIT;';

-- Execute the generated commands
.mode list
.separator "" ""
.output temp_commands.sql
SELECT command FROM temp_commands;
.output stdout

-- Execute the commands
.read temp_commands.sql

-- Clean up
DROP TABLE IF EXISTS temp_commands;
DETACH DATABASE temp_db;
"@

# Write SQL to a temporary file
$tempFile = [System.IO.Path]::GetTempFileName() + ".sql"
$sqlCommands | Out-File -FilePath $tempFile -Encoding UTF8

# Get path to SQLite3
$sqlitePath = "sqlite3"
if (-not (Get-Command $sqlitePath -ErrorAction SilentlyContinue)) {
    # Try to find sqlite3 in common locations
    $possiblePaths = @(
        "C:\Program Files\SQLite\sqlite3.exe",
        "C:\sqlite\sqlite3.exe",
        "$env:ProgramFiles\SQLite\sqlite3.exe"
    )
    
    foreach ($path in $possiblePaths) {
        if (Test-Path $path) {
            $sqlitePath = $path
            break
        }
    }
    
    if (-not (Test-Path $sqlitePath)) {
        Write-Host "SQLite3 not found. Please install SQLite and add it to your PATH." -ForegroundColor Red
        exit 1
    }
}

Write-Host "Executing SQL commands..." -ForegroundColor Cyan
& $sqlitePath $dbPath ".read $tempFile"

# Clean up
Remove-Item -Path $tempFile -Force

Write-Host "`n✅ Collation update complete!" -ForegroundColor Green
Write-Host "Backup created at: $backupPath" -ForegroundColor Cyan
Write-Host "Please verify your application is working correctly." -ForegroundColor Yellow
