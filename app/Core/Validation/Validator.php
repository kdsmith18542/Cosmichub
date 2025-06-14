<?php

namespace App\Core\Validation;

use App\Core\Container\Container;
use App\Core\Exceptions\ServiceException;
use App\Core\Validation\Contracts\ValidatorInterface;
use App\Exceptions\ValidationException;

/**
 * Validator Class
 * 
 * Provides data validation functionality for the application
 */
class Validator implements ValidatorInterface
{
    /**
     * @var Container The application container
     */
    protected $container;
    
    /**
     * @var array Validation rules
     */
    protected $rules = [];
    
    /**
     * @var array Custom validation messages
     */
    protected $messages = [];
    
    /**
     * @var array Custom attribute names
     */
    protected $attributes = [];
    
    /**
     * @var array Validation errors
     */
    protected $errors = [];
    
    /**
     * @var array Data being validated
     */
    protected $data = [];
    
    /**
     * @var array Custom validation rules
     */
    protected $customRules = [];
    
    /**
     * @var array Rule aliases
     */
    protected $aliases = [
        'req' => 'required',
        'min' => 'min_length',
        'max' => 'max_length',
        'email' => 'valid_email',
        'url' => 'valid_url',
        'int' => 'integer',
        'num' => 'numeric',
        'bool' => 'boolean'
    ];
    
    /**
     * Create a new validator instance
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Validate data against rules
     * 
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $attributes
     * @return bool
     */
    public function validate(array $data, array $rules, array $messages = [], array $attributes = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->attributes = $attributes;
        $this->errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $this->validateField($field, $fieldRules);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate a single field
     * 
     * @param string $field
     * @param string|array $rules
     * @return void
     */
    protected function validateField($field, $rules)
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        
        $value = $this->getValue($field);
        $isRequired = in_array('required', $rules);
        
        // If field is not required and empty, skip validation
        if (!$isRequired && $this->isEmpty($value)) {
            return;
        }
        
        foreach ($rules as $rule) {
            $this->validateRule($field, $value, $rule);
        }
    }
    
    /**
     * Validate a single rule
     * 
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @return void
     */
    protected function validateRule($field, $value, $rule)
    {
        // Parse rule and parameters
        $parameters = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramString] = explode(':', $rule, 2);
            $parameters = explode(',', $paramString);
        }
        
        // Resolve rule alias
        $rule = $this->aliases[$rule] ?? $rule;
        
        // Check if it's a custom rule
        if (isset($this->customRules[$rule])) {
            $passes = call_user_func($this->customRules[$rule], $value, $parameters, $this->data);
        } else {
            $method = 'validate' . ucfirst(str_replace('_', '', $rule));
            
            if (!method_exists($this, $method)) {
                throw new ServiceException("Validation rule '{$rule}' does not exist");
            }
            
            $passes = $this->$method($value, $parameters, $field);
        }
        
        if (!$passes) {
            $this->addError($field, $rule, $parameters);
        }
    }
    
    /**
     * Add validation error
     * 
     * @param string $field
     * @param string $rule
     * @param array $parameters
     * @return void
     */
    protected function addError($field, $rule, array $parameters = [])
    {
        $message = $this->getMessage($field, $rule, $parameters);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get validation error message
     * 
     * @param string $field
     * @param string $rule
     * @param array $parameters
     * @return string
     */
    protected function getMessage($field, $rule, array $parameters = [])
    {
        $key = "{$field}.{$rule}";
        
        if (isset($this->messages[$key])) {
            return $this->formatMessage($this->messages[$key], $field, $parameters);
        }
        
        if (isset($this->messages[$rule])) {
            return $this->formatMessage($this->messages[$rule], $field, $parameters);
        }
        
        return $this->getDefaultMessage($field, $rule, $parameters);
    }
    
    /**
     * Get default error message
     * 
     * @param string $field
     * @param string $rule
     * @param array $parameters
     * @return string
     */
    protected function getDefaultMessage($field, $rule, array $parameters = [])
    {
        $attribute = $this->getAttribute($field);
        
        $messages = [
            'required' => "The {$attribute} field is required.",
            'min_length' => "The {$attribute} must be at least {$parameters[0]} characters.",
            'max_length' => "The {$attribute} may not be greater than {$parameters[0]} characters.",
            'valid_email' => "The {$attribute} must be a valid email address.",
            'valid_url' => "The {$attribute} must be a valid URL.",
            'integer' => "The {$attribute} must be an integer.",
            'numeric' => "The {$attribute} must be a number.",
            'boolean' => "The {$attribute} must be true or false.",
            'in' => "The selected {$attribute} is invalid.",
            'not_in' => "The selected {$attribute} is invalid.",
            'unique' => "The {$attribute} has already been taken.",
            'exists' => "The selected {$attribute} is invalid.",
            'confirmed' => "The {$attribute} confirmation does not match.",
            'same' => "The {$attribute} and {$parameters[0]} must match.",
            'different' => "The {$attribute} and {$parameters[0]} must be different.",
            'regex' => "The {$attribute} format is invalid.",
            'alpha' => "The {$attribute} may only contain letters.",
            'alpha_num' => "The {$attribute} may only contain letters and numbers.",
            'alpha_dash' => "The {$attribute} may only contain letters, numbers, dashes and underscores.",
            'date' => "The {$attribute} is not a valid date.",
            'date_format' => "The {$attribute} does not match the format {$parameters[0]}.",
            'before' => "The {$attribute} must be a date before {$parameters[0]}.",
            'after' => "The {$attribute} must be a date after {$parameters[0]}.",
            'array' => "The {$attribute} must be an array.",
            'json' => "The {$attribute} must be a valid JSON string."
        ];
        
        return $messages[$rule] ?? "The {$attribute} is invalid.";
    }
    
    /**
     * Format error message
     * 
     * @param string $message
     * @param string $field
     * @param array $parameters
     * @return string
     */
    protected function formatMessage($message, $field, array $parameters = [])
    {
        $message = str_replace(':attribute', $this->getAttribute($field), $message);
        $message = str_replace(':field', $field, $message);
        
        foreach ($parameters as $index => $parameter) {
            $message = str_replace(":param{$index}", $parameter, $message);
            $message = str_replace(":{$index}", $parameter, $message);
        }
        
        return $message;
    }
    
    /**
     * Get attribute name
     * 
     * @param string $field
     * @return string
     */
    protected function getAttribute($field)
    {
        return $this->attributes[$field] ?? str_replace('_', ' ', $field);
    }
    
    /**
     * Get field value
     * 
     * @param string $field
     * @return mixed
     */
    protected function getValue($field)
    {
        return $this->data[$field] ?? null;
    }
    
    /**
     * Check if value is empty
     * 
     * @param mixed $value
     * @return bool
     */
    protected function isEmpty($value)
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Get first error for a field
     * 
     * @param string $field
     * @return string|null
     */
    public function getFirstError($field)
    {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Check if validation failed
     * 
     * @return bool
     */
    public function fails()
    {
        return !empty($this->errors);
    }
    
    /**
     * Check if validation passed
     * 
     * @return bool
     */
    public function passes()
    {
        return empty($this->errors);
    }
    
    /**
     * Add custom validation rule
     * 
     * @param string $name
     * @param callable $callback
     * @return $this
     */
    public function addRule($name, callable $callback)
    {
        $this->customRules[$name] = $callback;
        return $this;
    }
    
    /**
     * Add rule alias
     * 
     * @param string $alias
     * @param string $rule
     * @return $this
     */
    public function addAlias($alias, $rule)
    {
        $this->aliases[$alias] = $rule;
        return $this;
    }
    
    // Built-in validation rules
    
    protected function validateRequired($value, $parameters, $field)
    {
        return !$this->isEmpty($value);
    }
    
    protected function validateMinlength($value, $parameters, $field)
    {
        return strlen($value) >= (int) $parameters[0];
    }
    
    protected function validateMaxlength($value, $parameters, $field)
    {
        return strlen($value) <= (int) $parameters[0];
    }
    
    protected function validateValidemail($value, $parameters, $field)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    protected function validateValidurl($value, $parameters, $field)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    protected function validateInteger($value, $parameters, $field)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    protected function validateNumeric($value, $parameters, $field)
    {
        return is_numeric($value);
    }
    
    protected function validateBoolean($value, $parameters, $field)
    {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }
    
    protected function validateIn($value, $parameters, $field)
    {
        return in_array($value, $parameters);
    }
    
    protected function validateNotin($value, $parameters, $field)
    {
        return !in_array($value, $parameters);
    }
    
    protected function validateConfirmed($value, $parameters, $field)
    {
        $confirmField = $field . '_confirmation';
        return $value === $this->getValue($confirmField);
    }
    
    protected function validateSame($value, $parameters, $field)
    {
        return $value === $this->getValue($parameters[0]);
    }
    
    protected function validateDifferent($value, $parameters, $field)
    {
        return $value !== $this->getValue($parameters[0]);
    }
    
    protected function validateRegex($value, $parameters, $field)
    {
        return preg_match($parameters[0], $value);
    }
    
    protected function validateAlpha($value, $parameters, $field)
    {
        return preg_match('/^[a-zA-Z]+$/', $value);
    }
    
    protected function validateAlphanum($value, $parameters, $field)
    {
        return preg_match('/^[a-zA-Z0-9]+$/', $value);
    }
    
    protected function validateAlphadash($value, $parameters, $field)
    {
        return preg_match('/^[a-zA-Z0-9_-]+$/', $value);
    }
    
    protected function validateDate($value, $parameters, $field)
    {
        return strtotime($value) !== false;
    }
    
    protected function validateDateformat($value, $parameters, $field)
    {
        $date = \DateTime::createFromFormat($parameters[0], $value);
        return $date && $date->format($parameters[0]) === $value;
    }
    
    protected function validateBefore($value, $parameters, $field)
    {
        return strtotime($value) < strtotime($parameters[0]);
    }
    
    protected function validateAfter($value, $parameters, $field)
    {
        return strtotime($value) > strtotime($parameters[0]);
    }
    
    protected function validateArray($value, $parameters, $field)
    {
        return is_array($value);
    }
    
    protected function validateJson($value, $parameters, $field)
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Create a new validator instance
     * 
     * @param Container $container
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $attributes
     * @return static
     */
    public static function make(Container $container, array $data, array $rules, array $messages = [], array $attributes = [])
    {
        $validator = new static($container);
        $validator->validate($data, $rules, $messages, $attributes);
        return $validator;
    }
    
    /**
     * Validate and throw exception on failure
     * 
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $attributes
     * @return array
     * @throws ServiceException
     */
    public function validateOrFail(array $data, array $rules, array $messages = [], array $attributes = [])
    {
        if (!$this->validate($data, $rules, $messages, $attributes)) {
            throw ServiceException::validationFailed('Validation failed', $this->errors);
        }
        
        return $data;
    }
    
    /**
     * Determine if the validation passes
     * 
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * Determine if the validation fails
     * 
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->passes();
    }
    
    /**
     * Get the validated data
     * 
     * @return array
     */
    public function validated(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this);
        }
        
        $validated = [];
        foreach ($this->rules as $field => $rules) {
            if (array_key_exists($field, $this->data)) {
                $validated[$field] = $this->data[$field];
            }
        }
        
        return $validated;
    }
    
    /**
     * Get errors for a specific field
     * 
     * @param string $field
     * @return array
     */
    public function getErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Check if a field has errors
     * 
     * @param string $field
     * @return bool
     */
    public function hasErrors(string $field): bool
    {
        return !empty($this->errors[$field]);
    }
    
    /**
     * Get the first error for a field
     * 
     * @param string $field
     * @return string|null
     */
    public function first(string $field): ?string
    {
        $errors = $this->getErrors($field);
        return $errors[0] ?? null;
    }
    
    /**
     * Add a custom error
     * 
     * @param string $field
     * @param string $message
     * @return self
     */
    public function addError(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
        
        return $this;
    }
    
    /**
     * Add multiple custom errors
     * 
     * @param array $errors
     * @return self
     */
    public function addErrors(array $errors): self
    {
        foreach ($errors as $field => $messages) {
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            } else {
                $this->addError($field, $messages);
            }
        }
        
        return $this;
    }
    
    /**
     * Get the data being validated
     * 
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    /**
     * Get the validation rules
     * 
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }
    
    /**
     * Get validation statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        return [
            'fields_validated' => count($this->rules),
            'total_errors' => array_sum(array_map('count', $this->errors)),
            'memory_usage' => memory_get_usage(true),
            'has_errors' => !empty($this->errors),
            'error_fields' => array_keys($this->errors)
        ];
    }
}