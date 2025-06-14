<?php

namespace App\Exceptions;

/**
 * Exception thrown when validation fails
 */
class ValidationException extends BaseException
{
    /**
     * @var array Validation errors
     */
    protected $errors = [];
    
    /**
     * @var string The exception type
     */
    protected $type = 'validation_error';
    
    /**
     * @var int HTTP status code
     */
    protected $statusCode = 422;
    
    /**
     * Constructor
     * 
     * @param string $message The exception message
     * @param array $errors Validation errors
     * @param int $code The exception code
     * @param \Exception|null $previous The previous exception
     */
    public function __construct($message = 'Validation failed', array $errors = [], $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Set validation errors
     * 
     * @param array $errors The validation errors
     * @return $this
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }
    
    /**
     * Add a validation error
     * 
     * @param string $field The field name
     * @param string $message The error message
     * @return $this
     */
    public function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
        return $this;
    }
    
    /**
     * Check if there are errors for a specific field
     * 
     * @param string $field The field name
     * @return bool
     */
    public function hasError($field)
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
    
    /**
     * Get errors for a specific field
     * 
     * @param string $field The field name
     * @return array
     */
    public function getFieldErrors($field)
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Get the first error for a specific field
     * 
     * @param string $field The field name
     * @return string|null
     */
    public function getFirstError($field)
    {
        $errors = $this->getFieldErrors($field);
        return !empty($errors) ? $errors[0] : null;
    }
    
    /**
     * Convert the exception to an array
     * 
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['errors'] = $this->errors;
        return $array;
    }
    
    /**
     * Get a user-friendly error message
     * 
     * @return string
     */
    public function getUserMessage()
    {
        if (!empty($this->errors)) {
            $firstField = array_keys($this->errors)[0];
            $firstError = $this->getFirstError($firstField);
            return $firstError ?: $this->getMessage();
        }
        
        return parent::getUserMessage();
    }
    
    /**
     * Check if this exception should be reported
     * 
     * @return bool
     */
    public function shouldReport()
    {
        return false; // Validation errors are usually not reported
    }
}