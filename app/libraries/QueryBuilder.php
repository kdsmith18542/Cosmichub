<?php
/**
 * Query Builder Class
 * 
 * Provides a fluent interface for building and executing database queries.
 */

class QueryBuilder {
    /**
     * @var string The table to query
     */
    protected $table;
    
    /**
     * @var string The model class to use
     */
    protected $model;
    
    /**
     * @var array The query columns
     */
    protected $columns = ['*'];
    
    /**
     * @var array The query where conditions
     */
    protected $wheres = [];
    
    /**
     * @var array The query order by clauses
     */
    protected $orders = [];
    
    /**
     * @var int The maximum number of records to return
     */
    protected $limit;
    
    /**
     * @var int The number of records to skip
     */
    protected $offset;
    
    /**
     * @var array The query bindings
     */
    protected $bindings = [
        'where' => [],
        'having' => [],
        'order' => []
    ];
    
    /**
     * Create a new query builder instance
     * 
     * @param string $table
     * @param string $model
     */
    public function __construct($table, $model = null) {
        $this->table = $table;
        $this->model = $model;
    }
    
    /**
     * Set the columns to be selected
     * 
     * @param array|string $columns
     * @return $this
     */
    public function select($columns = ['*']) {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    /**
     * Add a basic where clause to the query
     * 
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and') {
        // If the column is an array, we'll assume it's an array of key-value pairs
        // and add each condition to the query
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }
        
        // If the operator is not given, we'll assume the operator is '='
        // and set the value to the operator value
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        // Add the where condition
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];
        
        // Add the value to the bindings
        if ($value !== null) {
            $this->addBinding($value, 'where');
        }
        
        return $this;
    }
    
    /**
     * Add an "or where" clause to the query
     * 
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null) {
        return $this->where($column, $operator, $value, 'or');
    }
    
    /**
     * Add a "where in" clause to the query
     * 
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false) {
        $type = $not ? 'NotIn' : 'In';
        
        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean
        ];
        
        // Add the values to the bindings
        foreach ($values as $value) {
            $this->addBinding($value, 'where');
        }
        
        return $this;
    }
    
    /**
     * Add a "where not in" clause to the query
     * 
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotIn($column, $values, $boolean = 'and') {
        return $this->whereIn($column, $values, $boolean, true);
    }
    
    /**
     * Add a "where null" clause to the query
     * 
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereNull($column, $boolean = 'and', $not = false) {
        $type = $not ? 'NotNull' : 'Null';
        
        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'boolean' => $boolean
        ];
        
        return $this;
    }
    
    /**
     * Add a "where not null" clause to the query
     * 
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function whereNotNull($column, $boolean = 'and') {
        return $this->whereNull($column, $boolean, true);
    }
    
    /**
     * Add a "where between" clause to the query
     * 
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false) {
        $type = 'between';
        
        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not
        ];
        
        // Add the values to the bindings
        $this->addBinding($values, 'where');
        
        return $this;
    }
    
    /**
     * Add an "order by" clause to the query
     * 
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc') {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) === 'asc' ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }
    
    /**
     * Set the "limit" value of the query
     * 
     * @param int $value
     * @return $this
     */
    public function limit($value) {
        $this->limit = $value;
        return $this;
    }
    
    /**
     * Set the "offset" value of the query
     * 
     * @param int $value
     * @return $this
     */
    public function offset($value) {
        $this->offset = $value;
        return $this;
    }
    
    /**
     * Execute the query and get the first result
     * 
     * @return mixed
     */
    public function first() {
        $results = $this->limit(1)->get();
        return count($results) > 0 ? $results[0] : null;
    }
    
    /**
     * Execute the query and get all results
     * 
     * @return array
     */
    public function get() {
        $sql = $this->toSql();
        $bindings = $this->getBindings();
        
        // Execute the query
        $results = Database::all($sql, $bindings);
        
        // Convert results to model instances if a model class is set
        if ($this->model) {
            return array_map(function($item) {
                $model = new $this->model((array) $item);
                $model->exists = true;
                return $model;
            }, $results);
        }
        
        return $results;
    }
    
    /**
     * Execute the query and get a single column's value
     * 
     * @param string $column
     * @return mixed
     */
    public function value($column) {
        $result = $this->first([$column]);
        return $result ? $result->{$column} : null;
    }
    
    /**
     * Get the SQL representation of the query
     * 
     * @return string
     */
    public function toSql() {
        $sql = 'SELECT ' . $this->compileColumns() . ' FROM ' . $this->table;
        
        // Add where clauses
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres($this->wheres);
        }
        
        // Add order by clauses
        if (!empty($this->orders)) {
            $sql .= ' ' . $this->compileOrders();
        }
        
        // Add limit and offset
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        
        return $sql;
    }
    
    /**
     * Get the bindings for the query
     * 
     * @return array
     */
    public function getBindings() {
        return array_merge(
            $this->bindings['where'],
            $this->bindings['having'],
            $this->bindings['order']
        );
    }
    
    /**
     * Add a binding to the query
     * 
     * @param mixed $value
     * @param string $type
     * @return void
     */
    protected function addBinding($value, $type = 'where') {
        if (is_array($value)) {
            $this->bindings[$type] = array_merge($this->bindings[$type], $value);
        } else {
            $this->bindings[$type][] = $value;
        }
    }
    
    /**
     * Compile the select columns
     * 
     * @return string
     */
    protected function compileColumns() {
        return implode(', ', $this->columns);
    }
    
    /**
     * Compile the where clauses
     * 
     * @param array $wheres
     * @return string
     */
    protected function compileWheres($wheres) {
        $sql = [];
        
        foreach ($wheres as $where) {
            $method = 'compileWhere' . $where['type'];
            
            if (method_exists($this, $method)) {
                $sql[] = $where['boolean'] . ' ' . $this->$method($where);
            }
        }
        
        // Remove the first boolean
        if (!empty($sql)) {
            $sql[0] = preg_replace('/^and\s+|^or\s+/i', '', $sql[0]);
        }
        
        return implode(' ', $sql);
    }
    
    /**
     * Compile a basic where clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereBasic($where) {
        return $where['column'] . ' ' . $where['operator'] . ' ?';
    }
    
    /**
     * Compile a where in clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereIn($where) {
        $placeholders = rtrim(str_repeat('?, ', count($where['values'])), ', ');
        return $where['column'] . ' IN (' . $placeholders . ')';
    }
    
    /**
     * Compile a where not in clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereNotIn($where) {
        return $this->compileWhereIn($where) . ' NOT';
    }
    
    /**
     * Compile a where null clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereNull($where) {
        return $where['column'] . ' IS NULL';
    }
    
    /**
     * Compile a where not null clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereNotNull($where) {
        return $where['column'] . ' IS NOT NULL';
    }
    
    /**
     * Compile a where between clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereBetween($where) {
        $not = $where['not'] ? 'NOT ' : '';
        return $where['column'] . ' ' . $not . 'BETWEEN ? AND ?';
    }
    
    /**
     * Compile the order by clauses
     * 
     * @return string
     */
    protected function compileOrders() {
        $orders = [];
        
        foreach ($this->orders as $order) {
            $orders[] = $order['column'] . ' ' . $order['direction'];
        }
        
        return 'ORDER BY ' . implode(', ', $orders);
    }
    
    /**
     * Add an array of where clauses to the query
     * 
     * @param array $wheres
     * @param string $boolean
     * @param string $method
     * @return $this
     */
    protected function addArrayOfWheres($wheres, $boolean = 'and', $method = 'where') {
        foreach ($wheres as $key => $value) {
            if (is_numeric($key) && is_array($value)) {
                $this->$method(...array_values($value));
            } else {
                $this->$method($key, '=', $value, $boolean);
            }
        }
        
        return $this;
    }
    
    /**
     * Handle dynamic method calls into the query builder
     * 
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters) {
        // Handle dynamic where methods (e.g., whereName, whereEmail)
        if (strpos($method, 'where') === 0 && $method !== 'where') {
            return $this->dynamicWhere($method, $parameters);
        }
        
        // Forward the call to the database connection
        return call_user_func_array([$this, $method], $parameters);
    }
    
    /**
     * Handle dynamic where methods
     * 
     * @param string $method
     * @param array $parameters
     * @return $this
     */
    protected function dynamicWhere($method, $parameters) {
        $method = substr($method, 5); // Remove 'where' from method name
        $operator = '=';
        $value = $parameters[0] ?? null;
        $boolean = 'and';
        
        // Check for 'Or' at the end of the method name
        if (strpos($method, 'Or') === strlen($method) - 2) {
            $boolean = 'or';
            $method = substr($method, 0, -2);
        }
        
        // Check for operator in method name (e.g., whereNameLike)
        foreach (['Not', 'Like', 'In', 'Null', 'NotNull', 'Between'] as $op) {
            if (strpos($method, $op) !== false) {
                $method = str_replace($op, '', $method);
                $operator = strtoupper($op);
                break;
            }
        }
        
        // Convert camelCase to snake_case for column name
        $column = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $method));
        
        // Add the where clause
        return $this->where($column, $operator, $value, $boolean);
    }
}
