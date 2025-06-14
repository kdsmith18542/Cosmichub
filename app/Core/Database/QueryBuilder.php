<?php

namespace App\Core\Database;

/**
 * QueryBuilder class for building SQL queries
 */
class QueryBuilder
{
    /**
     * @var ConnectionInterface The database connection
     */
    protected $connection;
    
    /**
     * @var string The table name
     */
    protected $table;
    
    /**
     * @var array The select columns
     */
    protected $columns = ['*'];
    
    /**
     * @var array The where clauses
     */
    protected $wheres = [];
    
    /**
     * @var array The order by clauses
     */
    protected $orders = [];
    
    /**
     * @var array The group by clauses
     */
    protected $groups = [];
    
    /**
     * @var array The having clauses
     */
    protected $havings = [];
    
    /**
     * @var array The joins
     */
    protected $joins = [];
    
    /**
     * @var int The limit
     */
    protected $limit;
    
    /**
     * @var int The offset
     */
    protected $offset;
    
    /**
     * @var array The bindings
     */
    protected $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
        'having' => [],
        'order' => [],
        'union' => [],
    ];
    
    /**
     * Create a new query builder instance
     * 
     * @param ConnectionInterface $connection The database connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * Set the table
     * 
     * @param string $table The table name
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;
        
        return $this;
    }
    
    /**
     * Set the columns to select
     * 
     * @param array|string $columns The columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        
        return $this;
    }
    
    /**
     * Add a where clause
     * 
     * @param string|array $column The column
     * @param string|null $operator The operator
     * @param mixed $value The value
     * @param string $boolean The boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->where($key, '=', $value, $boolean);
            }
            
            return $this;
        }
        
        // If the columns is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
        if ($column instanceof \Closure) {
            return $this->whereNested($column, $boolean);
        }
        
        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if ($this->invalidOperator($operator)) {
            list($value, $operator) = [$operator, '='];
        }
        
        // If the value is a Closure, we will assume it is a sub-select query and
        // keep the operator and add the Closure to the query as a sub-select.
        if ($value instanceof \Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }
        
        // If the value is "null", we will just assume the developer wants to add a
        // where null clause to the query. So, we will allow a short-cut here to
        // that method for convenience so the developer doesn't have to check.
        if (is_null($value)) {
            return $this->whereNull($column, $boolean, $operator != '=');
        }
        
        $type = 'Basic';
        
        // Now that we are working with just a simple query we can put the elements
        // in our array and add the query binding to our array of bindings that
        // will be bound to each SQL statements when it is finally executed.
        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');
        
        $this->addBinding($value, 'where');
        
        return $this;
    }
    
    /**
     * Add an "or where" clause to the query
     * 
     * @param string|array $column The column
     * @param string|null $operator The operator
     * @param mixed $value The value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }
    
    /**
     * Add a "where in" clause to the query
     * 
     * @param string $column The column
     * @param array $values The values
     * @param string $boolean The boolean
     * @param bool $not Whether to negate
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';
        
        $this->wheres[] = compact('type', 'column', 'values', 'boolean');
        
        $this->addBinding($values, 'where');
        
        return $this;
    }
    
    /**
     * Add an "or where in" clause to the query
     * 
     * @param string $column The column
     * @param array $values The values
     * @return $this
     */
    public function orWhereIn($column, $values)
    {
        return $this->whereIn($column, $values, 'or');
    }
    
    /**
     * Add a "where not in" clause to the query
     * 
     * @param string $column The column
     * @param array $values The values
     * @param string $boolean The boolean
     * @return $this
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }
    
    /**
     * Add an "or where not in" clause to the query
     * 
     * @param string $column The column
     * @param array $values The values
     * @return $this
     */
    public function orWhereNotIn($column, $values)
    {
        return $this->whereNotIn($column, $values, 'or');
    }
    
    /**
     * Add a "where null" clause to the query
     * 
     * @param string $column The column
     * @param string $boolean The boolean
     * @param bool $not Whether to negate
     * @return $this
     */
    public function whereNull($column, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotNull' : 'Null';
        
        $this->wheres[] = compact('type', 'column', 'boolean');
        
        return $this;
    }
    
    /**
     * Add an "or where null" clause to the query
     * 
     * @param string $column The column
     * @return $this
     */
    public function orWhereNull($column)
    {
        return $this->whereNull($column, 'or');
    }
    
    /**
     * Add a "where not null" clause to the query
     * 
     * @param string $column The column
     * @param string $boolean The boolean
     * @return $this
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        return $this->whereNull($column, $boolean, true);
    }
    
    /**
     * Add an "or where not null" clause to the query
     * 
     * @param string $column The column
     * @return $this
     */
    public function orWhereNotNull($column)
    {
        return $this->whereNotNull($column, 'or');
    }
    
    /**
     * Add a nested where statement to the query
     * 
     * @param \Closure $callback The callback
     * @param string $boolean The boolean
     * @return $this
     */
    public function whereNested(\Closure $callback, $boolean = 'and')
    {
        $query = new static($this->connection);
        
        $callback($query);
        
        if (count($query->wheres)) {
            $type = 'Nested';
            $this->wheres[] = compact('type', 'query', 'boolean');
            $this->addBinding($query->getBindings()['where'], 'where');
        }
        
        return $this;
    }
    
    /**
     * Add a where between statement to the query
     * 
     * @param string $column The column
     * @param array $values The values
     * @param string $boolean The boolean
     * @param bool $not Whether to negate
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotBetween' : 'Between';
        
        $this->wheres[] = compact('type', 'column', 'values', 'boolean');
        
        $this->addBinding($values, 'where');
        
        return $this;
    }
    
    /**
     * Add an or where between statement to the query
     * 
     * @param string $column The column
     * @param array $values The values
     * @return $this
     */
    public function orWhereBetween($column, array $values)
    {
        return $this->whereBetween($column, $values, 'or');
    }
    
    /**
     * Add a where not between statement to the query
     * 
     * @param string $column The column
     * @param array $values The values
     * @param string $boolean The boolean
     * @return $this
     */
    public function whereNotBetween($column, array $values, $boolean = 'and')
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }
    
    /**
     * Add an or where not between statement to the query
     * 
     * @param string $column The column
     * @param array $values The values
     * @return $this
     */
    public function orWhereNotBetween($column, array $values)
    {
        return $this->whereNotBetween($column, $values, 'or');
    }
    
    /**
     * Add a "where date" statement to the query
     * 
     * @param string $column The column
     * @param string $operator The operator
     * @param mixed $value The value
     * @param string $boolean The boolean
     * @return $this
     */
    public function whereDate($column, $operator, $value = null, $boolean = 'and')
    {
        if ($this->invalidOperator($operator)) {
            list($value, $operator) = [$operator, '='];
        }
        
        $type = 'Date';
        
        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');
        
        $this->addBinding($value, 'where');
        
        return $this;
    }
    
    /**
     * Add an "or where date" statement to the query
     * 
     * @param string $column The column
     * @param string $operator The operator
     * @param mixed $value The value
     * @return $this
     */
    public function orWhereDate($column, $operator, $value = null)
    {
        return $this->whereDate($column, $operator, $value, 'or');
    }
    
    /**
     * Add an "order by" clause to the query
     * 
     * @param string $column The column
     * @param string $direction The direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = strtolower($direction) == 'asc' ? 'asc' : 'desc';
        
        $this->orders[] = compact('column', 'direction');
        
        return $this;
    }
    
    /**
     * Add a descending "order by" clause to the query
     * 
     * @param string $column The column
     * @return $this
     */
    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'desc');
    }
    
    /**
     * Add a "group by" clause to the query
     * 
     * @param array|string $groups The groups
     * @return $this
     */
    public function groupBy($groups)
    {
        $groups = is_array($groups) ? $groups : func_get_args();
        
        foreach ($groups as $group) {
            $this->groups[] = $group;
        }
        
        return $this;
    }
    
    /**
     * Add a "having" clause to the query
     * 
     * @param string $column The column
     * @param string|null $operator The operator
     * @param mixed $value The value
     * @param string $boolean The boolean
     * @return $this
     */
    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($this->invalidOperator($operator)) {
            list($value, $operator) = [$operator, '='];
        }
        
        $type = 'Basic';
        
        $this->havings[] = compact('type', 'column', 'operator', 'value', 'boolean');
        
        $this->addBinding($value, 'having');
        
        return $this;
    }
    
    /**
     * Add an "or having" clause to the query
     * 
     * @param string $column The column
     * @param string|null $operator The operator
     * @param mixed $value The value
     * @return $this
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        return $this->having($column, $operator, $value, 'or');
    }
    
    /**
     * Add a "join" clause to the query
     * 
     * @param string $table The table
     * @param string $first The first column
     * @param string|null $operator The operator
     * @param string|null $second The second column
     * @param string $type The join type
     * @param bool $where Whether to add a where clause
     * @return $this
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        $join = new JoinClause($this, $type, $table);
        
        if ($first instanceof \Closure) {
            $first($join);
            
            $this->joins[] = $join;
            
            $this->addBinding($join->getBindings(), 'join');
        } else {
            if ($where) {
                $join->where($first, $operator, $second);
            } else {
                $join->on($first, $operator, $second);
            }
            
            $this->joins[] = $join;
            
            $this->addBinding($join->getBindings(), 'join');
        }
        
        return $this;
    }
    
    /**
     * Add a "left join" clause to the query
     * 
     * @param string $table The table
     * @param string $first The first column
     * @param string|null $operator The operator
     * @param string|null $second The second column
     * @return $this
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }
    
    /**
     * Add a "right join" clause to the query
     * 
     * @param string $table The table
     * @param string $first The first column
     * @param string|null $operator The operator
     * @param string|null $second The second column
     * @return $this
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }
    
    /**
     * Add a "cross join" clause to the query
     * 
     * @param string $table The table
     * @return $this
     */
    public function crossJoin($table)
    {
        return $this->join($table, null, null, null, 'cross');
    }
    
    /**
     * Set the limit
     * 
     * @param int $limit The limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        
        return $this;
    }
    
    /**
     * Set the offset
     * 
     * @param int $offset The offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        
        return $this;
    }
    
    /**
     * Set the limit and offset for a given page
     * 
     * @param int $page The page
     * @param int $perPage The per page
     * @return $this
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }
    
    /**
     * Get the SQL representation of the query
     * 
     * @return string
     */
    public function toSql()
    {
        return $this->grammar->compileSelect($this);
    }
    
    /**
     * Execute the query as a "select" statement
     * 
     * @param array|string $columns The columns
     * @return array
     */
    public function get($columns = ['*'])
    {
        if (!empty($columns)) {
            $this->select($columns);
        }
        
        $sql = $this->buildSelectQuery();
        
        $bindings = $this->getBindings();
        
        return $this->connection->select($sql, $bindings);
    }
    
    /**
     * Get a single record
     * 
     * @param array|string $columns The columns
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        if (!empty($columns)) {
            $this->select($columns);
        }
        
        $results = $this->limit(1)->get();
        
        return count($results) > 0 ? $results[0] : null;
    }
    
    /**
     * Get a single column's value from the first result
     * 
     * @param string $column The column
     * @return mixed
     */
    public function value($column)
    {
        $result = $this->first([$column]);
        
        return $result ? $result[$column] : null;
    }
    
    /**
     * Get a single column's value from the first result of a query or throw an exception
     * 
     * @param string $column The column
     * @return mixed
     * @throws \RuntimeException
     */
    public function valueOrFail($column)
    {
        $result = $this->value($column);
        
        if (is_null($result)) {
            throw new \RuntimeException('No results found');
        }
        
        return $result;
    }
    
    /**
     * Execute an aggregate function on the database
     * 
     * @param string $function The function
     * @param array $columns The columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*'])
    {
        $this->aggregate = compact('function', 'columns');
        
        $results = $this->get();
        
        $this->aggregate = null;
        
        if (count($results) > 0) {
            return array_change_key_case((array) $results[0])['aggregate'];
        }
        
        return null;
    }
    
    /**
     * Execute a count query
     * 
     * @param string $column The column
     * @return int
     */
    public function count($column = '*')
    {
        return (int) $this->aggregate('count', [$column]);
    }
    
    /**
     * Execute a max query
     * 
     * @param string $column The column
     * @return mixed
     */
    public function max($column)
    {
        return $this->aggregate('max', [$column]);
    }
    
    /**
     * Execute a min query
     * 
     * @param string $column The column
     * @return mixed
     */
    public function min($column)
    {
        return $this->aggregate('min', [$column]);
    }
    
    /**
     * Execute a sum query
     * 
     * @param string $column The column
     * @return mixed
     */
    public function sum($column)
    {
        return $this->aggregate('sum', [$column]);
    }
    
    /**
     * Execute an avg query
     * 
     * @param string $column The column
     * @return mixed
     */
    public function avg($column)
    {
        return $this->aggregate('avg', [$column]);
    }
    
    /**
     * Insert a new record into the database
     * 
     * @param array $values The values
     * @return bool
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return true;
        }
        
        // If the values are not an array of arrays, we will assume it is
        // an array of key-value pairs and convert it to an array of arrays
        if (!is_array(reset($values))) {
            $values = [$values];
        }
        
        $sql = $this->buildInsertQuery(count($values));
        
        $bindings = [];
        
        foreach ($values as $record) {
            foreach ($record as $value) {
                $bindings[] = $value;
            }
        }
        
        return $this->connection->insert($sql, $bindings);
    }
    
    /**
     * Insert a new record and get the value of the primary key
     * 
     * @param array $values The values
     * @param string|null $sequence The sequence name
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $this->insert($values);
        
        return $this->connection->lastInsertId($sequence);
    }
    
    /**
     * Update a record in the database
     * 
     * @param array $values The values
     * @return int
     */
    public function update(array $values)
    {
        $sql = $this->buildUpdateQuery($values);
        
        $bindings = array_values($values);
        
        $bindings = array_merge($bindings, $this->getBindings()['where']);
        
        return $this->connection->update($sql, $bindings);
    }
    
    /**
     * Delete a record from the database
     * 
     * @param mixed $id The ID
     * @return int
     */
    public function delete($id = null)
    {
        if (!is_null($id)) {
            $this->where('id', '=', $id);
        }
        
        $sql = $this->buildDeleteQuery();
        
        $bindings = $this->getBindings()['where'];
        
        return $this->connection->delete($sql, $bindings);
    }
    
    /**
     * Build the select query
     * 
     * @return string
     */
    protected function buildSelectQuery()
    {
        $sql = 'SELECT ' . implode(', ', $this->columns);
        
        $sql .= ' FROM ' . $this->table;
        
        if (!empty($this->joins)) {
            $sql .= ' ' . $this->buildJoins();
        }
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }
        
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }
        
        if (!empty($this->havings)) {
            $sql .= ' HAVING ' . $this->buildHavings();
        }
        
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . $this->buildOrders();
        }
        
        if (!is_null($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        
        if (!is_null($this->offset)) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        
        return $sql;
    }
    
    /**
     * Build the insert query
     * 
     * @param int $count The count
     * @return string
     */
    protected function buildInsertQuery($count)
    {
        $columns = array_keys(reset($this->bindings['insert']));
        
        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $columns) . ') VALUES ';
        
        $placeholders = [];
        
        for ($i = 0; $i < $count; $i++) {
            $placeholders[] = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        }
        
        $sql .= implode(', ', $placeholders);
        
        return $sql;
    }
    
    /**
     * Build the update query
     * 
     * @param array $values The values
     * @return string
     */
    protected function buildUpdateQuery(array $values)
    {
        $columns = array_keys($values);
        
        $sql = 'UPDATE ' . $this->table . ' SET ';
        
        $sets = [];
        
        foreach ($columns as $column) {
            $sets[] = $column . ' = ?';
        }
        
        $sql .= implode(', ', $sets);
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }
        
        return $sql;
    }
    
    /**
     * Build the delete query
     * 
     * @return string
     */
    protected function buildDeleteQuery()
    {
        $sql = 'DELETE FROM ' . $this->table;
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }
        
        return $sql;
    }
    
    /**
     * Build the where clauses
     * 
     * @return string
     */
    protected function buildWheres()
    {
        $wheres = [];
        
        foreach ($this->wheres as $where) {
            $method = 'build' . $where['type'] . 'Where';
            
            $wheres[] = ($where['boolean'] == 'and' ? 'AND ' : 'OR ') . $this->$method($where);
        }
        
        return ltrim(implode(' ', $wheres), 'AND ')->ltrim('OR ');
    }
    
    /**
     * Build a basic where clause
     * 
     * @param array $where The where clause
     * @return string
     */
    protected function buildBasicWhere(array $where)
    {
        return $where['column'] . ' ' . $where['operator'] . ' ?';
    }
    
    /**
     * Build a nested where clause
     * 
     * @param array $where The where clause
     * @return string
     */
    protected function buildNestedWhere(array $where)
    {
        return '(' . $where['query']->buildWheres() . ')';
    }
    
    /**
     * Build an in where clause
     * 
     * @param array $where The where clause
     * @return string
     */
    protected function buildInWhere(array $where)
    {
        $values = array_fill(0, count($where['values']), '?');
        
        return $where['column'] . ' IN (' . implode(', ', $values) . ')';
    }
    
    /**
     * Build a not in where clause
     * 
     * @param array $where The where clause
     * @return string
     */
    protected function buildNotInWhere(array $where)
    {
        $values = array_fill(0, count($where['values']), '?');
        
        return $where['column'] . ' NOT IN (' . implode(', ', $values) . ')';
    }
    
    /**
     * Build a null where clause
     * 
     * @param array $where The where clause
     * @return string
     */
    protected function buildNullWhere(array $where)
    {
        return $where['column'] . ' IS NULL';
    }
    
    /**
     * Build a not null where clause
     * 
     * @param array $where The where clause
     * @return string
     */
    protected function buildNotNullWhere(array $where)
    {
        return $where['column'] . ' IS NOT NULL';
    }
    
    /**
     * Build a between where clause
     * 
     * @param array $where The where clause
     * @return string
     */
    protected function buildBetweenWhere(array $where)
    {
        return $where['column'] . ' BETWEEN ? AND ?';
    }
    
    /**
     * Build a not between where clause
     * 
     * @param array $where The where clause
     * @return string
     */
    protected function buildNotBetweenWhere(array $where)
    {
        return $where['column'] . ' NOT BETWEEN ? AND ?';
    }
    
    /**
     * Build a date where clause
     * 
     * @param array $where The where clause
     * @return string
     */
    protected function buildDateWhere(array $where)
    {
        return 'DATE(' . $where['column'] . ') ' . $where['operator'] . ' ?';
    }
    
    /**
     * Build the join clauses
     * 
     * @return string
     */
    protected function buildJoins()
    {
        $joins = [];
        
        foreach ($this->joins as $join) {
            $table = $join->table;
            
            $type = strtoupper($join->type);
            
            $clauses = [];
            
            foreach ($join->clauses as $clause) {
                $clauses[] = $clause['boolean'] . ' ' . $clause['first'] . ' ' . $clause['operator'] . ' ' . $clause['second'];
            }
            
            $joins[] = $type . ' JOIN ' . $table . ' ON ' . ltrim(implode(' ', $clauses), 'AND ')->ltrim('OR ');
        }
        
        return implode(' ', $joins);
    }
    
    /**
     * Build the having clauses
     * 
     * @return string
     */
    protected function buildHavings()
    {
        $havings = [];
        
        foreach ($this->havings as $having) {
            $havings[] = ($having['boolean'] == 'and' ? 'AND ' : 'OR ') . $having['column'] . ' ' . $having['operator'] . ' ?';
        }
        
        return ltrim(implode(' ', $havings), 'AND ')->ltrim('OR ');
    }
    
    /**
     * Build the order by clauses
     * 
     * @return string
     */
    protected function buildOrders()
    {
        $orders = [];
        
        foreach ($this->orders as $order) {
            $orders[] = $order['column'] . ' ' . strtoupper($order['direction']);
        }
        
        return implode(', ', $orders);
    }
    
    /**
     * Add a binding to the query
     * 
     * @param mixed $value The value
     * @param string $type The type
     * @return $this
     */
    public function addBinding($value, $type = 'where')
    {
        if (is_array($value)) {
            $this->bindings[$type] = array_merge($this->bindings[$type], $value);
        } else {
            $this->bindings[$type][] = $value;
        }
        
        return $this;
    }
    
    /**
     * Get the current query bindings
     * 
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
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
     * Get the database connection
     * 
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }
}