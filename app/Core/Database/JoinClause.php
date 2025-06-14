<?php

namespace App\Core\Database;

/**
 * JoinClause class for building SQL join clauses
 */
class JoinClause
{
    /**
     * @var QueryBuilder The query builder
     */
    protected $query;
    
    /**
     * @var string The join type
     */
    public $type;
    
    /**
     * @var string The table
     */
    public $table;
    
    /**
     * @var array The join clauses
     */
    public $clauses = [];
    
    /**
     * @var array The bindings
     */
    protected $bindings = [];
    
    /**
     * Create a new join clause instance
     * 
     * @param QueryBuilder $query The query builder
     * @param string $type The join type
     * @param string $table The table
     */
    public function __construct(QueryBuilder $query, $type, $table)
    {
        $this->query = $query;
        $this->type = $type;
        $this->table = $table;
    }
    
    /**
     * Add an "on" clause to the join
     * 
     * @param string $first The first column
     * @param string|null $operator The operator
     * @param string|null $second The second column
     * @param string $boolean The boolean
     * @return $this
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        if ($first instanceof \Closure) {
            return $this->whereNested($first, $boolean);
        }
        
        if ($this->invalidOperator($operator)) {
            list($second, $operator) = [$operator, '='];
        }
        
        $this->clauses[] = compact('first', 'operator', 'second', 'boolean');
        
        if (!$this->isExpression($second)) {
            $this->bindings[] = $second;
        }
        
        return $this;
    }
    
    /**
     * Add an "or on" clause to the join
     * 
     * @param string $first The first column
     * @param string|null $operator The operator
     * @param string|null $second The second column
     * @return $this
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }
    
    /**
     * Add a "where" clause to the join
     * 
     * @param string $first The first column
     * @param string|null $operator The operator
     * @param string|null $second The second column
     * @param string $boolean The boolean
     * @return $this
     */
    public function where($first, $operator = null, $second = null, $boolean = 'and')
    {
        if ($first instanceof \Closure) {
            return $this->whereNested($first, $boolean);
        }
        
        if ($this->invalidOperator($operator)) {
            list($second, $operator) = [$operator, '='];
        }
        
        $this->clauses[] = compact('first', 'operator', 'second', 'boolean');
        
        $this->bindings[] = $second;
        
        return $this;
    }
    
    /**
     * Add an "or where" clause to the join
     * 
     * @param string $first The first column
     * @param string|null $operator The operator
     * @param string|null $second The second column
     * @return $this
     */
    public function orWhere($first, $operator = null, $second = null)
    {
        return $this->where($first, $operator, $second, 'or');
    }
    
    /**
     * Add a nested where statement to the join
     * 
     * @param \Closure $callback The callback
     * @param string $boolean The boolean
     * @return $this
     */
    public function whereNested(\Closure $callback, $boolean = 'and')
    {
        $join = new static($this->query, $this->type, $this->table);
        
        $callback($join);
        
        $this->clauses[] = [
            'nested' => $join,
            'boolean' => $boolean,
        ];
        
        $this->bindings = array_merge($this->bindings, $join->getBindings());
        
        return $this;
    }
    
    /**
     * Check if an operator is invalid
     * 
     * @param string $operator The operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        $operators = ['=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like', 'ilike', 'not ilike', 'in', 'not in', 'between', 'not between'];
        
        return !in_array(strtolower($operator), $operators);
    }
    
    /**
     * Check if a value is an expression
     * 
     * @param mixed $value The value
     * @return bool
     */
    protected function isExpression($value)
    {
        return $value instanceof Expression;
    }
    
    /**
     * Get the bindings
     * 
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }
}