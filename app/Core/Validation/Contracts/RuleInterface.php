<?php

namespace App\Core\Validation\Contracts;

/**
 * Rule Interface
 * 
 * Defines the contract for validation rules.
 */
interface RuleInterface
{
    /**
     * Validate the given value
     * 
     * @param string $field Field name
     * @param mixed $value Value to validate
     * @param array $parameters Rule parameters
     * @param array $data All validation data
     * @return bool
     */
    public function validate(string $field, $value, array $parameters, array $data): bool;
    
    /**
     * Get the validation error message
     * 
     * @param string $field Field name
     * @param mixed $value Value that failed validation
     * @param array $parameters Rule parameters
     * @return string
     */
    public function getMessage(string $field, $value, array $parameters): string;
    
    /**
     * Get the rule name
     * 
     * @return string
     */
    public function getName(): string;
    
    /**
     * Check if the rule should stop validation on failure
     * 
     * @return bool
     */
    public function shouldStopOnFailure(): bool;
    
    /**
     * Get rule requirements (e.g., required parameters)
     * 
     * @return array
     */
    public function getRequirements(): array;
}