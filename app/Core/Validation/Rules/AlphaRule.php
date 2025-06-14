<?php

namespace App\Core\Validation\Rules;

/**
 * Alpha Rule
 * 
 * Validates that a field contains only alphabetic characters.
 */
class AlphaRule extends AbstractRule
{
    /**
     * Rule name
     * 
     * @var string
     */
    protected string $name = 'alpha';
    
    /**
     * Default error message
     * 
     * @var string
     */
    protected string $message = 'The :attribute field must contain only letters.';
    
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
        
        // Convert to string if not already
        $value = (string)$value;
        
        // Check if value contains only alphabetic characters
        return preg_match('/^[a-zA-Z]+$/', $value) === 1;
    }
}