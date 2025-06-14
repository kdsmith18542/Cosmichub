<?php

namespace App\Core\Validation\Rules;

/**
 * Numeric Rule
 * 
 * Validates that a field contains a numeric value.
 */
class NumericRule extends AbstractRule
{
    /**
     * Rule name
     * 
     * @var string
     */
    protected string $name = 'numeric';
    
    /**
     * Default error message
     * 
     * @var string
     */
    protected string $message = 'The :attribute field must be a number.';
    
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
        
        // Check if value is numeric
        return is_numeric($value);
    }
}