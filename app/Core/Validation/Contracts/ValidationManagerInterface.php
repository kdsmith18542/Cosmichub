<?php

namespace App\Core\Validation\Contracts;

/**
 * Validation Manager Interface
 * 
 * Defines the contract for validation managers.
 */
interface ValidationManagerInterface
{
    /**
     * Create a new validator instance
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @param array $attributes Custom attribute names
     * @return ValidatorInterface
     */
    public function make(array $data, array $rules, array $messages = [], array $attributes = []): ValidatorInterface;
    
    /**
     * Validate data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @param array $attributes Custom attribute names
     * @return array Validated data
     * @throws ValidationException
     */
    public function validate(array $data, array $rules, array $messages = [], array $attributes = []): array;
    
    /**
     * Register a validation rule
     * 
     * @param string $name Rule name
     * @param string|RuleInterface $rule Rule class or instance
     * @return void
     */
    public function extend(string $name, $rule): void;
    
    /**
     * Get a validation rule
     * 
     * @param string $name Rule name
     * @param array $parameters Rule parameters
     * @return RuleInterface|null
     */
    public function getRule(string $name, array $parameters = []): ?RuleInterface;
    
    /**
     * Check if a rule exists
     * 
     * @param string $name Rule name
     * @return bool
     */
    public function hasRule(string $name): bool;
    
    /**
     * Get all registered rules
     * 
     * @return array
     */
    public function getRules(): array;
    
    /**
     * Set custom error messages
     * 
     * @param array $messages Error messages
     * @return void
     */
    public function setMessages(array $messages): void;
    
    /**
     * Get error message for a rule
     * 
     * @param string $rule Rule name
     * @param string $attribute Attribute name
     * @return string|null
     */
    public function getMessage(string $rule, string $attribute): ?string;
    
    /**
     * Set custom attribute names
     * 
     * @param array $attributes Attribute names
     * @return void
     */
    public function setAttributes(array $attributes): void;
    
    /**
     * Get attribute name
     * 
     * @param string $attribute Attribute key
     * @return string
     */
    public function getAttribute(string $attribute): string;
    
    /**
     * Parse rule string into name and parameters
     * 
     * @param string $rule Rule string
     * @return array [name, parameters]
     */
    public function parseRule(string $rule): array;
    
    /**
     * Get validation statistics
     * 
     * @return array
     */
    public function getStats(): array;
}