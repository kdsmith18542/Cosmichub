<?php

namespace App\Core\Traits;

use App\Exceptions\ValidationException;

/**
 * Trait for adding validation capabilities to classes
 */
trait Validatable
{
    /**
     * Validate input data against rules
     * 
     * @param array $data The data to validate
     * @param array $rules The validation rules
     * @param array $messages Custom error messages
     * @return array The validated data
     * @throws ValidationException
     */
    protected function validate(array $data, array $rules, array $messages = [])
    {
        $errors = [];
        $validated = [];
        
        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $data[$field] ?? null;
            $fieldErrors = [];
            
            foreach ($fieldRules as $rule) {
                $ruleName = $rule;
                $ruleParams = [];
                
                // Parse rule parameters (e.g., 'min:3' -> rule='min', params=['3'])
                if (strpos($rule, ':') !== false) {
                    [$ruleName, $paramString] = explode(':', $rule, 2);
                    $ruleParams = explode(',', $paramString);
                }
                
                $error = $this->validateRule($field, $value, $ruleName, $ruleParams, $data);
                
                if ($error) {
                    $customMessage = $messages["{$field}.{$ruleName}"] ?? $messages[$field] ?? null;
                    $fieldErrors[] = $customMessage ?: $error;
                }
            }
            
            if (!empty($fieldErrors)) {
                $errors[$field] = $fieldErrors;
            } else {
                $validated[$field] = $value;
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }
        
        return $validated;
    }
    
    /**
     * Validate a single rule
     * 
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @param array $params
     * @param array $data
     * @return string|null Error message or null if valid
     */
    protected function validateRule($field, $value, $rule, $params, $data)
    {
        switch ($rule) {
            case 'required':
                return $this->validateRequired($field, $value);
                
            case 'email':
                return $this->validateEmail($field, $value);
                
            case 'min':
                return $this->validateMin($field, $value, $params[0] ?? 0);
                
            case 'max':
                return $this->validateMax($field, $value, $params[0] ?? 0);
                
            case 'numeric':
                return $this->validateNumeric($field, $value);
                
            case 'integer':
                return $this->validateInteger($field, $value);
                
            case 'string':
                return $this->validateString($field, $value);
                
            case 'array':
                return $this->validateArray($field, $value);
                
            case 'boolean':
                return $this->validateBoolean($field, $value);
                
            case 'url':
                return $this->validateUrl($field, $value);
                
            case 'date':
                return $this->validateDate($field, $value);
                
            case 'confirmed':
                return $this->validateConfirmed($field, $value, $data);
                
            case 'unique':
                return $this->validateUnique($field, $value, $params);
                
            case 'exists':
                return $this->validateExists($field, $value, $params);
                
            case 'in':
                return $this->validateIn($field, $value, $params);
                
            case 'not_in':
                return $this->validateNotIn($field, $value, $params);
                
            case 'regex':
                return $this->validateRegex($field, $value, $params[0] ?? '');
                
            default:
                return null;
        }
    }
    
    /**
     * Validate required field
     */
    protected function validateRequired($field, $value)
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return "The {$field} field is required.";
        }
        return null;
    }
    
    /**
     * Validate email format
     */
    protected function validateEmail($field, $value)
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$field} must be a valid email address.";
        }
        return null;
    }
    
    /**
     * Validate minimum length/value
     */
    protected function validateMin($field, $value, $min)
    {
        if ($value === null) return null;
        
        if (is_string($value) && strlen($value) < $min) {
            return "The {$field} must be at least {$min} characters.";
        }
        
        if (is_numeric($value) && $value < $min) {
            return "The {$field} must be at least {$min}.";
        }
        
        if (is_array($value) && count($value) < $min) {
            return "The {$field} must have at least {$min} items.";
        }
        
        return null;
    }
    
    /**
     * Validate maximum length/value
     */
    protected function validateMax($field, $value, $max)
    {
        if ($value === null) return null;
        
        if (is_string($value) && strlen($value) > $max) {
            return "The {$field} may not be greater than {$max} characters.";
        }
        
        if (is_numeric($value) && $value > $max) {
            return "The {$field} may not be greater than {$max}.";
        }
        
        if (is_array($value) && count($value) > $max) {
            return "The {$field} may not have more than {$max} items.";
        }
        
        return null;
    }
    
    /**
     * Validate numeric value
     */
    protected function validateNumeric($field, $value)
    {
        if ($value !== null && !is_numeric($value)) {
            return "The {$field} must be a number.";
        }
        return null;
    }
    
    /**
     * Validate integer value
     */
    protected function validateInteger($field, $value)
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_INT)) {
            return "The {$field} must be an integer.";
        }
        return null;
    }
    
    /**
     * Validate string value
     */
    protected function validateString($field, $value)
    {
        if ($value !== null && !is_string($value)) {
            return "The {$field} must be a string.";
        }
        return null;
    }
    
    /**
     * Validate array value
     */
    protected function validateArray($field, $value)
    {
        if ($value !== null && !is_array($value)) {
            return "The {$field} must be an array.";
        }
        return null;
    }
    
    /**
     * Validate boolean value
     */
    protected function validateBoolean($field, $value)
    {
        if ($value !== null && !is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            return "The {$field} must be true or false.";
        }
        return null;
    }
    
    /**
     * Validate URL format
     */
    protected function validateUrl($field, $value)
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
            return "The {$field} must be a valid URL.";
        }
        return null;
    }
    
    /**
     * Validate date format
     */
    protected function validateDate($field, $value)
    {
        if ($value !== null && !strtotime($value)) {
            return "The {$field} is not a valid date.";
        }
        return null;
    }
    
    /**
     * Validate confirmed field (e.g., password confirmation)
     */
    protected function validateConfirmed($field, $value, $data)
    {
        $confirmField = $field . '_confirmation';
        if (!isset($data[$confirmField]) || $value !== $data[$confirmField]) {
            return "The {$field} confirmation does not match.";
        }
        return null;
    }
    
    /**
     * Validate unique value in database
     */
    protected function validateUnique($field, $value, $params)
    {
        if ($value === null) return null;
        
        // This would need database access - implement based on your database layer
        // For now, return null (no validation)
        return null;
    }
    
    /**
     * Validate value exists in database
     */
    protected function validateExists($field, $value, $params)
    {
        if ($value === null) return null;
        
        // This would need database access - implement based on your database layer
        // For now, return null (no validation)
        return null;
    }
    
    /**
     * Validate value is in allowed list
     */
    protected function validateIn($field, $value, $params)
    {
        if ($value !== null && !in_array($value, $params)) {
            $allowed = implode(', ', $params);
            return "The selected {$field} is invalid. Allowed values: {$allowed}.";
        }
        return null;
    }
    
    /**
     * Validate value is not in forbidden list
     */
    protected function validateNotIn($field, $value, $params)
    {
        if ($value !== null && in_array($value, $params)) {
            return "The selected {$field} is invalid.";
        }
        return null;
    }
    
    /**
     * Validate value matches regex pattern
     */
    protected function validateRegex($field, $value, $pattern)
    {
        if ($value !== null && !preg_match($pattern, $value)) {
            return "The {$field} format is invalid.";
        }
        return null;
    }
}