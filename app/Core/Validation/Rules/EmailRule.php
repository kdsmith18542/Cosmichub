<?php

namespace App\Core\Validation\Rules;

/**
 * Email Rule
 * 
 * Validates that a field contains a valid email address.
 */
class EmailRule extends AbstractRule
{
    /**
     * Rule name
     * 
     * @var string
     */
    protected string $name = 'email';
    
    /**
     * Default error message
     * 
     * @var string
     */
    protected string $message = 'The :attribute field must be a valid email address.';
    
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
        
        // Validate email format
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Additional validation based on parameters
        if (in_array('strict', $parameters)) {
            return $this->validateStrict($value);
        }
        
        if (in_array('dns', $parameters)) {
            return $this->validateDns($value);
        }
        
        return true;
    }
    
    /**
     * Perform strict email validation
     * 
     * @param string $email
     * @return bool
     */
    protected function validateStrict(string $email): bool
    {
        // More strict validation
        $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        return preg_match($pattern, $email) === 1;
    }
    
    /**
     * Validate email domain via DNS
     * 
     * @param string $email
     * @return bool
     */
    protected function validateDns(string $email): bool
    {
        $domain = substr(strrchr($email, '@'), 1);
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }
}