<?php
namespace App\Models;

use App\Core\Database\QueryBuilder;

/**
 * Base Model class
 */
abstract class Model
{
    /** @var string Table name */
    protected static $table = '';
    
    /** @var string Primary key */
    protected static $primaryKey = 'id';

    /** @var QueryBuilder Query builder instance */
    protected $queryBuilder;

    public function __construct()
    {
        if (empty(static::$table)) {
            throw new \Exception("Table name not defined for model " . get_called_class());
        }
        $this->queryBuilder = new QueryBuilder();
        $this->queryBuilder->table(static::$table);
    }
    
    /**
     * Find a record by ID
     */
    public static function find($id)
    {
        $instance = new static();
        return $instance->queryBuilder->where(static::$primaryKey, '=', $id)->first();
    }
    
    /**
     * Get all records
     */
    public static function all()
    {
        $instance = new static();
        return $instance->queryBuilder->get();
    }
    
    /**
     * Create a new record
     */
    public static function create(array $data)
    {
        $instance = new static();
        return $instance->queryBuilder->insert($data);
    }
    
    /**
     * Update a record
     */
    public static function update($id, array $data)
    {
        $instance = new static();
        return $instance->queryBuilder->where(static::$primaryKey, '=', $id)->update($data);
    }
    
    /**
     * Delete a record
     */
    public static function delete($id)
    {
        $instance = new static();
        return $instance->queryBuilder->where(static::$primaryKey, '=', $id)->delete();
    }
    
    /**
     * Execute a raw query
     */
    public static function query($sql, $params = [])
    {
        $instance = new static();
        return $instance->queryBuilder->raw($sql, $params);
    }
}
