<?php

/**
 * Validation Helper Functions
 * 
 * Provides convenient global functions for validation operations.
 */

if (!function_exists('validator')) {
    /**
     * Create a new validator instance
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @param array $attributes Custom attribute names
     * @return \App\Core\Validation\Contracts\ValidatorInterface
     */
    function validator(array $data = [], array $rules = [], array $messages = [], array $attributes = [])
    {
        $manager = app('validator');
        
        if (empty($data) && empty($rules)) {
            return $manager;
        }
        
        return $manager->make($data, $rules, $messages, $attributes);
    }
}

if (!function_exists('validate')) {
    /**
     * Validate data and return validated data or throw exception
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @param array $attributes Custom attribute names
     * @return array Validated data
     * @throws \App\Core\Validation\Exceptions\ValidationException
     */
    function validate(array $data, array $rules, array $messages = [], array $attributes = [])
    {
        return validator($data, $rules, $messages, $attributes)->validate();
    }
}

if (!function_exists('validation_rule')) {
    /**
     * Create a validation rule instance
     * 
     * @param string $rule Rule name
     * @param mixed ...$parameters Rule parameters
     * @return \App\Core\Validation\Contracts\RuleInterface|null
     */
    function validation_rule(string $rule, ...$parameters)
    {
        $manager = app('validator');
        $ruleClass = $manager->getRule($rule);
        
        if (!$ruleClass) {
            return null;
        }
        
        return new $ruleClass(...$parameters);
    }
}

if (!function_exists('validation_passes')) {
    /**
     * Check if validation passes
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @param array $attributes Custom attribute names
     * @return bool
     */
    function validation_passes(array $data, array $rules, array $messages = [], array $attributes = []): bool
    {
        return validator($data, $rules, $messages, $attributes)->passes();
    }
}

if (!function_exists('validation_fails')) {
    /**
     * Check if validation fails
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @param array $attributes Custom attribute names
     * @return bool
     */
    function validation_fails(array $data, array $rules, array $messages = [], array $attributes = []): bool
    {
        return validator($data, $rules, $messages, $attributes)->fails();
    }
}

if (!function_exists('validation_errors')) {
    /**
     * Get validation errors
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @param array $attributes Custom attribute names
     * @return array
     */
    function validation_errors(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = validator($data, $rules, $messages, $attributes);
        $validator->validate();
        return $validator->getErrors();
    }
}