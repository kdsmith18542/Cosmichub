<?php

namespace App\Core\Database;

use App\Core\Traits\Loggable;
use App\Core\Exceptions\DatabaseException;
use Closure;

/**
 * Enhanced QueryBuilder with advanced features
 * 
 * Provides query optimization, caching, relationship handling,
 * and advanced query building capabilities
 */
class EnhancedQueryBuilder extends QueryBuilder
{
    use Loggable;
    
    /**
     * @var array Query cache
     */
    protected static $queryCache = [];
    
    /**
     * @var bool Enable query caching
     */
    protected $enableCache = false;
    
    /**
     * @var int Cache TTL in seconds
     */
    protected $cacheTtl = 300;
    
    /**
     * @var array Performance metrics
     */
    protected $metrics = [];
    
    /**
     * @var array Eager load relationships
     */
    protected $eagerLoads = [];
    
    /**
     * @var array Query scopes
     */
    protected $scopes = [];
    
    /**
     * @var bool Enable query logging
     */
    protected $enableQueryLog = true;
    
    /**
     * Enable query result caching
     * 
     * @param int $ttl Cache TTL in seconds
     * @return $this
     */
    public function cache($ttl = 300)
    {
        $this->enableCache = true;
        $this->cacheTtl = $ttl;
        
        return $this;
    }
    
    /**
     * Disable query caching
     * 
     * @return $this
     */
    public function noCache()
    {
        $this->enableCache = false;
        
        return $this;
    }
    
    /**
     * Add eager loading for relationships
     * 
     * @param array|string $relations
     * @return $this
     */
    public function with($relations)
    {
        $relations = is_array($relations) ? $relations : func_get_args();
        
        foreach ($relations as $relation) {
            $this->eagerLoads[] = $relation;
        }
        
        return $this;
    }
    
    /**
     * Add a query scope
     * 
     * @param string $scope
     * @param mixed ...$parameters
     * @return $this
     */
    public function scope($scope, ...$parameters)
    {
        $this->scopes[] = [
            'scope' => $scope,
            'parameters' => $parameters
        ];
        
        return $this;
    }
    
    /**
     * Add a where clause with better type handling
     * 
     * @param string|array|Closure $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // Handle closure-based where clauses
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }
        
        // Handle array of conditions
        if (is_array($column)) {
            return $this->whereArray($column, $boolean);
        }
        
        // Handle two-parameter where (column, value)
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        // Type-safe value handling
        $value = $this->prepareValue($value);
        
        return parent::where($column, $operator, $value, $boolean);
    }
    
    /**
     * Add a nested where clause
     * 
     * @param Closure $callback
     * @param string $boolean
     * @return $this
     */
    protected function whereNested(Closure $callback, $boolean = 'and')
    {
        $query = new static($this->connection);
        $query->table($this->table);
        
        $callback($query);
        
        if (count($query->wheres) > 0) {
            $this->wheres[] = [
                'type' => 'nested',
                'query' => $query,
                'boolean' => $boolean
            ];
            
            $this->addBinding($query->getBindings()['where'], 'where');
        }
        
        return $this;
    }
    
    /**
     * Add where clauses from array
     * 
     * @param array $conditions
     * @param string $boolean
     * @return $this
     */
    protected function whereArray(array $conditions, $boolean = 'and')
    {
        foreach ($conditions as $key => $value) {
            if (is_numeric($key)) {
                // Handle [column, operator, value] format
                if (is_array($value) && count($value) >= 2) {
                    $this->where($value[0], $value[1] ?? '=', $value[2] ?? null, $boolean);
                }
            } else {
                // Handle [column => value] format
                $this->where($key, '=', $value, $boolean);
            }
        }
        
        return $this;
    }
    
    /**
     * Add a where in clause with better performance
     * 
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIn($column, array $values, $boolean = 'and', $not = false)
    {
        // Optimize for large arrays
        if (count($values) > 1000) {
            return $this->whereInChunked($column, $values, $boolean, $not);
        }
        
        // Remove duplicates and null values
        $values = array_unique(array_filter($values, function($value) {
            return $value !== null;
        }));
        
        if (empty($values)) {
            return $not ? $this : $this->whereRaw('1 = 0');
        }
        
        return parent::whereIn($column, $values, $boolean, $not);
    }
    
    /**
     * Handle large whereIn clauses by chunking
     * 
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    protected function whereInChunked($column, array $values, $boolean = 'and', $not = false)
    {
        $chunks = array_chunk($values, 1000);
        
        return $this->where(function($query) use ($column, $chunks, $not) {
            foreach ($chunks as $chunk) {
                if ($not) {
                    $query->whereNotIn($column, $chunk, 'and');
                } else {
                    $query->whereIn($column, $chunk, 'or');
                }
            }
        }, null, null, $boolean);
    }
    
    /**
     * Add a full-text search clause
     * 
     * @param string|array $columns
     * @param string $value
     * @param array $options
     * @return $this
     */
    public function whereFullText($columns, $value, array $options = [])
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $mode = $options['mode'] ?? 'natural';
        
        $columnList = implode(',', array_map(function($column) {
            return "`{$column}`";
        }, $columns));
        
        $modeClause = '';
        switch ($mode) {
            case 'boolean':
                $modeClause = ' IN BOOLEAN MODE';
                break;
            case 'query_expansion':
                $modeClause = ' WITH QUERY EXPANSION';
                break;
        }
        
        return $this->whereRaw(
            "MATCH({$columnList}) AGAINST(?{$modeClause})",
            [$value]
        );
    }
    
    /**
     * Add a JSON where clause
     * 
     * @param string $column
     * @param string $path
     * @param mixed $value
     * @param string $operator
     * @param string $boolean
     * @return $this
     */
    public function whereJson($column, $path, $value, $operator = '=', $boolean = 'and')
    {
        $jsonPath = str_replace('.', '->', $path);
        
        return $this->whereRaw(
            "JSON_EXTRACT(`{$column}`, '$.{$jsonPath}') {$operator} ?",
            [$value],
            $boolean
        );
    }
    
    /**
     * Execute the query with performance monitoring
     * 
     * @return array
     */
    public function get()
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        try {
            // Check cache first
            if ($this->enableCache) {
                $cacheKey = $this->getCacheKey();
                if (isset(static::$queryCache[$cacheKey])) {
                    $this->logQuery('CACHE HIT', $this->toSql(), $this->getBindings()['where']);
                    return static::$queryCache[$cacheKey];
                }
            }
            
            // Execute query
            $result = parent::get();
            
            // Cache result
            if ($this->enableCache) {
                static::$queryCache[$cacheKey] = $result;
            }
            
            // Record metrics
            $this->recordMetrics($startTime, $startMemory, count($result));
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logError('Query execution failed', [
                'sql' => $this->toSql(),
                'bindings' => $this->getBindings(),
                'error' => $e->getMessage()
            ]);
            
            throw new DatabaseException(
                'Query execution failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Get first result with caching
     * 
     * @return array|null
     */
    public function first()
    {
        return $this->limit(1)->get()[0] ?? null;
    }
    
    /**
     * Prepare value for binding
     * 
     * @param mixed $value
     * @return mixed
     */
    protected function prepareValue($value)
    {
        if (is_bool($value)) {
            return (int) $value;
        }
        
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        
        return $value;
    }
    
    /**
     * Generate cache key for query
     * 
     * @return string
     */
    protected function getCacheKey()
    {
        return md5($this->toSql() . serialize($this->getBindings()));
    }
    
    /**
     * Record performance metrics
     * 
     * @param float $startTime
     * @param int $startMemory
     * @param int $resultCount
     */
    protected function recordMetrics($startTime, $startMemory, $resultCount)
    {
        $executionTime = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage() - $startMemory;
        
        $this->metrics = [
            'execution_time' => $executionTime,
            'memory_usage' => $memoryUsage,
            'result_count' => $resultCount,
            'sql' => $this->toSql()
        ];
        
        // Log slow queries
        if ($executionTime > 1.0) {
            $this->logWarning('Slow query detected', $this->metrics);
        }
        
        if ($this->enableQueryLog) {
            $this->logQuery('EXECUTED', $this->toSql(), $this->getBindings()['where'], $executionTime);
        }
    }
    
    /**
     * Log query execution
     * 
     * @param string $type
     * @param string $sql
     * @param array $bindings
     * @param float|null $time
     */
    protected function logQuery($type, $sql, $bindings = [], $time = null)
    {
        $context = [
            'type' => $type,
            'sql' => $sql,
            'bindings' => $bindings
        ];
        
        if ($time !== null) {
            $context['execution_time'] = $time;
        }
        
        $this->logInfo('Database query', $context);
    }
    
    /**
     * Get performance metrics
     * 
     * @return array
     */
    public function getMetrics()
    {
        return $this->metrics;
    }
    
    /**
     * Clear query cache
     */
    public static function clearCache()
    {
        static::$queryCache = [];
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public static function getCacheStats()
    {
        return [
            'entries' => count(static::$queryCache),
            'memory_usage' => strlen(serialize(static::$queryCache))
        ];
    }
}