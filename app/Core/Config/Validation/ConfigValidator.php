<?php

namespace App\Core\Config\Validation;

use App\Core\Exceptions\ConfigException;

/**
 * Configuration Validator
 * 
 * Handles validation of configuration values and structure
 */
class ConfigValidator
{
    /**
     * Validation rules
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Required configuration keys
     *
     * @var array
     */
    protected $required = [];

    /**
     * Default validation rules for common configuration keys
     *
     * @var array
     */
    protected $defaultRules = [
        'app.name' => 'string|required',
        'app.env' => 'string|required|in:local,development,testing,staging,production',
        'app.debug' => 'boolean',
        'app.url' => 'string|url',
        'app.timezone' => 'string|timezone',
        'app.locale' => 'string|locale',
        'app.fallback_locale' => 'string|locale',
        'app.key' => 'string|required|min:32',
        'database.default' => 'string|required',
        'database.connections' => 'array|required',
        'cache.default' => 'string|required',
        'cache.stores' => 'array|required',
        'session.driver' => 'string|required',
        'session.lifetime' => 'integer|min:1',
        'session.expire_on_close' => 'boolean',
        'session.encrypt' => 'boolean',
        'session.cookie' => 'string|required',
        'session.domain' => 'string|nullable',
        'session.secure' => 'boolean',
        'session.http_only' => 'boolean',
        'session.same_site' => 'string|in:lax,strict,none',
        'logging.default' => 'string|required',
        'logging.channels' => 'array|required',
        'mail.default' => 'string|required',
        'mail.mailers' => 'array|required',
    ];

    /**
     * Create a new config validator instance
     *
     * @param array $rules
     * @param array $required
     */
    public function __construct(array $rules = [], array $required = [])
    {
        $this->rules = array_merge($this->defaultRules, $rules);
        $this->required = $required;
    }

    /**
     * Validate configuration array
     *
     * @param array $config
     * @return bool
     * @throws ConfigException
     */
    public function validate(array $config)
    {
        $errors = [];

        // Validate required keys
        $errors = array_merge($errors, $this->validateRequired($config));

        // Validate rules
        $errors = array_merge($errors, $this->validateRules($config));

        if (!empty($errors)) {
            throw new ConfigException('Configuration validation failed: ' . implode(', ', $errors));
        }

        return true;
    }

    /**
     * Validate required configuration keys
     *
     * @param array $config
     * @return array
     */
    protected function validateRequired(array $config)
    {
        $errors = [];
        $requiredKeys = array_merge($this->required, $this->getRequiredFromRules());

        foreach ($requiredKeys as $key) {
            if (!$this->hasValue($config, $key)) {
                $errors[] = "Required configuration key '{$key}' is missing";
            }
        }

        return $errors;
    }

    /**
     * Get required keys from validation rules
     *
     * @return array
     */
    protected function getRequiredFromRules()
    {
        $required = [];

        foreach ($this->rules as $key => $rule) {
            if (is_string($rule) && strpos($rule, 'required') !== false) {
                $required[] = $key;
            } elseif (is_array($rule) && in_array('required', $rule)) {
                $required[] = $key;
            }
        }

        return $required;
    }

    /**
     * Validate configuration rules
     *
     * @param array $config
     * @return array
     */
    protected function validateRules(array $config)
    {
        $errors = [];

        foreach ($this->rules as $key => $rule) {
            if (!$this->hasValue($config, $key)) {
                // Skip validation if key doesn't exist and is not required
                if (!$this->isRequired($rule)) {
                    continue;
                }
            }

            $value = $this->getValue($config, $key);
            $ruleErrors = $this->validateValue($key, $value, $rule);
            $errors = array_merge($errors, $ruleErrors);
        }

        return $errors;
    }

    /**
     * Validate a single value against rules
     *
     * @param string $key
     * @param mixed $value
     * @param string|array $rules
     * @return array
     */
    protected function validateValue($key, $value, $rules)
    {
        $errors = [];
        $ruleList = is_string($rules) ? explode('|', $rules) : $rules;

        foreach ($ruleList as $rule) {
            $ruleName = $rule;
            $ruleParams = [];

            // Parse rule parameters (e.g., 'min:5' -> ['min', '5'])
            if (strpos($rule, ':') !== false) {
                list($ruleName, $params) = explode(':', $rule, 2);
                $ruleParams = explode(',', $params);
            }

            $error = $this->applyRule($key, $value, $ruleName, $ruleParams);
            if ($error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Apply a single validation rule
     *
     * @param string $key
     * @param mixed $value
     * @param string $rule
     * @param array $params
     * @return string|null
     */
    protected function applyRule($key, $value, $rule, array $params = [])
    {
        switch ($rule) {
            case 'required':
                return $this->validateRequired($key, $value);
            case 'string':
                return $this->validateString($key, $value);
            case 'integer':
            case 'int':
                return $this->validateInteger($key, $value);
            case 'boolean':
            case 'bool':
                return $this->validateBoolean($key, $value);
            case 'array':
                return $this->validateArray($key, $value);
            case 'numeric':
                return $this->validateNumeric($key, $value);
            case 'url':
                return $this->validateUrl($key, $value);
            case 'email':
                return $this->validateEmail($key, $value);
            case 'min':
                return $this->validateMin($key, $value, $params[0] ?? 0);
            case 'max':
                return $this->validateMax($key, $value, $params[0] ?? 0);
            case 'in':
                return $this->validateIn($key, $value, $params);
            case 'nullable':
                return null; // Always passes
            case 'timezone':
                return $this->validateTimezone($key, $value);
            case 'locale':
                return $this->validateLocale($key, $value);
            default:
                return null; // Unknown rule, skip
        }
    }

    /**
     * Validate required rule
     *
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    protected function validateRequired($key, $value)
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return "Configuration key '{$key}' is required";
        }
        return null;
    }

    /**
     * Validate string rule
     *
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    protected function validateString($key, $value)
    {
        if ($value !== null && !is_string($value)) {
            return "Configuration key '{$key}' must be a string";
        }
        return null;
    }

    /**
     * Validate integer rule
     *
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    protected function validateInteger($key, $value)
    {
        if ($value !== null && !is_int($value) && !ctype_digit((string)$value)) {
            return "Configuration key '{$key}' must be an integer";
        }
        return null;
    }

    /**
     * Validate boolean rule
     *
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    protected function validateBoolean($key, $value)
    {
        if ($value !== null && !is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            return "Configuration key '{$key}' must be a boolean";
        }
        return null;
    }

    /**
     * Validate array rule
     *
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    protected function validateArray($key, $value)
    {
        if ($value !== null && !is_array($value)) {
            return "Configuration key '{$key}' must be an array";
        }
        return null;
    }

    /**
     * Validate numeric rule
     *
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    protected function validateNumeric($key, $value)
    {
        if ($value !== null && !is_numeric($value)) {
            return "Configuration key '{$key}' must be numeric";
        }
        return null;
    }

    /**
     * Validate URL rule
     *
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    protected function validateUrl($key, $value)
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
            return "Configuration key '{$key}' must be a valid URL";
        }
        return null;
    }

    /**
     * Validate email rule
     *
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    protected function validateEmail($key, $value)
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Configuration key '{$key}' must be a valid email address";
        }
        return null;
    }

    /**
     * Validate minimum value rule
     *
     * @param string $key
     * @param mixed $value
     * @param mixed $min
     * @return string|null
     */
    protected function validateMin($key, $value, $min)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && strlen($value) < $min) {
            return "Configuration key '{$key}' must be at least {$min} characters";
        }

        if (is_numeric($value) && $value < $min) {
            return "Configuration key '{$key}' must be at least {$min}";
        }

        if (is_array($value) && count($value) < $min) {
            return "Configuration key '{$key}' must have at least {$min} items";
        }

        return null;
    }

    /**
     * Validate maximum value rule
     *
     * @param string $key
     * @param mixed $value
     * @param mixed $max
     * @return string|null
     */
    protected function validateMax($key, $value, $max)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && strlen($value) > $max) {
            return "Configuration key '{$key}' must not exceed {$max} characters";
        }

        if (is_numeric($value) && $value > $max) {
            return "Configuration key '{$key}' must not exceed {$max}";
        }

        if (is_array($value) && count($value) > $max) {
            return "Configuration key '{$key}' must not have more than {$max} items";
        }

        return null;
    }

    /**
     * Validate in rule
     *
     * @param string $key
     * @param mixed $value
     * @param array $allowed
     * @return string|null
     */
    protected function validateIn($key, $value, array $allowed)
    {
        if ($value !== null && !in_array($value, $allowed, true)) {
            $allowedStr = implode(', ', $allowed);
            return "Configuration key '{$key}' must be one of: {$allowedStr}";
        }
        return null;
    }

    /**
     * Validate timezone rule
     *
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    protected function validateTimezone($key, $value)
    {
        if ($value !== null && !in_array($value, timezone_identifiers_list())) {
            return "Configuration key '{$key}' must be a valid timezone";
        }
        return null;
    }

    /**
     * Validate locale rule
     *
     * @param string $key
     * @param mixed $value
     * @return string|null
     */
    protected function validateLocale($key, $value)
    {
        if ($value !== null) {
            // Basic locale validation (language code or language-country code)
            if (!preg_match('/^[a-z]{2}([_-][A-Z]{2})?$/', $value)) {
                return "Configuration key '{$key}' must be a valid locale (e.g., 'en', 'en_US', 'en-US')";
            }
        }
        return null;
    }

    /**
     * Check if a rule is required
     *
     * @param string|array $rules
     * @return bool
     */
    protected function isRequired($rules)
    {
        if (is_string($rules)) {
            return strpos($rules, 'required') !== false;
        }

        if (is_array($rules)) {
            return in_array('required', $rules);
        }

        return false;
    }

    /**
     * Check if a value exists in the configuration array using dot notation
     *
     * @param array $config
     * @param string $key
     * @return bool
     */
    protected function hasValue(array $config, $key)
    {
        $keys = explode('.', $key);
        $current = $config;

        foreach ($keys as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    /**
     * Get a value from the configuration array using dot notation
     *
     * @param array $config
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getValue(array $config, $key, $default = null)
    {
        $keys = explode('.', $key);
        $current = $config;

        foreach ($keys as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Add validation rule
     *
     * @param string $key
     * @param string|array $rule
     * @return void
     */
    public function addRule($key, $rule)
    {
        $this->rules[$key] = $rule;
    }

    /**
     * Add required key
     *
     * @param string $key
     * @return void
     */
    public function addRequired($key)
    {
        if (!in_array($key, $this->required)) {
            $this->required[] = $key;
        }
    }

    /**
     * Get all validation rules
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Get all required keys
     *
     * @return array
     */
    public function getRequired()
    {
        return array_merge($this->required, $this->getRequiredFromRules());
    }
}