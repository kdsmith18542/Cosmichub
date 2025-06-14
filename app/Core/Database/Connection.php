<?php

namespace App\Core\Database;

use PDO;
use PDOStatement;
use PDOException;

/**
 * Connection class for database connections
 */
class Connection implements ConnectionInterface
{
    /**
     * @var PDO The PDO instance
     */
    protected $pdo;
    
    /**
     * @var array The connection configuration
     */
    protected $config;
    
    /**
     * @var int The transaction count
     */
    protected $transactions = 0;
    
    /**
     * @var array The query log
     */
    protected $queryLog = [];
    
    /**
     * @var bool Whether to log queries
     */
    protected $loggingQueries = false;
    
    /**
     * Create a new connection instance
     * 
     * @param PDO $pdo The PDO instance
     * @param array $config The connection configuration
     */
    public function __construct(PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction()
    {
        if ($this->transactions == 0) {
            $this->pdo->beginTransaction();
        }
        
        $this->transactions++;
        
        return true;
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->pdo->commit();
        }
        
        $this->transactions = max(0, $this->transactions - 1);
        
        return true;
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollBack()
    {
        if ($this->transactions == 1) {
            $this->pdo->rollBack();
        }
        
        $this->transactions = max(0, $this->transactions - 1);
        
        return true;
    }
    
    /**
     * Get the transaction count
     * 
     * @return int
     */
    public function transactionLevel()
    {
        return $this->transactions;
    }
    
    /**
     * Execute a query
     * 
     * @param string $query The query
     * @param array $bindings The query bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->prepare($query);
            
            $this->bindValues($statement, $bindings);
            
            return $statement->execute();
        });
    }
    
    /**
     * Run a select query
     * 
     * @param string $query The query
     * @param array $bindings The query bindings
     * @param bool $useReadPdo Whether to use the read PDO
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->prepare($query);
            
            $this->bindValues($statement, $bindings);
            
            $statement->execute();
            
            return $statement->fetchAll();
        });
    }
    
    /**
     * Run a select query and return a single result
     * 
     * @param string $query The query
     * @param array $bindings The query bindings
     * @param bool $useReadPdo Whether to use the read PDO
     * @return mixed
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $records = $this->select($query, $bindings, $useReadPdo);
        
        return array_shift($records);
    }
    
    /**
     * Run an insert query
     * 
     * @param string $query The query
     * @param array $bindings The query bindings
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }
    
    /**
     * Run an update query
     * 
     * @param string $query The query
     * @param array $bindings The query bindings
     * @return int
     */
    public function update($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }
    
    /**
     * Run a delete query
     * 
     * @param string $query The query
     * @param array $bindings The query bindings
     * @return int
     */
    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }
    
    /**
     * Run a query that returns the number of affected rows
     * 
     * @param string $query The query
     * @param array $bindings The query bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->prepare($query);
            
            $this->bindValues($statement, $bindings);
            
            $statement->execute();
            
            return $statement->rowCount();
        });
    }
    
    /**
     * Run a raw, unprepared query
     * 
     * @param string $query The query
     * @return bool
     */
    public function unprepared($query)
    {
        return $this->run($query, [], function ($query) {
            return (bool) $this->pdo->exec($query);
        });
    }
    
    /**
     * Prepare a query for execution
     * 
     * @param string $query The query
     * @return PDOStatement
     */
    public function prepare($query)
    {
        return $this->pdo->prepare($query);
    }
    
    /**
     * Bind values to a statement
     * 
     * @param PDOStatement $statement The statement
     * @param array $bindings The bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $param = is_string($key) ? $key : $key + 1;
            
            $type = PDO::PARAM_STR;
            
            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $type = PDO::PARAM_NULL;
            }
            
            $statement->bindValue($param, $value, $type);
        }
    }
    
    /**
     * Run a query
     * 
     * @param string $query The query
     * @param array $bindings The query bindings
     * @param callable $callback The query callback
     * @return mixed
     * @throws PDOException
     */
    protected function run($query, $bindings, callable $callback)
    {
        $start = microtime(true);
        
        try {
            $result = $callback($query, $bindings);
            
            $this->logQuery($query, $bindings, $start);
            
            return $result;
        } catch (PDOException $e) {
            throw new PDOException(
                "Error executing query: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Log a query
     * 
     * @param string $query The query
     * @param array $bindings The query bindings
     * @param float $start The query start time
     * @return void
     */
    protected function logQuery($query, $bindings, $start)
    {
        if ($this->loggingQueries) {
            $time = round((microtime(true) - $start) * 1000, 2);
            
            $this->queryLog[] = compact('query', 'bindings', 'time');
        }
    }
    
    /**
     * Get the last insert ID
     * 
     * @param string|null $name The sequence name
     * @return string
     */
    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }
    
    /**
     * Get the query log
     * 
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }
    
    /**
     * Clear the query log
     * 
     * @return void
     */
    public function clearQueryLog()
    {
        $this->queryLog = [];
    }
    
    /**
     * Enable query logging
     * 
     * @return void
     */
    public function enableQueryLog()
    {
        $this->loggingQueries = true;
    }
    
    /**
     * Disable query logging
     * 
     * @return void
     */
    public function disableQueryLog()
    {
        $this->loggingQueries = false;
    }
    
    /**
     * Check if query logging is enabled
     * 
     * @return bool
     */
    public function loggingQueries()
    {
        return $this->loggingQueries;
    }
    
    /**
     * Get the PDO instance
     * 
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }
    
    /**
     * Get the connection configuration
     * 
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}