<?php

namespace App\Core\Validation\Rules;

/**
 * Required Rule
 * 
 * Validates that a field is present and not empty.
 */
class RequiredRule extends AbstractRule
{
    /**
     * Rule name
     * 
     * @var string
     */
    protected string $name = 'required';
    
    /**
     * Default error message
     * 
     * @var string
     */
    protected string $message = 'The :attribute field is required.';
    
    /**
     * Whether this rule should stop validation on failure
     * 
     * @var bool
     */
    protected bool $stopOnFailure = true;
    
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
        // Check if field exists in data
        if (!array_key_exists($field, $data)) {
            return false;
        }
        
        // Check if value is empty
        if ($this->isEmpty($value)) {
            return false;
        }
        
        // Special handling for uploaded files
        if (is_array($value) && isset($value['tmp_name'])) {
            return !empty($value['tmp_name']) && $value['error'] === UPLOAD_ERR_OK;
        }
        
        return true;
    }
}