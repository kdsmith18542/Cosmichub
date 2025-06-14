<?php

namespace App\Exceptions;

use Exception;

/**
 * Base exception class for the application
 */
class BaseException extends Exception
{
    /**
     * @var array Additional context data
     */
    protected $context = [];
    
    /**
     * @var string The exception type
     */
    protected $type = 'error';
    
    /**
     * @var int HTTP status code
     */
    protected $statusCode = 500;
    
    /**
     * Constructor
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param Exception|null $previous The previous exception
     * @param array $context Additional context data
     */
    public function __construct($message = '', $code = 0, Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
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
     * Set the exception context
     * 
     * @param array $context The context data
     * @return $this
     */
    public function setContext(array $context)
    {
        $this->context = $context;
        return $this;
    }
    
    /**
     * Add context data
     * 
     * @param string $key The context key
     * @param mixed $value The context value
     * @return $this
     */
    public function addContext($key, $value)
    {
        $this->context[$key] = $value;
        return $this;
    }
    
    /**
     * Get the exception type
     * 
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * Set the exception type
     * 
     * @param string $type The exception type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    
    /**
     * Get the HTTP status code
     * 
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    /**
     * Set the HTTP status code
     * 
     * @param int $statusCode The HTTP status code
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    
    /**
     * Convert the exception to an array
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'type' => $this->type,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'status_code' => $this->statusCode
        ];
    }
    
    /**
     * Convert the exception to JSON
     * 
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
    
    /**
     * Get a user-friendly error message
     * 
     * @return string
     */
    public function getUserMessage()
    {
        return $this->getMessage() ?: 'An error occurred';
    }
    
    /**
     * Check if this is a client error (4xx)
     * 
     * @return bool
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }
    
    /**
     * Check if this is a server error (5xx)
     * 
     * @return bool
     */
    public function isServerError()
    {
        return $this->statusCode >= 500;
    }
    
    /**
     * Check if this exception should be reported
     * 
     * @return bool
     */
    public function shouldReport()
    {
        return $this->isServerError();
    }
    
    /**
     * Get the exception as a string
     * 
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            '%s: %s in %s:%d',
            get_class($this),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine()
        );
    }
}