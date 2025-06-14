<?php

namespace App\Core\Database;

use App\Core\Application;
use App\Core\Database\ConnectionInterface;

/**
 * Model class for database models
 */
abstract class Model
{
    /**
     * @var Application The application instance
     */
    protected static $app;
    
    /**
     * @var string The table name
     */
    protected $table;
    
    /**
     * @var string The primary key
     */
    protected $primaryKey = 'id';
    
    /**
     * @var bool Whether to use timestamps
     */
    protected $timestamps = true;
    
    /**
     * @var string The created at column
     */
    protected $createdAt = 'created_at';
    
    /**
     * @var string The updated at column
     */
    protected $updatedAt = 'updated_at';
    
    /**
     * @var array The model attributes
     */
    protected $attributes = [];
    
    /**
     * @var array The original attributes
     */
    protected $original = [];
    
    /**
     * @var array The fillable attributes
     */
    protected $fillable = [];
    
    /**
     * @var array The guarded attributes
     */
    protected $guarded = ['*'];
    
    /**
     * @var array The hidden attributes
     */
    protected $hidden = [];
    
    /**
     * @var array The casts
     */
    protected $casts = [];
    
    /**
     * @var bool Whether the model exists
     */
    protected $exists = false;
    
    /**
     * Create a new model instance
     * 
     * @param array $attributes The attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }
    
    /**
     * Set the application instance
     * 
     * @param Application $app The application instance
     * @return void
     */
    public static function setApplication(Application $app)
    {
        static::$app = $app;
    }
    
    /**
     * Get the application instance
     * 
     * @return Application
     */
    public static function getApplication()
    {
        return static::$app;
    }
    
    /**
     * Get the database connection
     * 
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return static::$app->make(DatabaseManager::class)->connection();
    }
    
    /**
     * Get the query builder
     * 
     * @return QueryBuilder
     */
    public function newQuery()
    {
        return static::$app->make(QueryBuilder::class)->table($this->getTable());
    }
    
    /**
     * Get the table name
     * 
     * @return string
     */
    public function getTable()
    {
        if ($this->table) {
            return $this->table;
        }
        
        $class = get_class($this);
        $parts = explode('\\', $class);
        $name = end($parts);
        
        return strtolower($name) . 's';
    }
    
    /**
     * Get the primary key
     * 
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
    
    /**
     * Get the primary key value
     * 
     * @return mixed
     */
    public function getPrimaryKeyValue()
    {
        return $this->getAttribute($this->getPrimaryKey());
    }
    
    /**
     * Fill the model with attributes
     * 
     * @param array $attributes The attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        
        return $this;
    }
    
    /**
     * Check if an attribute is fillable
     * 
     * @param string $key The attribute key
     * @return bool
     */
    public function isFillable($key)
    {
        if (in_array($key, $this->fillable)) {
            return true;
        }
        
        if (in_array('*', $this->guarded)) {
            return false;
        }
        
        return !in_array($key, $this->guarded);
    }
    
    /**
     * Get an attribute
     * 
     * @param string $key The attribute key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }
        
        return null;
    }
    
    /**
     * Set an attribute
     * 
     * @param string $key The attribute key
     * @param mixed $value The attribute value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        
        return $this;
    }
    
    /**
     * Cast an attribute
     * 
     * @param string $key The attribute key
     * @param mixed $value The attribute value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }
        
        if (!isset($this->casts[$key])) {
            return $value;
        }
        
        $type = $this->casts[$key];
        
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
                return json_decode($value, true);
            case 'object':
                return json_decode($value);
            case 'date':
                return new \DateTime($value);
            default:
                return $value;
        }
    }
    
    /**
     * Get all attributes
     * 
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    /**
     * Set the model as existing
     * 
     * @param bool $exists Whether the model exists
     * @return $this
     */
    public function setExists($exists = true)
    {
        $this->exists = $exists;
        
        return $this;
    }
    
    /**
     * Check if the model exists
     * 
     * @return bool
     */
    public function exists()
    {
        return $this->exists;
    }
    
    /**
     * Save the model
     * 
     * @return bool
     */
    public function save()
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            
            if (!$this->exists) {
                $this->setAttribute($this->createdAt, $now);
            }
            
            $this->setAttribute($this->updatedAt, $now);
        }
        
        if ($this->exists) {
            $saved = $this->update();
        } else {
            $saved = $this->insert();
        }
        
        if ($saved) {
            $this->original = $this->attributes;
        }
        
        return $saved;
    }
    
    /**
     * Insert the model
     * 
     * @return bool
     */
    protected function insert()
    {
        $query = $this->newQuery();
        
        $id = $query->insertGetId($this->attributes);
        
        $this->setAttribute($this->primaryKey, $id);
        
        $this->exists = true;
        
        return true;
    }
    
    /**
     * Update the model
     * 
     * @return bool
     */
    protected function update()
    {
        $query = $this->newQuery();
        
        $query->where($this->primaryKey, $this->getPrimaryKeyValue());
        
        return $query->update($this->attributes) > 0;
    }
    
    /**
     * Delete the model
     * 
     * @return bool
     */
    public function delete()
    {
        if (!$this->exists) {
            return false;
        }
        
        $query = $this->newQuery();
        
        $query->where($this->primaryKey, $this->getPrimaryKeyValue());
        
        $deleted = $query->delete() > 0;
        
        if ($deleted) {
            $this->exists = false;
        }
        
        return $deleted;
    }
    
    /**
     * Convert the model to an array
     * 
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->getAttributes();
        
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }
        
        return $attributes;
    }
    
    /**
     * Convert the model to JSON
     * 
     * @param int $options The JSON options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * Find a model by its primary key
     * 
     * @param mixed $id The primary key
     * @return static|null
     */
    public static function find($id)
    {
        $instance = new static;
        
        $query = $instance->newQuery();
        
        $query->where($instance->getPrimaryKey(), $id);
        
        $result = $query->first();
        
        if (!$result) {
            return null;
        }
        
        return static::newFromArray($result);
    }
    
    /**
     * Find a model by its primary key or throw an exception
     * 
     * @param mixed $id The primary key
     * @return static
     * @throws \RuntimeException
     */
    public static function findOrFail($id)
    {
        $model = static::find($id);
        
        if (!$model) {
            throw new \RuntimeException('Model not found');
        }
        
        return $model;
    }
    
    /**
     * Get all models
     * 
     * @return array
     */
    public static function all()
    {
        $instance = new static;
        
        $query = $instance->newQuery();
        
        $results = $query->get();
        
        return static::hydrate($results);
    }
    
    /**
     * Create a new model from an array
     * 
     * @param array $attributes The attributes
     * @return static
     */
    public static function newFromArray(array $attributes)
    {
        $instance = new static;
        
        $instance->attributes = $attributes;
        $instance->original = $attributes;
        $instance->exists = true;
        
        return $instance;
    }
    
    /**
     * Hydrate models from an array of results
     * 
     * @param array $results The results
     * @return array
     */
    public static function hydrate(array $results)
    {
        $models = [];
        
        foreach ($results as $result) {
            $models[] = static::newFromArray($result);
        }
        
        return $models;
    }
    
    /**
     * Create a new model
     * 
     * @param array $attributes The attributes
     * @return static
     */
    public static function create(array $attributes)
    {
        $instance = new static($attributes);
        
        $instance->save();
        
        return $instance;
    }
    
    /**
     * Begin a query
     * 
     * @return QueryBuilder
     */
    public static function query()
    {
        $instance = new static;
        
        return $instance->newQuery();
    }
    
    /**
     * Get an attribute
     * 
     * @param string $key The attribute key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Set an attribute
     * 
     * @param string $key The attribute key
     * @param mixed $value The attribute value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Check if an attribute exists
     * 
     * @param string $key The attribute key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }
    
    /**
     * Unset an attribute
     * 
     * @param string $key The attribute key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
    
    /**
     * Convert the model to a string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
    
    /**
     * Handle dynamic static method calls
     * 
     * @param string $method The method
     * @param array $parameters The parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
    
    /**
     * Handle dynamic method calls
     * 
     * @param string $method The method
     * @param array $parameters The parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $query = $this->newQuery();
        
        return $query->$method(...$parameters);
    }
}