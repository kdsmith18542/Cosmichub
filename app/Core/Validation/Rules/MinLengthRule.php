<?php

namespace App\Core\Validation\Rules;

/**
 * Min Length Rule
 * 
 * Validates that a field has a minimum length.
 */
class MinLengthRule extends AbstractRule
{
    /**
     * Rule name
     * 
     * @var string
     */
    protected string $name = 'min_length';
    
    /**
     * Default error message
     * 
     * @var string
     */
    protected string $message = 'The :attribute field must be at least :param characters.';
    
    /**
     * Rule requirements
     * 
     * @var array
     */
    protected array $requirements = ['min_length'];
    
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
        
        // Check if minimum length parameter is provided
        if (empty($parameters[0])) {
            return false;
        }
        
        $minLength = (int)$parameters[0];
        $size = $this->getSize($value);
        
        return $size >= $minLength;
    }
    
    /**
     * Get the validation error message
     * 
     * @param string $field Field name
     * @param mixed $value Value that failed validation
     * @param array $parameters Rule parameters
     * @return string
     */
    public function getMessage(string $field, $value, array $parameters): string
    {
        $message = str_replace(':attribute', $field, $this->message);
        $message = str_replace(':param', $parameters[0] ?? '0', $message);
        
        return $message;
    }
}