<?php

namespace App\Core\Validation\Contracts;

/**
 * Validator Interface
 * 
 * Defines the contract for validation instances.
 */
interface ValidatorInterface
{
    /**
     * Determine if the validation passes
     * 
     * @return bool
     */
    public function passes(): bool;
    
    /**
     * Determine if the validation fails
     * 
     * @return bool
     */
    public function fails(): bool;
    
    /**
     * Get the validated data
     * 
     * @return array
     */
    public function validated(): array;
    
    /**
     * Get all validation errors
     * 
     * @return array
     */
    public function errors(): array;
    
    /**
     * Get errors for a specific field
     * 
     * @param string $field
     * @return array
     */
    public function getErrors(string $field): array;
    
    /**
     * Check if a field has errors
     * 
     * @param string $field
     * @return bool
     */
    public function hasErrors(string $field): bool;
    
    /**
     * Get the first error for a field
     * 
     * @param string $field
     * @return string|null
     */
    public function first(string $field): ?string;
    
    /**
     * Add a custom error
     * 
     * @param string $field
     * @param string $message
     * @return self
     */
    public function addError(string $field, string $message): self;
    
    /**
     * Add multiple custom errors
     * 
     * @param array $errors
     * @return self
     */
    public function addErrors(array $errors): self;
    
    /**
     * Get the data being validated
     * 
     * @return array
     */
    public function getData(): array;
    
    /**
     * Get the validation rules
     * 
     * @return array
     */
    public function getRules(): array;
    
    /**
     * Get validation statistics
     * 
     * @return array
     */
    public function getStats(): array;
}