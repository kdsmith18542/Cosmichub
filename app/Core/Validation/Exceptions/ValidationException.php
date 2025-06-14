<?php

namespace App\Core\Validation\Exceptions;

use Exception;
use Throwable;

/**
 * Validation Exception
 * 
 * Thrown when validation fails.
 */
class ValidationException extends Exception
{
    /**
     * Validation errors
     * 
     * @var array
     */
    protected array $errors;
    
    /**
     * Validated data
     * 
     * @var array
     */
    protected array $validatedData;
    
    /**
     * Create a new validation exception
     * 
     * @param array $errors Validation errors
     * @param array $validatedData Validated data
     * @param string $message Exception message
     * @param int $code Exception code
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        array $errors = [],
        array $validatedData = [],
        string $message = 'Validation failed',
        int $code = 422,
        ?Throwable $previous = null
    ) {
        $this->errors = $errors;
        $this->validatedData = $validatedData;
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get validated data
     * 
     * @return array
     */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }
    
    /**
     * Get the first error message
     * 
     * @param string|null $field Specific field to get error for
     * @return string|null
     */
    public function getFirstError(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }
        
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        
        return null;
    }
    
    /**
     * Check if there are errors for a specific field
     * 
     * @param string $field
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
    
    /**
     * Get errors for a specific field
     * 
     * @param string $field
     * @return array
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Convert to array format
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'validated_data' => $this->validatedData,
            'code' => $this->getCode()
        ];
    }
    
    /**
     * Convert to JSON format
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}