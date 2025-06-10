<?php
namespace App\Models;

use PDO;
use PDOException;

/**
 * Base Model class
 */
class Model
{
    /** @var PDO Database connection */
    protected static $db;
    
    /** @var string Table name */
    protected static $table = '';
    
    /** @var string Primary key */
    protected static $primaryKey = 'id';
    
    /**
     * Get database connection
     */
    protected static function getDb()
    {
        if (!self::$db) {
            $config = require __DIR__ . '/../../app/config/database.php';
            
            try {
                if ($config['driver'] === 'sqlite') {
                    $dsn = "sqlite:{$config['database']}";
                    self::$db = new PDO($dsn);
                } else {
                    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
                    self::$db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                }
                
                self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
                
            } catch (PDOException $e) {
                throw new \Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        return self::$db;
    }
    
    /**
     * Find a record by ID
     */
    public static function find($id)
    {
        $db = self::getDb();
        $stmt = $db->prepare("SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get all records
     */
    public static function all()
    {
        $db = self::getDb();
        $stmt = $db->query("SELECT * FROM " . static::$table);
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new record
     */
    public static function create(array $data)
    {
        $db = self::getDb();
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO " . static::$table . " ($columns) VALUES ($placeholders)";
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute($data)) {
            return $db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update a record
     */
    public static function update($id, array $data)
    {
        $db = self::getDb();
        $set = [];
        
        foreach (array_keys($data) as $key) {
            $set[] = "$key = :$key";
        }
        
        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $set) . " WHERE " . static::$primaryKey . " = :id";
        $stmt = $db->prepare($sql);
        $data['id'] = $id;
        
        return $stmt->execute($data);
    }
    
    /**
     * Delete a record
     */
    public static function delete($id)
    {
        $db = self::getDb();
        $stmt = $db->prepare("DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Execute a raw query
     */
    public static function query($sql, $params = [])
    {
        $db = self::getDb();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
