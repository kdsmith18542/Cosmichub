<?php

namespace App\Core\Exceptions;

use Exception;
use PDOException;

/**
 * Database Exception
 * 
 * Handles database-specific errors and exceptions
 */
class DatabaseException extends Exception
{
    /**
     * @var string SQL query that caused the error
     */
    protected $sql;
    
    /**
     * @var array Query bindings
     */
    protected $bindings = [];
    
    /**
     * @var string Database connection name
     */
    protected $connectionName;
    
    /**
     * @var array Additional context data
     */
    protected $context = [];
    
    /**
     * Create a new database exception
     * 
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param string|null $sql
     * @param array $bindings
     * @param string|null $connectionName
     */
    public function __construct(
        $message = '',
        $code = 0,
        Exception $previous = null,
        $sql = null,
        array $bindings = [],
        $connectionName = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->connectionName = $connectionName;
    }
    
    /**
     * Create exception from PDO exception
     * 
     * @param PDOException $e
     * @param string|null $sql
     * @param array $bindings
     * @param string|null $connectionName
     * @return static
     */
    public static function fromPDOException(
        PDOException $e,
        $sql = null,
        array $bindings = [],
        $connectionName = null
    ) {
        $message = static::formatPDOMessage($e);
        
        return new static(
            $message,
            (int) $e->getCode(),
            $e,
            $sql,
            $bindings,
            $connectionName
        );
    }
    
    /**
     * Create exception for connection failure
     * 
     * @param string $connectionName
     * @param Exception $previous
     * @return static
     */
    public static function connectionFailed($connectionName, Exception $previous = null)
    {
        $message = "Failed to connect to database '{$connectionName}'";
        
        return new static($message, 1001, $previous, null, [], $connectionName);
    }
    
    /**
     * Create exception for query execution failure
     * 
     * @param string $sql
     * @param array $bindings
     * @param Exception $previous
     * @param string|null $connectionName
     * @return static
     */
    public static function queryFailed(
        $sql,
        array $bindings = [],
        Exception $previous = null,
        $connectionName = null
    ) {
        $message = 'Query execution failed';
        
        if ($previous) {
            $message .= ': ' . $previous->getMessage();
        }
        
        return new static($message, 1002, $previous, $sql, $bindings, $connectionName);
    }
    
    /**
     * Create exception for transaction failure
     * 
     * @param string $operation
     * @param Exception $previous
     * @param string|null $connectionName
     * @return static
     */
    public static function transactionFailed(
        $operation,
        Exception $previous = null,
        $connectionName = null
    ) {
        $message = "Transaction {$operation} failed";
        
        if ($previous) {
            $message .= ': ' . $previous->getMessage();
        }
        
        return new static($message, 1003, $previous, null, [], $connectionName);
    }
    
    /**
     * Create exception for constraint violation
     * 
     * @param string $constraint
     * @param string $sql
     * @param array $bindings
     * @param string|null $connectionName
     * @return static
     */
    public static function constraintViolation(
        $constraint,
        $sql = null,
        array $bindings = [],
        $connectionName = null
    ) {
        $message = "Constraint violation: {$constraint}";
        
        return new static($message, 1004, null, $sql, $bindings, $connectionName);
    }
    
    /**
     * Create exception for duplicate entry
     * 
     * @param string $key
     * @param string $sql
     * @param array $bindings
     * @param string|null $connectionName
     * @return static
     */
    public static function duplicateEntry(
        $key,
        $sql = null,
        array $bindings = [],
        $connectionName = null
    ) {
        $message = "Duplicate entry for key: {$key}";
        
        return new static($message, 1005, null, $sql, $bindings, $connectionName);
    }
    
    /**
     * Create exception for table not found
     * 
     * @param string $table
     * @param string|null $connectionName
     * @return static
     */
    public static function tableNotFound($table, $connectionName = null)
    {
        $message = "Table '{$table}' not found";
        
        return new static($message, 1006, null, null, [], $connectionName);
    }
    
    /**
     * Create exception for column not found
     * 
     * @param string $column
     * @param string $table
     * @param string|null $connectionName
     * @return static
     */
    public static function columnNotFound($column, $table = null, $connectionName = null)
    {
        $message = "Column '{$column}' not found";
        
        if ($table) {
            $message .= " in table '{$table}'";
        }
        
        return new static($message, 1007, null, null, [], $connectionName);
    }
    
    /**
     * Format PDO exception message
     * 
     * @param PDOException $e
     * @return string
     */
    protected static function formatPDOMessage(PDOException $e)
    {
        $message = $e->getMessage();
        
        // Extract useful information from PDO error info
        if ($e->errorInfo) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null;
            $driverMessage = $e->errorInfo[2] ?? null;
            
            if ($driverMessage && $driverMessage !== $message) {
                $message = $driverMessage;
            }
            
            // Add SQL state if available
            if ($sqlState) {
                $message .= " (SQLSTATE: {$sqlState})";
            }
        }
        
        return $message;
    }
    
    /**
     * Get the SQL query
     * 
     * @return string|null
     */
    public function getSql()
    {
        return $this->sql;
    }
    
    /**
     * Get the query bindings
     * 
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }
    
    /**
     * Get the connection name
     * 
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }
    
    /**
     * Get the exception context
     * 
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
    
    /**
     * Set additional context
     * 
     * @param array $context
     * @return $this
     */
    public function setContext(array $context)
    {
        $this->context = array_merge($this->context, $context);
        
        return $this;
    }
    
    /**
     * Get formatted SQL with bindings
     * 
     * @return string|null
     */
    public function getFormattedSql()
    {
        if (!$this->sql) {
            return null;
        }
        
        $sql = $this->sql;
        
        foreach ($this->bindings as $binding) {
            $value = is_string($binding) ? "'{$binding}'" : $binding;
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }
        
        return $sql;
    }
    
    /**
     * Convert to array for logging
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'sql' => $this->sql,
            'bindings' => $this->bindings,
            'formatted_sql' => $this->getFormattedSql(),
            'connection' => $this->connectionName,
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ];
    }
    
    /**
     * Check if this is a connection error
     * 
     * @return bool
     */
    public function isConnectionError()
    {
        return $this->getCode() === 1001;
    }
    
    /**
     * Check if this is a constraint violation
     * 
     * @return bool
     */
    public function isConstraintViolation()
    {
        return in_array($this->getCode(), [1004, 1005]);
    }
    
    /**
     * Check if this is a schema error (table/column not found)
     * 
     * @return bool
     */
    public function isSchemaError()
    {
        return in_array($this->getCode(), [1006, 1007]);
    }
}