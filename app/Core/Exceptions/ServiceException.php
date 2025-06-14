<?php

namespace App\Core\Exceptions;

use Exception;
use Throwable;

/**
 * Service Exception Class
 * 
 * Handles service-specific errors with detailed context and error types
 */
class ServiceException extends Exception
{
    /**
     * @var array Additional context data
     */
    protected $context = [];
    
    /**
     * @var string Error type
     */
    protected $errorType;
    
    /**
     * @var array Validation errors (if applicable)
     */
    protected $validationErrors = [];
    
    /**
     * Create a new service exception instance
     * 
     * @param string $message
     * @param string $errorType
     * @param array $context
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        $message = '',
        $errorType = 'service_error',
        array $context = [],
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->errorType = $errorType;
        $this->context = $context;
    }
    
    /**
     * Create a validation failed exception
     * 
     * @param string $message
     * @param array $errors
     * @param array $context
     * @return static
     */
    public static function validationFailed($message = 'Validation failed', array $errors = [], array $context = [])
    {
        $exception = new static($message, 'validation_failed', $context, 422);
        $exception->validationErrors = $errors;
        
        return $exception;
    }
    
    /**
     * Create an operation failed exception
     * 
     * @param string $message
     * @param Throwable|null $previous
     * @param array $context
     * @return static
     */
    public static function operationFailed($message = 'Operation failed', Throwable $previous = null, array $context = [])
    {
        return new static($message, 'operation_failed', $context, 500, $previous);
    }
    
    /**
     * Create a transaction failed exception
     * 
     * @param string $message
     * @param Throwable|null $previous
     * @param array $context
     * @return static
     */
    public static function transactionFailed($message = 'Transaction failed', Throwable $previous = null, array $context = [])
    {
        return new static($message, 'transaction_failed', $context, 500, $previous);
    }
    
    /**
     * Create a method not found exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function methodNotFound($message = 'Method not found', array $context = [])
    {
        return new static($message, 'method_not_found', $context, 404);
    }
    
    /**
     * Create an unauthorized access exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function unauthorized($message = 'Unauthorized access', array $context = [])
    {
        return new static($message, 'unauthorized', $context, 401);
    }
    
    /**
     * Create a forbidden access exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function forbidden($message = 'Forbidden access', array $context = [])
    {
        return new static($message, 'forbidden', $context, 403);
    }
    
    /**
     * Create a resource not found exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function notFound($message = 'Resource not found', array $context = [])
    {
        return new static($message, 'not_found', $context, 404);
    }
    
    /**
     * Create a conflict exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function conflict($message = 'Resource conflict', array $context = [])
    {
        return new static($message, 'conflict', $context, 409);
    }
    
    /**
     * Create a rate limit exceeded exception
     * 
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function rateLimitExceeded($message = 'Rate limit exceeded', array $context = [])
    {
        return new static($message, 'rate_limit_exceeded', $context, 429);
    }
    
    /**
     * Create an external service error exception
     * 
     * @param string $message
     * @param string $service
     * @param Throwable|null $previous
     * @param array $context
     * @return static
     */
    public static function externalServiceError($message = 'External service error', $service = '', Throwable $previous = null, array $context = [])
    {
        $context['external_service'] = $service;
        
        return new static($message, 'external_service_error', $context, 502, $previous);
    }
    
    /**
     * Create a timeout exception
     * 
     * @param string $message
     * @param int $timeout
     * @param array $context
     * @return static
     */
    public static function timeout($message = 'Operation timeout', $timeout = 0, array $context = [])
    {
        $context['timeout'] = $timeout;
        
        return new static($message, 'timeout', $context, 408);
    }
    
    /**
     * Create an invalid configuration exception
     * 
     * @param string $message
     * @param string $configKey
     * @param array $context
     * @return static
     */
    public static function invalidConfiguration($message = 'Invalid configuration', $configKey = '', array $context = [])
    {
        $context['config_key'] = $configKey;
        
        return new static($message, 'invalid_configuration', $context, 500);
    }
    
    /**
     * Create a dependency injection exception
     * 
     * @param string $message
     * @param string $dependency
     * @param array $context
     * @return static
     */
    public static function dependencyInjection($message = 'Dependency injection failed', $dependency = '', array $context = [])
    {
        $context['dependency'] = $dependency;
        
        return new static($message, 'dependency_injection', $context, 500);
    }
    
    /**
     * Create a cache error exception
     * 
     * @param string $message
     * @param string $operation
     * @param Throwable|null $previous
     * @param array $context
     * @return static
     */
    public static function cacheError($message = 'Cache operation failed', $operation = '', Throwable $previous = null, array $context = [])
    {
        $context['cache_operation'] = $operation;
        
        return new static($message, 'cache_error', $context, 500, $previous);
    }
    
    /**
     * Create a serialization error exception
     * 
     * @param string $message
     * @param mixed $data
     * @param Throwable|null $previous
     * @param array $context
     * @return static
     */
    public static function serializationError($message = 'Serialization failed', $data = null, Throwable $previous = null, array $context = [])
    {
        $context['data_type'] = gettype($data);
        
        return new static($message, 'serialization_error', $context, 500, $previous);
    }
    
    /**
     * Create a business logic violation exception
     * 
     * @param string $message
     * @param string $rule
     * @param array $context
     * @return static
     */
    public static function businessRuleViolation($message = 'Business rule violation', $rule = '', array $context = [])
    {
        $context['business_rule'] = $rule;
        
        return new static($message, 'business_rule_violation', $context, 422);
    }
    
    /**
     * Get the error type
     * 
     * @return string
     */
    public function getErrorType()
    {
        return $this->errorType;
    }
    
    /**
     * Get the context data
     * 
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }
    
    /**
     * Set context data
     * 
     * @param array $context
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
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addContext($key, $value)
    {
        $this->context[$key] = $value;
        return $this;
    }
    
    /**
     * Set validation errors
     * 
     * @param array $errors
     * @return $this
     */
    public function setValidationErrors(array $errors)
    {
        $this->validationErrors = $errors;
        return $this;
    }
    
    /**
     * Check if this is a validation error
     * 
     * @return bool
     */
    public function isValidationError()
    {
        return $this->errorType === 'validation_failed';
    }
    
    /**
     * Check if this is an authorization error
     * 
     * @return bool
     */
    public function isAuthorizationError()
    {
        return in_array($this->errorType, ['unauthorized', 'forbidden']);
    }
    
    /**
     * Check if this is a client error (4xx)
     * 
     * @return bool
     */
    public function isClientError()
    {
        return $this->code >= 400 && $this->code < 500;
    }
    
    /**
     * Check if this is a server error (5xx)
     * 
     * @return bool
     */
    public function isServerError()
    {
        return $this->code >= 500 && $this->code < 600;
    }
    
    /**
     * Get HTTP status code
     * 
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->code ?: 500;
    }
    
    /**
     * Convert the exception to an array
     * 
     * @return array
     */
    public function toArray()
    {
        $data = [
            'error' => true,
            'type' => $this->errorType,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context
        ];
        
        if (!empty($this->validationErrors)) {
            $data['validation_errors'] = $this->validationErrors;
        }
        
        if ($this->getPrevious()) {
            $data['previous'] = [
                'message' => $this->getPrevious()->getMessage(),
                'code' => $this->getPrevious()->getCode(),
                'file' => $this->getPrevious()->getFile(),
                'line' => $this->getPrevious()->getLine()
            ];
        }
        
        return $data;
    }
    
    /**
     * Convert the exception to JSON
     * 
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * Get a user-friendly error message
     * 
     * @return string
     */
    public function getUserMessage()
    {
        switch ($this->errorType) {
            case 'validation_failed':
                return 'The provided data is invalid. Please check your input and try again.';
            case 'unauthorized':
                return 'You are not authorized to perform this action. Please log in and try again.';
            case 'forbidden':
                return 'You do not have permission to perform this action.';
            case 'not_found':
                return 'The requested resource could not be found.';
            case 'conflict':
                return 'The request conflicts with the current state of the resource.';
            case 'rate_limit_exceeded':
                return 'Too many requests. Please wait a moment and try again.';
            case 'timeout':
                return 'The operation took too long to complete. Please try again.';
            case 'external_service_error':
                return 'An external service is currently unavailable. Please try again later.';
            case 'business_rule_violation':
                return 'The operation violates business rules and cannot be completed.';
            default:
                return 'An error occurred while processing your request. Please try again.';
        }
    }
    
    /**
     * String representation of the exception
     * 
     * @return string
     */
    public function __toString()
    {
        $string = parent::__toString();
        
        if (!empty($this->context)) {
            $string .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        
        if (!empty($this->validationErrors)) {
            $string .= "\nValidation Errors: " . json_encode($this->validationErrors, JSON_PRETTY_PRINT);
        }
        
        return $string;
    }
}