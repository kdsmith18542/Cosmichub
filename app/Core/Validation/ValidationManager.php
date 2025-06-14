<?php

namespace App\Core\Validation;

use App\Core\Validation\Contracts\ValidationManagerInterface;
use App\Core\Validation\Contracts\ValidatorInterface;
use App\Core\Validation\Contracts\RuleInterface;
use App\Core\Validation\Rules\RequiredRule;
use App\Core\Validation\Rules\EmailRule;
use App\Core\Validation\Rules\MinLengthRule;
use App\Core\Validation\Rules\MaxLengthRule;
use App\Core\Validation\Rules\NumericRule;
use App\Core\Validation\Rules\IntegerRule;
use App\Core\Validation\Rules\AlphaRule;
use App\Core\Validation\Rules\AlphaNumericRule;
use App\Core\Validation\Rules\UrlRule;
use App\Core\Validation\Rules\DateRule;
use App\Core\Validation\Rules\InRule;
use App\Core\Validation\Rules\NotInRule;
use App\Core\Validation\Rules\RegexRule;
use App\Core\Validation\Rules\ConfirmedRule;
use App\Core\Validation\Rules\UniqueRule;
use App\Core\Validation\Rules\ExistsRule;

/**
 * Validation Manager
 * 
 * Manages validation rules and creates validators.
 */
class ValidationManager implements ValidationManagerInterface
{
    /**
     * Registered validation rules
     * 
     * @var array
     */
    protected array $rules = [];
    
    /**
     * Custom error messages
     * 
     * @var array
     */
    protected array $messages = [];
    
    /**
     * Custom attribute names
     * 
     * @var array
     */
    protected array $attributes = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->registerDefaultRules();
        $this->loadDefaultMessages();
    }
    
    /**
     * Create a new validator instance
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @param array $attributes Custom attribute names
     * @return ValidatorInterface
     */
    public function make(array $data, array $rules, array $messages = [], array $attributes = []): ValidatorInterface
    {
        return new Validator(
            $data,
            $rules,
            array_merge($this->messages, $messages),
            array_merge($this->attributes, $attributes),
            $this
        );
    }
    
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
    public function validate(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = $this->make($data, $rules, $messages, $attributes);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }
    
    /**
     * Register a validation rule
     * 
     * @param string $name Rule name
     * @param string|RuleInterface $rule Rule class or instance
     * @return void
     */
    public function extend(string $name, $rule): void
    {
        $this->rules[$name] = $rule;
    }
    
    /**
     * Get a validation rule
     * 
     * @param string $name Rule name
     * @param array $parameters Rule parameters
     * @return RuleInterface|null
     */
    public function getRule(string $name, array $parameters = []): ?RuleInterface
    {
        if (!isset($this->rules[$name])) {
            return null;
        }
        
        $rule = $this->rules[$name];
        
        if (is_string($rule)) {
            $rule = new $rule($parameters);
        } elseif ($rule instanceof RuleInterface) {
            $rule = clone $rule;
            $rule->setParameters($parameters);
        }
        
        return $rule;
    }
    
    /**
     * Check if a rule exists
     * 
     * @param string $name Rule name
     * @return bool
     */
    public function hasRule(string $name): bool
    {
        return isset($this->rules[$name]);
    }
    
    /**
     * Get all registered rules
     * 
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }
    
    /**
     * Set custom error messages
     * 
     * @param array $messages Error messages
     * @return void
     */
    public function setMessages(array $messages): void
    {
        $this->messages = array_merge($this->messages, $messages);
    }
    
    /**
     * Get error message for a rule
     * 
     * @param string $rule Rule name
     * @param string $attribute Attribute name
     * @return string|null
     */
    public function getMessage(string $rule, string $attribute): ?string
    {
        // Check for attribute-specific message
        $key = "{$attribute}.{$rule}";
        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }
        
        // Check for general rule message
        if (isset($this->messages[$rule])) {
            return $this->messages[$rule];
        }
        
        return null;
    }
    
    /**
     * Set custom attribute names
     * 
     * @param array $attributes Attribute names
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = array_merge($this->attributes, $attributes);
    }
    
    /**
     * Get attribute name
     * 
     * @param string $attribute Attribute key
     * @return string
     */
    public function getAttribute(string $attribute): string
    {
        return $this->attributes[$attribute] ?? $attribute;
    }
    
    /**
     * Register default validation rules
     * 
     * @return void
     */
    protected function registerDefaultRules(): void
    {
        $this->rules = [
            'required' => \App\Core\Validation\Rules\RequiredRule::class,
            'email' => \App\Core\Validation\Rules\EmailRule::class,
            'min_length' => \App\Core\Validation\Rules\MinLengthRule::class,
            'max_length' => \App\Core\Validation\Rules\MaxLengthRule::class,
            'numeric' => \App\Core\Validation\Rules\NumericRule::class,
            'integer' => \App\Core\Validation\Rules\IntegerRule::class,
            'alpha' => \App\Core\Validation\Rules\AlphaRule::class,
            'alpha_num' => \App\Core\Validation\Rules\AlphaNumRule::class,
        ];
    }
    
    /**
     * Load default error messages
     * 
     * @return void
     */
    protected function loadDefaultMessages(): void
    {
        $this->messages = [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'min' => 'The :attribute must be at least :min characters.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'numeric' => 'The :attribute must be a number.',
            'integer' => 'The :attribute must be an integer.',
            'alpha' => 'The :attribute may only contain letters.',
            'alpha_num' => 'The :attribute may only contain letters and numbers.',
            'url' => 'The :attribute format is invalid.',
            'date' => 'The :attribute is not a valid date.',
            'in' => 'The selected :attribute is invalid.',
            'not_in' => 'The selected :attribute is invalid.',
            'regex' => 'The :attribute format is invalid.',
            'confirmed' => 'The :attribute confirmation does not match.',
            'unique' => 'The :attribute has already been taken.',
            'exists' => 'The selected :attribute is invalid.',
        ];
    }
    
    /**
     * Parse rule string into name and parameters
     * 
     * @param string $rule Rule string
     * @return array [name, parameters]
     */
    public function parseRule(string $rule): array
    {
        if (strpos($rule, ':') === false) {
            return [$rule, []];
        }
        
        [$name, $params] = explode(':', $rule, 2);
        $parameters = explode(',', $params);
        
        return [$name, array_map('trim', $parameters)];
    }
    
    /**
     * Get validation statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_rules' => count($this->rules),
            'total_messages' => count($this->messages),
            'total_attributes' => count($this->attributes),
            'registered_rules' => array_keys($this->rules),
        ];
    }
}