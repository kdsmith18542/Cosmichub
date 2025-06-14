<?php
namespace App\Libraries;

use PDO;
use PDOException;

/**
 * Database Connection Class
 * 
 * Handles the database connection and provides common database operations.
 */
class Database {
    /**
     * @var PDO Database connection
     */
    private static $instance = null;
    
    /**
     * @var PDOStatement The last prepared statement
     */
    private $stmt;
    
    /**
     * Get a database connection instance (singleton)
     * 
     * @return PDO Database connection
     * @throws PDOException If connection fails
     */
    public static function getInstance() {
        if (self::$instance === null) {
            // Load database configuration
            $config = require __DIR__ . '/../config/database.php';
            
            try {
                // Create PDO instance
                self::$instance = new PDO(
                    "sqlite:{$config['database']}",
                    null, // No username for SQLite
                    null, // No password for SQLite
                    $config['options']
                );
                
                // Enable foreign key constraints
                self::$instance->exec('PRAGMA foreign_keys = ON');
                
            } catch (PDOException $e) {
                // Log error and rethrow
                $logger = container()->get(\Psr\Log\LoggerInterface::class);
                $logger->error('Database connection failed: ' . $e->getMessage());
                throw new PDOException('Could not connect to the database. Please try again later.');
            }
        }
        
        return self::$instance;
    }
    
    /**
     * Prepare a SQL statement for execution
     * 
     * @param string $sql SQL query
     * @return $this
     */
    public function query($sql) {
        $this->stmt = self::getInstance()->prepare($sql);
        return $this;
    }
    
    /**
     * Bind values to prepared statement
     * 
     * @param mixed $param Parameter identifier
     * @param mixed $value Value to bind
     * @param int $type PDO parameter type
     * @return $this
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
    
    /**
     * Execute the prepared statement
     * 
     * @return bool True on success, false on failure
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $logger = container()->get(\Psr\Log\LoggerInterface::class);
            $logger->error('Database error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get result set as array of objects
     * 
     * @return array Array of objects
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Get single record as object
     * 
     * @return object|false Object on success, false on failure
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Get the row count
     * 
     * @return int Number of rows
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string Last inserted ID
     */
    public function lastInsertId() {
        return self::getInstance()->lastInsertId();
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function beginTransaction() {
        return self::getInstance()->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function commit() {
        return self::getInstance()->commit();
    }
    
    /**
     * Roll back a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function rollBack() {
        return self::getInstance()->rollBack();
    }
    
    /**
     * Execute a raw SQL query
     * 
     * @param string $sql SQL query
     * @return PDOStatement|false PDO statement object on success, false on failure
     */
    public static function raw($sql) {
        return self::getInstance()->query($sql);
    }
    
    /**
     * Execute a raw SQL query and return the first row
     * 
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return object|false Object on success, false on failure
     */
    public static function first($sql, $params = []) {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Execute a raw SQL query and return all rows
     * 
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return array Array of objects
     */
    public static function all($sql, $params = []) {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Insert a record into a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return string|false Last insert ID on success, false on failure
     */
    public static function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($data);
            return self::getInstance()->lastInsertId();
        } catch (PDOException $e) {
            $logger = container()->get(\Psr\Log\LoggerInterface::class);
            $logger->error('Insert failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a record in a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause (without WHERE keyword)
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public static function update($table, $data, $where, $params = []) {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = :{$column}";
        }
        $set = implode(', ', $set);
        
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute(array_merge($data, $params));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $logger = container()->get(\Psr\Log\LoggerInterface::class);
            $logger->error('Update failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete records from a table
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (without WHERE keyword)
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public static function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $logger = container()->get(\Psr\Log\LoggerInterface::class);
            $logger->error('Delete failed: ' . $e->getMessage());
            return false;
        }
    }
}
