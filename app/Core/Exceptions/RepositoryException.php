<?php

namespace App\Core\Exceptions;

use Exception;

/**
 * Repository Exception
 * 
 * Handles repository-specific errors and exceptions
 */
class RepositoryException extends Exception
{
    /**
     * @var array Additional context data
     */
    protected $context = [];
    
    /**
     * Create a new repository exception
     * 
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param array $context
     */
    public function __construct($message = '', $code = 0, Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    /**
     * Create exception for record not found
     * 
     * @param mixed $id
     * @param string $repository
     * @return static
     */
    public static function notFound($id, $repository = null)
    {
        $message = "Record with ID '{$id}' not found";
        
        if ($repository) {
            $message .= " in {$repository}";
        }
        
        return new static($message, 404, null, [
            'id' => $id,
            'repository' => $repository
        ]);
    }
    
    /**
     * Create exception for validation errors
     * 
     * @param array $errors
     * @param string $repository
     * @return static
     */
    public static function validationFailed(array $errors, $repository = null)
    {
        $message = 'Validation failed';
        
        if ($repository) {
            $message .= " in {$repository}";
        }
        
        return new static($message, 422, null, [
            'errors' => $errors,
            'repository' => $repository
        ]);
    }
    
    /**
     * Create exception for database errors
     * 
     * @param string $operation
     * @param Exception $previous
     * @param string $repository
     * @return static
     */
    public static function databaseError($operation, Exception $previous, $repository = null)
    {
        $message = "Database error during {$operation}";
        
        if ($repository) {
            $message .= " in {$repository}";
        }
        
        return new static($message, 500, $previous, [
            'operation' => $operation,
            'repository' => $repository,
            'database_error' => $previous->getMessage()
        ]);
    }
    
    /**
     * Create exception for unauthorized access
     * 
     * @param string $operation
     * @param string $repository
     * @return static
     */
    public static function unauthorized($operation, $repository = null)
    {
        $message = "Unauthorized access to {$operation}";
        
        if ($repository) {
            $message .= " in {$repository}";
        }
        
        return new static($message, 403, null, [
            'operation' => $operation,
            'repository' => $repository
        ]);
    }
    
    /**
     * Create exception for invalid data
     * 
     * @param string $field
     * @param mixed $value
     * @param string $repository
     * @return static
     */
    public static function invalidData($field, $value, $repository = null)
    {
        $message = "Invalid data for field '{$field}'";
        
        if ($repository) {
            $message .= " in {$repository}";
        }
        
        return new static($message, 400, null, [
            'field' => $field,
            'value' => $value,
            'repository' => $repository
        ]);
    }
    
    /**
     * Create exception for duplicate entries
     * 
     * @param string $field
     * @param mixed $value
     * @param string $repository
     * @return static
     */
    public static function duplicate($field, $value, $repository = null)
    {
        $message = "Duplicate entry for field '{$field}' with value '{$value}'";
        
        if ($repository) {
            $message .= " in {$repository}";
        }
        
        return new static($message, 409, null, [
            'field' => $field,
            'value' => $value,
            'repository' => $repository
        ]);
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
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ];
    }
}