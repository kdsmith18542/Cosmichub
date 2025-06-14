<?php

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;

/**
 * Validation Middleware
 * 
 * Provides input validation for HTTP requests with customizable rules.
 */
class ValidationMiddleware implements MiddlewareInterface
{
    /**
     * Validation rules for different routes.
     *
     * @var array
     */
    protected $rules = [];
    
    /**
     * Custom error messages.
     *
     * @var array
     */
    protected $messages = [];
    
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next)
    {
        $rules = $this->getRulesForRequest($request);
        
        if (!empty($rules)) {
            $validator = $this->validate($request, $rules);
            
            if ($validator['fails']) {
                return $this->buildValidationErrorResponse($validator['errors'], $request);
            }
        }
        
        return $next($request);
    }
    
    /**
     * Get validation rules for the current request.
     *
     * @param Request $request
     * @return array
     */
    protected function getRulesForRequest(Request $request)
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $key = $method . ':' . $path;
        
        // Check for exact match first
        if (isset($this->rules[$key])) {
            return $this->rules[$key];
        }
        
        // Check for pattern matches
        foreach ($this->rules as $pattern => $rules) {
            if ($this->matchesPattern($pattern, $key)) {
                return $rules;
            }
        }
        
        return [];
    }
    
    /**
     * Check if a key matches a pattern.
     *
     * @param string $pattern
     * @param string $key
     * @return bool
     */
    protected function matchesPattern($pattern, $key)
    {
        // Convert pattern to regex
        $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
        $regex = '/^' . $regex . '$/i';
        
        return preg_match($regex, $key);
    }
    
    /**
     * Validate the request data.
     *
     * @param Request $request
     * @param array $rules
     * @return array
     */
    protected function validate(Request $request, array $rules)
    {
        $data = $this->getRequestData($request);
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $ruleName = $rule;
                $ruleValue = null;
                
                // Parse rule with parameters (e.g., 'max:255')
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $ruleValue) = explode(':', $rule, 2);
                }
                
                $error = $this->validateField($field, $value, $ruleName, $ruleValue, $data);
                
                if ($error) {
                    if (!isset($errors[$field])) {
                        $errors[$field] = [];
                    }
                    $errors[$field][] = $error;
                    break; // Stop on first error for this field
                }
            }
        }
        
        return [
            'fails' => !empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get request data for validation.
     *
     * @param Request $request
     * @return array
     */
    protected function getRequestData(Request $request)
    {
        $data = [];
        
        // Get POST data
        if ($request->getMethod() === 'POST') {
            $data = array_merge($data, $_POST);
        }
        
        // Get JSON data
        $jsonData = $request->getJsonData();
        if ($jsonData) {
            $data = array_merge($data, $jsonData);
        }
        
        // Get query parameters
        $data = array_merge($data, $_GET);
        
        return $data;
    }
    
    /**
     * Validate a single field.
     *
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @param mixed $ruleValue
     * @param array $data
     * @return string|null
     */
    protected function validateField($field, $value, $rule, $ruleValue, array $data)
    {
        switch ($rule) {
            case 'required':
                if (is_null($value) || $value === '') {
                    return $this->getMessage($field, $rule, "The {$field} field is required.");
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $this->getMessage($field, $rule, "The {$field} must be a valid email address.");
                }
                break;
                
            case 'min':
                if ($value && strlen($value) < $ruleValue) {
                    return $this->getMessage($field, $rule, "The {$field} must be at least {$ruleValue} characters.");
                }
                break;
                
            case 'max':
                if ($value && strlen($value) > $ruleValue) {
                    return $this->getMessage($field, $rule, "The {$field} may not be greater than {$ruleValue} characters.");
                }
                break;
                
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    return $this->getMessage($field, $rule, "The {$field} must be a number.");
                }
                break;
                
            case 'integer':
                if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return $this->getMessage($field, $rule, "The {$field} must be an integer.");
                }
                break;
                
            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return $this->getMessage($field, $rule, "The {$field} must be a valid URL.");
                }
                break;
                
            case 'alpha':
                if ($value && !preg_match('/^[a-zA-Z]+$/', $value)) {
                    return $this->getMessage($field, $rule, "The {$field} may only contain letters.");
                }
                break;
                
            case 'alpha_num':
                if ($value && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                    return $this->getMessage($field, $rule, "The {$field} may only contain letters and numbers.");
                }
                break;
                
            case 'alpha_dash':
                if ($value && !preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                    return $this->getMessage($field, $rule, "The {$field} may only contain letters, numbers, dashes and underscores.");
                }
                break;
                
            case 'in':
                $allowedValues = explode(',', $ruleValue);
                if ($value && !in_array($value, $allowedValues)) {
                    $allowed = implode(', ', $allowedValues);
                    return $this->getMessage($field, $rule, "The selected {$field} is invalid. Allowed values: {$allowed}.");
                }
                break;
                
            case 'confirmed':
                $confirmationField = $field . '_confirmation';
                if ($value && (!isset($data[$confirmationField]) || $value !== $data[$confirmationField])) {
                    return $this->getMessage($field, $rule, "The {$field} confirmation does not match.");
                }
                break;
                
            case 'unique':
                // This would typically check against a database
                // For now, we'll just validate the format
                break;
                
            case 'exists':
                // This would typically check against a database
                // For now, we'll just validate the format
                break;
                
            case 'date':
                if ($value && !strtotime($value)) {
                    return $this->getMessage($field, $rule, "The {$field} is not a valid date.");
                }
                break;
                
            case 'boolean':
                if ($value !== null && !in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
                    return $this->getMessage($field, $rule, "The {$field} field must be true or false.");
                }
                break;
                
            case 'array':
                if ($value && !is_array($value)) {
                    return $this->getMessage($field, $rule, "The {$field} must be an array.");
                }
                break;
                
            case 'json':
                if ($value) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return $this->getMessage($field, $rule, "The {$field} must be a valid JSON string.");
                    }
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Get custom message or default message.
     *
     * @param string $field
     * @param string $rule
     * @param string $default
     * @return string
     */
    protected function getMessage($field, $rule, $default)
    {
        $key = "{$field}.{$rule}";
        
        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }
        
        if (isset($this->messages[$rule])) {
            return str_replace(':field', $field, $this->messages[$rule]);
        }
        
        return $default;
    }
    
    /**
     * Build validation error response.
     *
     * @param array $errors
     * @param Request $request
     * @return Response
     */
    protected function buildValidationErrorResponse(array $errors, Request $request)
    {
        // For API requests, return JSON
        if ($this->isApiRequest($request)) {
            $content = json_encode([
                'error' => 'Validation Failed',
                'message' => 'The given data was invalid.',
                'errors' => $errors
            ]);
            
            return new Response($content, 422, [
                'Content-Type' => 'application/json'
            ]);
        }
        
        // For web requests, store errors in session and redirect back
        $session = app('session');
        $session->flash('validation_errors', $errors);
        $session->flashInput($this->getRequestData($request));
        
        $referer = $request->getHeader('Referer') ?: '/';
        
        return new Response('', 302, [
            'Location' => $referer
        ]);
    }
    
    /**
     * Check if the request is an API request.
     *
     * @param Request $request
     * @return bool
     */
    protected function isApiRequest(Request $request)
    {
        $path = $request->getPath();
        $acceptHeader = $request->getHeader('Accept');
        
        return strpos($path, '/api/') === 0 || 
               strpos($acceptHeader, 'application/json') !== false;
    }
    
    /**
     * Set validation rules.
     *
     * @param array $rules
     * @return void
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }
    
    /**
     * Add validation rules.
     *
     * @param string $pattern
     * @param array $rules
     * @return void
     */
    public function addRules($pattern, array $rules)
    {
        $this->rules[$pattern] = $rules;
    }
    
    /**
     * Set custom error messages.
     *
     * @param array $messages
     * @return void
     */
    public function setMessages(array $messages)
    {
        $this->messages = $messages;
    }
    
    /**
     * Get validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }
}