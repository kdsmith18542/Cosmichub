<?php

namespace App\Core\Database;

use App\Core\Exceptions\DatabaseException;
use App\Core\Traits\Loggable;
use JsonSerializable;
use ArrayAccess;
use Iterator;
use Countable;

/**
 * Enhanced Model Class
 * 
 * Extends the base Model with advanced ORM functionality including
 * relationships, validation, events, caching, and enhanced query capabilities
 */
abstract class EnhancedModel extends Model implements JsonSerializable, ArrayAccess, Iterator, Countable
{
    use Loggable;
    
    /**
     * @var array The relationships that should be touched on save
     */
    protected $touches = [];
    
    /**
     * @var array The loaded relationships for the model
     */
    protected $relations = [];
    
    /**
     * @var array The accessors to append to the model's array form
     */
    protected $appends = [];
    
    /**
     * @var array The attributes that should be visible in serialization
     */
    protected $visible = [];
    
    /**
     * @var array The attributes that should be mutated to dates
     */
    protected $dates = ['created_at', 'updated_at'];
    
    /**
     * @var array The changed model attributes
     */
    protected $changes = [];
    
    /**
     * @var bool Indicates if the model was inserted during the current request lifecycle
     */
    public $wasRecentlyCreated = false;
    
    /**
     * @var bool Indicates if the model is currently force deleting
     */
    protected $forceDeleting = false;
    
    /**
     * @var array The event map for the model
     */
    protected $dispatchesEvents = [];
    
    /**
     * @var array User-defined validation rules
     */
    protected $rules = [];
    
    /**
     * @var array Custom validation messages
     */
    protected $messages = [];
    
    /**
     * @var array The cache of the mutated attributes for each class
     */
    protected static $mutatorCache = [];
    
    /**
     * @var array The booted models
     */
    protected static $booted = [];
    
    /**
     * @var bool Indicates if all mass assignment is enabled
     */
    protected static $unguarded = false;
    
    /**
     * @var bool Indicates whether attributes are snake cased on arrays
     */
    public static $snakeAttributes = true;
    
    /**
     * @var int Iterator position
     */
    private $position = 0;
    
    /**
     * @var array The primitive cast types
     */
    protected static $primitiveCastTypes = [
        'array', 'bool', 'boolean', 'collection', 'custom_datetime', 'date', 'datetime',
        'decimal', 'double', 'float', 'int', 'integer', 'json', 'object',
        'real', 'string', 'timestamp',
    ];
    
    /**
     * Create a new enhanced model instance
     * 
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->bootIfNotBooted();
        $this->initializeTraits();
        $this->syncOriginal();
    }
    
    /**
     * Boot the model if it hasn't been booted
     */
    protected function bootIfNotBooted()
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;
            static::boot();
        }
    }
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        // Override in child classes for custom boot logic
    }
    
    /**
     * Initialize the model's traits
     */
    protected function initializeTraits()
    {
        foreach (class_uses_recursive($this) as $trait) {
            $method = 'initialize' . class_basename($trait);
            
            if (method_exists($this, $method)) {
                $this->{$method}();
            }
        }
    }
    
    /**
     * Get an enhanced query builder instance
     * 
     * @return EnhancedQueryBuilder
     */
    public function newQuery()
    {
        $builder = static::getApplication()->make(EnhancedQueryBuilder::class);
        return $builder->setModel($this)->table($this->getTable());
    }
    
    /**
     * Create a new query builder without any scopes
     * 
     * @return EnhancedQueryBuilder
     */
    public static function newQueryWithoutScopes()
    {
        return (new static)->newQuery();
    }
    
    /**
     * Find a model by its primary key
     * 
     * @param mixed $id
     * @param array $columns
     * @return static|null
     */
    public static function find($id, $columns = ['*'])
    {
        return static::newQuery()->find($id, $columns);
    }
    
    /**
     * Find a model by its primary key or throw an exception
     * 
     * @param mixed $id
     * @param array $columns
     * @return static
     * 
     * @throws DatabaseException
     */
    public static function findOrFail($id, $columns = ['*'])
    {
        $result = static::find($id, $columns);
        
        if (is_null($result)) {
            throw DatabaseException::recordNotFound(static::class, $id);
        }
        
        return $result;
    }
    
    /**
     * Find multiple models by their primary keys
     * 
     * @param array $ids
     * @param array $columns
     * @return Collection
     */
    public static function findMany($ids, $columns = ['*'])
    {
        return static::newQuery()->findMany($ids, $columns);
    }
    
    /**
     * Get all of the models from the database
     * 
     * @param array $columns
     * @return Collection
     */
    public static function all($columns = ['*'])
    {
        return static::newQuery()->get($columns);
    }
    
    /**
     * Create a new model instance
     * 
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes = [])
    {
        $model = new static($attributes);
        $model->save();
        
        return $model;
    }
    
    /**
     * Save the model to the database
     * 
     * @param array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $this->mergeAttributesFromClassCasts();
        
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }
        
        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->exists) {
            $saved = $this->isDirty() ?
                        $this->performUpdate() : true;
        } else {
            $saved = $this->performInsert();
            
            if (!$this->getConnectionName() &&
                $connection = static::resolveConnection()) {
                $this->setConnection($connection->getName());
            }
        }
        
        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        if ($saved) {
            $this->finishSave($options);
        }
        
        return $saved;
    }
    
    /**
     * Perform a model insert operation
     * 
     * @return bool
     */
    protected function performInsert()
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }
        
        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }
        
        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final insert ID for this
        // table. Not all tables have to be incrementing though.
        $attributes = $this->getAttributesForInsert();
        
        if ($this->getIncrementing()) {
            $this->insertAndSetId($attributes);
        }
        
        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            if (empty($attributes)) {
                return true;
            }
            
            $this->newQuery()->insert($attributes);
        }
        
        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;
        
        $this->wasRecentlyCreated = true;
        
        $this->fireModelEvent('created', false);
        
        return true;
    }
    
    /**
     * Perform a model update operation
     * 
     * @return bool
     */
    protected function performUpdate()
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }
        
        // First we need to create a fresh query instance and touch the updated_at
        // timestamp on the model which are maintained by us for developer convenience.
        // Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }
        
        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        $dirty = $this->getDirty();
        
        if (count($dirty) > 0) {
            $this->setKeysForSaveQuery($this->newQuery())->update($dirty);
            
            $this->syncChanges();
            
            $this->fireModelEvent('updated', false);
        }
        
        return true;
    }
    
    /**
     * Set the keys for a save update query
     * 
     * @param EnhancedQueryBuilder $query
     * @return EnhancedQueryBuilder
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());
        
        return $query;
    }
    
    /**
     * Get the primary key value for a save query
     * 
     * @return mixed
     */
    protected function getKeyForSaveQuery()
    {
        return $this->original[$this->getKeyName()] ?? $this->getKey();
    }
    
    /**
     * Insert the given attributes and set the ID on the model
     * 
     * @param array $attributes
     * @return void
     */
    protected function insertAndSetId(array $attributes)
    {
        $id = $this->newQuery()->insertGetId($attributes, $keyName = $this->getKeyName());
        
        $this->setAttribute($keyName, $id);
    }
    
    /**
     * Get the attributes that should be converted to dates
     * 
     * @return array
     */
    public function getDates()
    {
        $defaults = [static::CREATED_AT, static::UPDATED_AT];
        
        return $this->usesTimestamps() ? array_unique(array_merge($this->dates, $defaults)) : $this->dates;
    }
    
    /**
     * Update the creation and update timestamps
     * 
     * @return void
     */
    protected function updateTimestamps()
    {
        $time = $this->freshTimestamp();
        
        if (!is_null(static::UPDATED_AT) && !$this->isDirty(static::UPDATED_AT)) {
            $this->setUpdatedAt($time);
        }
        
        if (!$this->exists && !is_null(static::CREATED_AT) &&
            !$this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($time);
        }
    }
    
    /**
     * Set the value of the "created at" attribute
     * 
     * @param mixed $value
     * @return $this
     */
    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = $value;
        
        return $this;
    }
    
    /**
     * Set the value of the "updated at" attribute
     * 
     * @param mixed $value
     * @return $this
     */
    public function setUpdatedAt($value)
    {
        $this->{static::UPDATED_AT} = $value;
        
        return $this;
    }
    
    /**
     * Get a fresh timestamp for the model
     * 
     * @return \DateTime
     */
    public function freshTimestamp()
    {
        return new \DateTime();
    }
    
    /**
     * Get the attributes for an insert operation
     * 
     * @return array
     */
    protected function getAttributesForInsert()
    {
        return $this->getAttributes();
    }
    
    /**
     * Finish processing on a successful save operation
     * 
     * @param array $options
     * @return void
     */
    protected function finishSave(array $options)
    {
        $this->fireModelEvent('saved', false);
        
        if ($this->isDirty() && ($options['touch'] ?? true)) {
            $this->touchOwners();
        }
        
        $this->syncOriginal();
    }
    
    /**
     * Touch the owning relations of the model
     * 
     * @return void
     */
    protected function touchOwners()
    {
        foreach ($this->touches as $relation) {
            $this->$relation()->touch();
            
            if ($this->$relation instanceof self) {
                $this->$relation->fireModelEvent('saved', false);
            }
        }
    }
    
    /**
     * Delete the model from the database
     * 
     * @return bool|null
     * 
     * @throws DatabaseException
     */
    public function delete()
    {
        if (is_null($this->getKeyName())) {
            throw new DatabaseException('No primary key defined on model.');
        }
        
        // If the model doesn't exist, there is nothing to delete so we'll just return
        // immediately and not do anything else. Otherwise, we will continue with a
        // deletion process on the model, firing the proper events, and so forth.
        if (!$this->exists) {
            return;
        }
        
        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }
        
        // Here, we'll touch the owning models, verifying these timestamps get updated
        // for the models. This will allow any caching to get broken on the parents
        // by the timestamp. Then we will go ahead and delete the model instance.
        $this->touchOwners();
        
        $this->performDeleteOnModel();
        
        // Once the model has been deleted, we will fire off the deleted event so that
        // the developers may hook into post-delete operations. We will then return
        // a boolean true as the delete is presumably successful on the database.
        $this->fireModelEvent('deleted', false);
        
        return true;
    }
    
    /**
     * Perform the actual delete query on this model instance
     * 
     * @return void
     */
    protected function performDeleteOnModel()
    {
        $this->setKeysForSaveQuery($this->newQuery())->delete();
        
        $this->exists = false;
    }
    
    /**
     * Fire the given event for the model
     * 
     * @param string $event
     * @param bool $halt
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        if (!isset(static::$dispatcher)) {
            return true;
        }
        
        // First, we will get the proper method to call on the event dispatcher, and then we
        // will attempt to fire a custom, object based event for the given event. If that
        // returns a result we can return that result, or we'll call the string events.
        $method = $halt ? 'until' : 'dispatch';
        
        $result = $this->filterModelEventResults(
            $this->fireCustomModelEvent($event, $method)
        );
        
        if ($result === false) {
            return false;
        }
        
        return !empty($result) ? $result : static::$dispatcher->{$method}(
            "eloquent.{$event}: ".static::class, $this
        );
    }
    
    /**
     * Fire a custom model event for the given event
     * 
     * @param string $event
     * @param string $method
     * @return mixed|null
     */
    protected function fireCustomModelEvent($event, $method)
    {
        if (!isset($this->dispatchesEvents[$event])) {
            return;
        }
        
        $result = static::$dispatcher->$method(new $this->dispatchesEvents[$event]($this));
        
        if (!is_null($result)) {
            return $result;
        }
    }
    
    /**
     * Filter the model event results
     * 
     * @param mixed $result
     * @return mixed
     */
    protected function filterModelEventResults($result)
    {
        if (is_array($result)) {
            $result = array_filter($result, function ($response) {
                return !is_null($response);
            });
        }
        
        return $result;
    }
    
    /**
     * @var mixed The event dispatcher instance
     */
    protected static $dispatcher;
    
    /**
     * Get the event dispatcher instance
     * 
     * @return mixed
     */
    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }
    
    /**
     * Set the event dispatcher instance
     * 
     * @param mixed $dispatcher
     * @return void
     */
    public static function setEventDispatcher($dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }
    
    /**
     * Unset the event dispatcher for models
     * 
     * @return void
     */
    public static function unsetEventDispatcher()
    {
        static::$dispatcher = null;
    }
    
    /**
     * Sync the original attributes with the current
     * 
     * @return $this
     */
    public function syncOriginal()
    {
        $this->original = $this->getAttributes();
        
        return $this;
    }
    
    /**
     * Sync the changed attributes
     * 
     * @return $this
     */
    public function syncChanges()
    {
        $this->changes = $this->getDirty();
        
        return $this;
    }
    
    /**
     * Determine if the model or any of the given attribute(s) have been modified
     * 
     * @param array|string|null $attributes
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        return $this->hasChanges(
            $this->getDirty(), is_array($attributes) ? $attributes : func_get_args()
        );
    }
    
    /**
     * Determine if the model and all the given attribute(s) have remained the same
     * 
     * @param array|string|null $attributes
     * @return bool
     */
    public function isClean($attributes = null)
    {
        return !$this->isDirty(...func_get_args());
    }
    
    /**
     * Determine if the model or any of the given attribute(s) have been modified
     * 
     * @param array|string|null $attributes
     * @return bool
     */
    public function wasChanged($attributes = null)
    {
        return $this->hasChanges(
            $this->getChanges(), is_array($attributes) ? $attributes : func_get_args()
        );
    }
    
    /**
     * Determine if any of the given attributes were changed
     * 
     * @param array $changes
     * @param array|string|null $attributes
     * @return bool
     */
    protected function hasChanges($changes, $attributes = null)
    {
        if (empty($attributes)) {
            return count($changes) > 0;
        }
        
        foreach ((array) $attributes as $attribute) {
            if (array_key_exists($attribute, $changes)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get the attributes that have been changed since last sync
     * 
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];
        
        foreach ($this->getAttributes() as $key => $value) {
            if (!$this->originalIsEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }
        
        return $dirty;
    }
    
    /**
     * Get the attributes that were changed
     * 
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }
    
    /**
     * Determine if the new and old values for a given key are equivalent
     * 
     * @param string $key
     * @return bool
     */
    public function originalIsEquivalent($key)
    {
        if (!array_key_exists($key, $this->original)) {
            return false;
        }
        
        $attribute = $this->getAttributes()[$key] ?? null;
        $original = $this->original[$key] ?? null;
        
        if ($attribute === $original) {
            return true;
        } elseif (is_null($attribute)) {
            return false;
        } elseif ($this->isDateAttribute($key)) {
            return $this->fromDateTime($attribute) === $this->fromDateTime($original);
        } elseif ($this->hasCast($key, static::$primitiveCastTypes)) {
            return $this->castAttribute($key, $attribute) ===
                   $this->castAttribute($key, $original);
        }
        
        return is_numeric($attribute) && is_numeric($original)
               && strcmp((string) $attribute, (string) $original) === 0;
    }
    
    /**
     * Convert the model instance to an array
     * 
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->attributesToArray(), $this->relationsToArray());
    }
    
    /**
     * Convert the model's attributes to an array
     * 
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = $this->addDateAttributesToArray(
            $attributes = $this->getArrayableAttributes()
        );
        
        $attributes = $this->addMutatedAttributesToArray(
            $attributes, $mutatedAttributes = $this->getMutatedAttributes()
        );
        
        $attributes = $this->addCastAttributesToArray(
            $attributes, $mutatedAttributes
        );
        
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }
        
        return $attributes;
    }
    
    /**
     * Convert the model instance to JSON
     * 
     * @param int $options
     * @return string
     * 
     * @throws DatabaseException
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);
        
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new DatabaseException(json_last_error_msg());
        }
        
        return $json;
    }
    
    /**
     * Convert the object into something JSON serializable
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
    /**
     * Determine if the given attribute exists
     * 
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return !is_null($this->getAttribute($offset));
    }
    
    /**
     * Get the value for a given offset
     * 
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->getAttribute($offset);
    }
    
    /**
     * Set the value for a given offset
     * 
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->setAttribute($offset, $value);
    }
    
    /**
     * Unset the value for a given offset
     * 
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }
    
    /**
     * Get the current position for iteration
     * 
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }
    
    /**
     * Get the current value for iteration
     * 
     * @return mixed
     */
    public function current(): mixed
    {
        $keys = array_keys($this->attributes);
        return $this->attributes[$keys[$this->position]] ?? null;
    }
    
    /**
     * Move to the next position for iteration
     * 
     * @return void
     */
    public function next(): void
    {
        ++$this->position;
    }
    
    /**
     * Rewind to the first position for iteration
     * 
     * @return void
     */
    public function rewind(): void
    {
        $this->position = 0;
    }
    
    /**
     * Check if the current position is valid for iteration
     * 
     * @return bool
     */
    public function valid(): bool
    {
        $keys = array_keys($this->attributes);
        return isset($keys[$this->position]);
    }
    
    /**
     * Count the number of attributes
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->attributes);
    }
    
    /**
     * Dynamically retrieve attributes on the model
     * 
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Dynamically set attributes on the model
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Determine if an attribute or relation exists on the model
     * 
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }
    
    /**
     * Unset an attribute on the model
     * 
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
    
    /**
     * Convert the model to its string representation
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
    
    /**
     * When a model is being unserialized, check if it needs to be booted
     * 
     * @return void
     */
    public function __wakeup()
    {
        $this->bootIfNotBooted();
    }
    
    // Additional helper methods that would be implemented
    // These are placeholders for methods that would need full implementation
    
    protected function mergeAttributesFromClassCasts() { /* Implementation */ }
    protected function getConnectionName() { return null; }
    protected function setConnection($name) { /* Implementation */ }
    protected static function resolveConnection() { return null; }
    protected function getIncrementing() { return $this->incrementing ?? true; }
    protected function isDateAttribute($key) { return in_array($key, $this->getDates()); }
    protected function fromDateTime($value) { return $value; }
    protected function hasCast($key, $types = null) { return false; }
    protected function castAttribute($key, $value) { return $value; }
    protected function addDateAttributesToArray($attributes) { return $attributes; }
    protected function addMutatedAttributesToArray($attributes, $mutated) { return $attributes; }
    protected function addCastAttributesToArray($attributes, $mutated) { return $attributes; }
    protected function getArrayableAttributes() { return $this->getAttributes(); }
    protected function getMutatedAttributes() { return []; }
    protected function getArrayableAppends() { return $this->appends; }
    protected function mutateAttributeForArray($key, $value) { return $value; }
    protected function relationsToArray() { return []; }
}