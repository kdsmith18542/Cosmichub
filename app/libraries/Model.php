<?php
namespace App\Libraries;

use PDO;
use PDOException;
use App\Libraries\QueryBuilder;
use App\Libraries\Database;
use function App\Helpers\snake_case;
use function App\Helpers\class_basename;

/**
 * Base Model Class
 * 
 * Provides common database operations for all models.
 */
abstract class Model {
    /**
     * @var string The database table name
     */
    protected static $table = '';
    
    /**
     * @var string The primary key for the table
     */
    protected static $primaryKey = 'id';
    
    /**
     * @var array The model's attributes
     */
    protected $attributes = [];
    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [];
    
    /**
     * @var bool Indicates if the model exists in the database
     */
    protected $exists = false;
    
    /**
     * Create a new model instance
     * 
     * @param array $attributes
     */
    public function __construct(array $attributes = []) {
        $this->fill($attributes);
    }
    
    /**
     * Fill the model with an array of attributes
     * 
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes) {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }
    
    /**
     * Set a given attribute on the model
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($key, $value) {
        // Only set fillable attributes
        if (in_array($key, $this->fillable) || $key === $this->getKeyName()) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }
    
    /**
     * Get an attribute from the model
     * 
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key) {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
        
        // Check for camelCase accessor method
        $method = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
        
        return null;
    }
    
    /**
     * Get all of the current attributes on the model
     * 
     * @return array
     */
    public function getAttributes() {
        return $this->attributes;
    }
    
    /**
     * Get the primary key for the model
     * 
     * @return string
     */
    public function getKeyName() {
        return static::$primaryKey;
    }
    
    /**
     * Get the value of the model's primary key
     * 
     * @return mixed
     */
    public function getKey() {
        return $this->getAttribute($this->getKeyName());
    }
    
    /**
     * Save the model to the database
     * 
     * @return bool
     */
    public function save() {
        if ($this->exists) {
            return $this->performUpdate();
        }
        
        return $this->performInsert();
    }
    
    /**
     * Perform a model insert operation
     * 
     * @return bool
     */
    protected function performInsert() {
        $attributes = $this->getAttributes();
        
        // Remove the primary key if it's auto-incrementing
        if ($this->getIncrementing() && !isset($attributes[$this->getKeyName()])) {
            unset($attributes[$this->getKeyName()]);
        }
        
        // Set timestamps if enabled
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }
        
        // Insert the record
        $id = Database::insert(static::$table, $attributes);
        
        if ($id) {
            $this->setAttribute($this->getKeyName(), $id);
            $this->exists = true;
            return true;
        }
        
        return false;
    }
    
    /**
     * Perform a model update operation
     * 
     * @return bool
     */
    protected function performUpdate() {
        $attributes = $this->getAttributes();
        $keyName = $this->getKeyName();
        
        // Remove the primary key from the update
        unset($attributes[$keyName]);
        
        // Update timestamps if enabled
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }
        
        // Update the record
        $updated = Database::update(
            static::$table,
            $attributes,
            "{$keyName} = :id",
            ['id' => $this->getKey()]
        );
        
        return $updated !== false;
    }
    
    /**
     * Delete the model from the database
     * 
     * @return bool
     */
    public function delete() {
        $deleted = $this->db->delete(
            $this->table,
            ['id' => $this->getKey()]
        );
        
        return $deleted !== false;
    }

    /**
     * Define a one-to-one or one-to-many inverse relationship.
     *
     * @param string $related The name of the related model.
     * @param string $foreignKey The foreign key of the relationship.
     * @param string $ownerKey The key on the parent model.
     * @return Model|null
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $ownerKey = $ownerKey ?: (new $related)->getKeyName();

        $relatedModel = new $related();
        return $relatedModel->find($this->{$foreignKey});
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return snake_case(class_basename($this)) . '_id';
    }
    
    /**
     * Determine if the model uses timestamps
     * 
     * @return bool
     */
    public function usesTimestamps() {
        return in_array('created_at', $this->fillable) && in_array('updated_at', $this->fillable);
    }
    
    /**
     * Update the creation and update timestamps
     * 
     * @return void
     */
    protected function updateTimestamps() {
        $time = date('Y-m-d H:i:s');
        
        if (!$this->exists && $this->usesTimestamps()) {
            $this->created_at = $time;
        }
        
        if ($this->usesTimestamps()) {
            $this->updated_at = $time;
        }
    }
    
    /**
     * Determine if the model's primary key is auto-incrementing
     * 
     * @return bool
     */
    public function getIncrementing() {
        return true;
    }
    
    /**
     * Get a new query builder for the model's table
     * 
     * @return \Database\Query\Builder
     */
    public static function query() {
        return new QueryBuilder(static::$table, get_called_class());
    }
    
    /**
     * Find a model by its primary key
     * 
     * @param mixed $id
     * @return static|null
     */
    public static function find($id) {
        $instance = new static();
        $result = Database::first(
            "SELECT * FROM " . static::$table . " WHERE " . $instance->getKeyName() . " = :id",
            ['id' => $id]
        );
        
        if ($result) {
            $model = new static((array) $result);
            $model->exists = true;
            return $model;
        }
        
        return null;
    }
    
    /**
     * Find a model by its primary key or throw an exception
     * 
     * @param mixed $id
     * @return static
     * @throws \RuntimeException
     */
    public static function findOrFail($id) {
        $model = static::find($id);
        
        if (!$model) {
            throw new \RuntimeException("No query results for model [" . get_called_class() . "]");
        }
        
        return $model;
    }
    
    /**
     * Get all of the models from the database
     * 
     * @return array
     */
    public static function all() {
        $instance = new static();
        $results = Database::all("SELECT * FROM " . static::$table);
        
        return array_map(function($item) use ($instance) {
            $model = new static((array) $item);
            $model->exists = true;
            return $model;
        }, $results);
    }
    
    /**
     * Create a new model instance and save it to the database
     * 
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes) {
        $model = new static($attributes);
        $model->save();
        return $model;
    }
    
    /**
     * Handle dynamic method calls into the model
     * 
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters) {
        return $this->forwardCallTo($this->newQuery(), $method, $parameters);
    }
    
    /**
     * Handle dynamic static method calls into the model
     * 
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters) {
        return (new static)->$method(...$parameters);
    }
    
    /**
     * Forward a method call to the given object
     * 
     * @param mixed $object
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    protected function forwardCallTo($object, $method, $parameters) {
        return $object->$method(...$parameters);
    }
    
    /**
     * Get a new query builder for the model's table
     * 
     * @return \Database\Query\Builder
     */
    protected function newQuery() {
        return new QueryBuilder(static::$table, get_called_class());
    }
    
    /**
     * Dynamically retrieve attributes on the model
     * 
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        return $this->getAttribute($key);
    }
    
    /**
     * Dynamically set attributes on the model
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value) {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Determine if an attribute exists on the model
     * 
     * @param string $key
     * @return bool
     */
    public function __isset($key) {
        return isset($this->attributes[$key]);
    }
    
    /**
     * Unset an attribute on the model
     * 
     * @param string $key
     * @return void
     */
    public function __unset($key) {
        unset($this->attributes[$key]);
    }
    
    /**
     * Convert the model to its string representation
     * 
     * @return string
     */
    public function __toString() {
        return json_encode($this->toArray());
    }
    
    /**
     * Convert the model instance to an array
     * 
     * @return array
     */
    public function toArray() {
        return $this->attributes;
    }
}
