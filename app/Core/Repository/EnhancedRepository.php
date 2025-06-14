<?php

namespace App\Core\Repository;

use App\Core\Database\EnhancedQueryBuilder;
use App\Core\Database\DatabaseManager;
use App\Core\Model\Model;
use App\Core\Application;
use App\Core\Traits\Loggable;
use App\Core\Events\EventDispatcher;
use App\Core\Exceptions\RepositoryException;
use Closure;

/**
 * Enhanced Repository with advanced features
 * 
 * Provides caching, events, validation, and advanced query capabilities
 */
abstract class EnhancedRepository extends Repository
{
    use Loggable;
    
    /**
     * @var EventDispatcher Event dispatcher
     */
    protected $events;
    
    /**
     * @var array Repository cache
     */
    protected static $cache = [];
    
    /**
     * @var bool Enable repository caching
     */
    protected $enableCache = true;
    
    /**
     * @var int Cache TTL in seconds
     */
    protected $cacheTtl = 600;
    
    /**
     * @var array Fillable attributes for mass assignment
     */
    protected $fillable = [];
    
    /**
     * @var array Guarded attributes (cannot be mass assigned)
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    /**
     * @var array Validation rules
     */
    protected $rules = [];
    
    /**
     * @var array Default query scopes
     */
    protected $defaultScopes = [];
    
    /**
     * @var array Relationships to eager load by default
     */
    protected $defaultWith = [];
    
    /**
     * @var array Query filters
     */
    protected $filters = [];
    
    /**
     * Create a new enhanced repository instance
     * 
     * @param DatabaseManager $db
     * @param Application $app
     * @param EventDispatcher|null $events
     */
    public function __construct(DatabaseManager $db, Application $app, EventDispatcher $events = null)
    {
        parent::__construct($db, $app);
        $this->events = $events;
    }
    
    /**
     * Get a new enhanced query builder instance
     * 
     * @return EnhancedQueryBuilder
     */
    public function newQuery()
    {
        $query = new EnhancedQueryBuilder($this->db->connection());
        $query->table($this->getTable());
        
        // Apply default scopes
        foreach ($this->defaultScopes as $scope) {
            $query->scope($scope);
        }
        
        // Apply default eager loading
        if (!empty($this->defaultWith)) {
            $query->with($this->defaultWith);
        }
        
        return $query;
    }
    
    /**
     * Find a record by ID with caching
     * 
     * @param mixed $id
     * @param array $columns
     * @return Model|null
     */
    public function find($id, $columns = ['*'])
    {
        if ($this->enableCache) {
            $cacheKey = $this->getCacheKey('find', $id, $columns);
            
            if (isset(static::$cache[$cacheKey])) {
                return static::$cache[$cacheKey];
            }
        }
        
        $result = $this->newQuery()
            ->select($columns)
            ->where('id', $id)
            ->first();
        
        if (!$result) {
            return null;
        }
        
        $model = $this->model ? new $this->model($result) : $result;
        
        // Cache the result
        if ($this->enableCache) {
            static::$cache[$cacheKey] = $model;
        }
        
        return $model;
    }
    
    /**
     * Create a new record with validation and events
     * 
     * @param array $data
     * @return Model|array
     * @throws RepositoryException
     */
    public function create(array $data)
    {
        // Validate data
        $this->validateData($data);
        
        // Filter fillable attributes
        $data = $this->filterFillable($data);
        
        // Fire creating event
        if ($this->events) {
            $this->events->dispatch('repository.creating', [
                'repository' => static::class,
                'data' => $data
            ]);
        }
        
        try {
            $id = $this->newQuery()->insert($data);
            
            if ($this->model) {
                $data['id'] = $id;
                $model = new $this->model($data);
            } else {
                $model = $data;
            }
            
            // Fire created event
            if ($this->events) {
                $this->events->dispatch('repository.created', [
                    'repository' => static::class,
                    'model' => $model
                ]);
            }
            
            // Clear cache
            $this->clearCache();
            
            $this->logInfo('Record created', [
                'table' => $this->getTable(),
                'id' => $id
            ]);
            
            return $model;
            
        } catch (\Exception $e) {
            $this->logError('Failed to create record', [
                'table' => $this->getTable(),
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            throw new RepositoryException(
                'Failed to create record: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Update a record with validation and events
     * 
     * @param mixed $id
     * @param array $data
     * @return bool
     * @throws RepositoryException
     */
    public function update($id, array $data)
    {
        // Validate data
        $this->validateData($data, $id);
        
        // Filter fillable attributes
        $data = $this->filterFillable($data);
        
        // Get existing model for events
        $existingModel = $this->find($id);
        
        if (!$existingModel) {
            throw new RepositoryException("Record with ID {$id} not found");
        }
        
        // Fire updating event
        if ($this->events) {
            $this->events->dispatch('repository.updating', [
                'repository' => static::class,
                'id' => $id,
                'data' => $data,
                'existing' => $existingModel
            ]);
        }
        
        try {
            $result = $this->newQuery()
                ->where('id', $id)
                ->update($data);
            
            // Fire updated event
            if ($this->events) {
                $this->events->dispatch('repository.updated', [
                    'repository' => static::class,
                    'id' => $id,
                    'data' => $data,
                    'existing' => $existingModel
                ]);
            }
            
            // Clear cache
            $this->clearCache();
            
            $this->logInfo('Record updated', [
                'table' => $this->getTable(),
                'id' => $id
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logError('Failed to update record', [
                'table' => $this->getTable(),
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            throw new RepositoryException(
                'Failed to update record: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Delete a record with events
     * 
     * @param mixed $id
     * @return bool
     * @throws RepositoryException
     */
    public function delete($id)
    {
        // Get existing model for events
        $existingModel = $this->find($id);
        
        if (!$existingModel) {
            throw new RepositoryException("Record with ID {$id} not found");
        }
        
        // Fire deleting event
        if ($this->events) {
            $this->events->dispatch('repository.deleting', [
                'repository' => static::class,
                'id' => $id,
                'model' => $existingModel
            ]);
        }
        
        try {
            $result = $this->newQuery()
                ->where('id', $id)
                ->delete();
            
            // Fire deleted event
            if ($this->events) {
                $this->events->dispatch('repository.deleted', [
                    'repository' => static::class,
                    'id' => $id,
                    'model' => $existingModel
                ]);
            }
            
            // Clear cache
            $this->clearCache();
            
            $this->logInfo('Record deleted', [
                'table' => $this->getTable(),
                'id' => $id
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logError('Failed to delete record', [
                'table' => $this->getTable(),
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            throw new RepositoryException(
                'Failed to delete record: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Find records with advanced filtering
     * 
     * @param array $filters
     * @param array $options
     * @return array
     */
    public function findWithFilters(array $filters = [], array $options = [])
    {
        $query = $this->newQuery();
        
        // Apply filters
        foreach ($filters as $key => $value) {
            if (method_exists($this, 'filter' . ucfirst($key))) {
                $method = 'filter' . ucfirst($key);
                $this->$method($query, $value);
            } elseif (isset($this->filters[$key])) {
                $filter = $this->filters[$key];
                if ($filter instanceof Closure) {
                    $filter($query, $value);
                }
            } else {
                $query->where($key, $value);
            }
        }
        
        // Apply sorting
        if (isset($options['sort'])) {
            $sortField = $options['sort'];
            $sortDirection = $options['direction'] ?? 'asc';
            $query->orderBy($sortField, $sortDirection);
        }
        
        // Apply pagination
        if (isset($options['page']) && isset($options['per_page'])) {
            return $query->paginate($options['per_page'], $options['page']);
        }
        
        // Apply limit
        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }
        
        return $query->get();
    }
    
    /**
     * Bulk insert records
     * 
     * @param array $records
     * @return bool
     */
    public function bulkInsert(array $records)
    {
        if (empty($records)) {
            return true;
        }
        
        // Validate all records
        foreach ($records as $record) {
            $this->validateData($record);
        }
        
        try {
            $result = $this->newQuery()->insertBatch($records);
            
            // Clear cache
            $this->clearCache();
            
            $this->logInfo('Bulk insert completed', [
                'table' => $this->getTable(),
                'count' => count($records)
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logError('Bulk insert failed', [
                'table' => $this->getTable(),
                'count' => count($records),
                'error' => $e->getMessage()
            ]);
            
            throw new RepositoryException(
                'Bulk insert failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Validate data against rules
     * 
     * @param array $data
     * @param mixed|null $id
     * @throws RepositoryException
     */
    protected function validateData(array $data, $id = null)
    {
        if (empty($this->rules)) {
            return;
        }
        
        $errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $fieldRules = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($fieldRules as $rule) {
                if (!$this->validateField($data, $field, $rule, $id)) {
                    $errors[$field][] = "Field {$field} failed validation rule: {$rule}";
                }
            }
        }
        
        if (!empty($errors)) {
            throw new RepositoryException('Validation failed: ' . json_encode($errors));
        }
    }
    
    /**
     * Validate a single field
     * 
     * @param array $data
     * @param string $field
     * @param string $rule
     * @param mixed|null $id
     * @return bool
     */
    protected function validateField(array $data, $field, $rule, $id = null)
    {
        $value = $data[$field] ?? null;
        
        switch ($rule) {
            case 'required':
                return !empty($value);
            
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            
            case 'numeric':
                return is_numeric($value);
            
            case 'unique':
                $query = $this->newQuery()->where($field, $value);
                if ($id) {
                    $query->where('id', '!=', $id);
                }
                return !$query->exists();
            
            default:
                if (strpos($rule, 'min:') === 0) {
                    $min = (int) substr($rule, 4);
                    return strlen($value) >= $min;
                }
                
                if (strpos($rule, 'max:') === 0) {
                    $max = (int) substr($rule, 4);
                    return strlen($value) <= $max;
                }
                
                return true;
        }
    }
    
    /**
     * Filter fillable attributes
     * 
     * @param array $data
     * @return array
     */
    protected function filterFillable(array $data)
    {
        if (!empty($this->fillable)) {
            return array_intersect_key($data, array_flip($this->fillable));
        }
        
        if (!empty($this->guarded)) {
            return array_diff_key($data, array_flip($this->guarded));
        }
        
        return $data;
    }
    
    /**
     * Generate cache key
     * 
     * @param string $method
     * @param mixed ...$params
     * @return string
     */
    protected function getCacheKey($method, ...$params)
    {
        return static::class . ':' . $method . ':' . md5(serialize($params));
    }
    
    /**
     * Clear repository cache
     */
    protected function clearCache()
    {
        $prefix = static::class . ':';
        
        foreach (array_keys(static::$cache) as $key) {
            if (strpos($key, $prefix) === 0) {
                unset(static::$cache[$key]);
            }
        }
    }
    
    /**
     * Enable caching
     * 
     * @param int $ttl
     * @return $this
     */
    public function cache($ttl = 600)
    {
        $this->enableCache = true;
        $this->cacheTtl = $ttl;
        
        return $this;
    }
    
    /**
     * Disable caching
     * 
     * @return $this
     */
    public function noCache()
    {
        $this->enableCache = false;
        
        return $this;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public static function getCacheStats()
    {
        return [
            'entries' => count(static::$cache),
            'memory_usage' => strlen(serialize(static::$cache))
        ];
    }
}