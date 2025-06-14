<?php

namespace App\Core\Validation\Rules;

/**
 * Integer Rule
 * 
 * Validates that a field contains an integer value.
 */
class IntegerRule extends AbstractRule
{
    /**
     * Rule name
     * 
     * @var string
     */
    protected string $name = 'integer';
    
    /**
     * Default error message
     * 
     * @var string
     */
    protected string $message = 'The :attribute field must be an integer.';
    
    /**
     * Validate the given value
     * 
     * @param string $field Field name
     * @param mixed $value Value to validate
     * @param array $parameters Rule parameters
     * @param array $data All validation data
     * @return bool
     */
    public function validate(string $field, $value, array $parameters, array $data): bool
    {
        // Skip validation if value is empty (use required rule for that)
        if ($this->isEmpty($value)) {
            return true;
        }
        
        // Check if value is an integer
        if (is_int($value)) {
            return true;
        }
        
        // Check if string value represents an integer
        if (is_string($value) && ctype_digit(ltrim($value, '-'))) {
            return true;
        }
        
        // Check using filter_var
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
}