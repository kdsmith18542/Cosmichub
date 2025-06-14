<?php

namespace App\Core\Validation\Rules;

use App\Core\Validation\Contracts\RuleInterface;

/**
 * Abstract Rule
 * 
 * Base class for validation rules.
 */
abstract class AbstractRule implements RuleInterface
{
    /**
     * Rule name
     * 
     * @var string
     */
    protected string $name;
    
    /**
     * Default error message
     * 
     * @var string
     */
    protected string $message = 'The :attribute field is invalid.';
    
    /**
     * Whether this rule should stop validation on failure
     * 
     * @var bool
     */
    protected bool $stopOnFailure = false;
    
    /**
     * Rule requirements
     * 
     * @var array
     */
    protected array $requirements = [];
    
    /**
     * Get the rule name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?? strtolower(class_basename(static::class));
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
        $message = $this->message;
        
        // Replace placeholders
        $message = str_replace(':attribute', $field, $message);
        $message = str_replace(':value', (string)$value, $message);
        
        // Replace parameter placeholders
        foreach ($parameters as $index => $parameter) {
            $message = str_replace(":param{$index}", $parameter, $message);
            $message = str_replace(":param", $parameter, $message); // For single parameter
        }
        
        return $message;
    }
    
    /**
     * Check if the rule should stop validation on failure
     * 
     * @return bool
     */
    public function shouldStopOnFailure(): bool
    {
        return $this->stopOnFailure;
    }
    
    /**
     * Get rule requirements
     * 
     * @return array
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }
    
    /**
     * Check if a value is empty
     * 
     * @param mixed $value
     * @return bool
     */
    protected function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }
    
    /**
     * Get the size of a value
     * 
     * @param mixed $value
     * @return int|float
     */
    protected function getSize($value)
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        if (is_array($value)) {
            return count($value);
        }
        
        if (is_string($value)) {
            return mb_strlen($value, 'UTF-8');
        }
        
        return 0;
    }
}