<?php

namespace App\Core\Service;

use App\Core\Application;
use App\Core\Database\DatabaseManager;
use App\Core\Traits\Loggable;
use App\Core\Traits\Validatable;

/**
 * Base Service class for business logic layer
 */
abstract class Service
{
    use Loggable, Validatable;
    /**
     * @var Application The application instance
     */
    protected $app;
    
    /**
     * @var DatabaseManager The database manager
     */
    protected $db;
    
    /**
     * @var array Repository instances
     */
    protected $repositories = [];
    
    /**
     * Constructor
     * 
     * @param Application $app The application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->db = $app->make('App\Core\Database\DatabaseManager');
        $this->initializeRepositories();
    }
    
    /**
     * Initialize repositories used by this service
     * Override in child classes to specify repositories
     * 
     * @return void
     */
    protected function initializeRepositories()
    {
        // Override in child classes
    }
    
    /**
     * Get a repository instance
     * 
     * @param string $repository The repository class or alias
     * @return mixed
     */
    protected function getRepository($repository)
    {
        if (!isset($this->repositories[$repository])) {
            $this->repositories[$repository] = $this->app->make($repository);
        }
        
        return $this->repositories[$repository];
    }
    
    /**
     * Begin a database transaction
     * 
     * @return void
     */
    protected function beginTransaction()
    {
        $this->db->beginTransaction();
    }
    
    /**
     * Commit a database transaction
     * 
     * @return void
     */
    protected function commit()
    {
        $this->db->commit();
    }
    
    /**
     * Rollback a database transaction
     * 
     * @return void
     */
    protected function rollback()
    {
        $this->db->rollback();
    }
    
    /**
     * Execute a callback within a database transaction
     * 
     * @param callable $callback The callback to execute
     * @return mixed
     * @throws \Exception
     */
    protected function transaction(callable $callback)
    {
        $this->beginTransaction();
        
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Log a message
     * 
     * @param string $level The log level
     * @param string $message The log message
     * @param array $context Additional context
     * @return void
     */
    protected function log($level, $message, array $context = [])
    {
        if ($this->app->has(\Psr\Log\LoggerInterface::class)) {
            $logger = $this->app->make(\Psr\Log\LoggerInterface::class);
            $logger->log($level, $message, $context);
        }
    }
    
    /**
     * Validate input data
     * 
     * @param array $data The data to validate
     * @param array $rules The validation rules
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function validate(array $data, array $rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $fieldRules = is_string($rule) ? explode('|', $rule) : $rule;
            
            foreach ($fieldRules as $fieldRule) {
                $ruleParts = explode(':', $fieldRule);
                $ruleName = $ruleParts[0];
                $ruleValue = isset($ruleParts[1]) ? $ruleParts[1] : null;
                
                $value = isset($data[$field]) ? $data[$field] : null;
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = "The {$field} field is required.";
                        }
                        break;
                        
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "The {$field} must be a valid email address.";
                        }
                        break;
                        
                    case 'min':
                        if (!empty($value) && strlen($value) < (int)$ruleValue) {
                            $errors[$field][] = "The {$field} must be at least {$ruleValue} characters.";
                        }
                        break;
                        
                    case 'max':
                        if (!empty($value) && strlen($value) > (int)$ruleValue) {
                            $errors[$field][] = "The {$field} may not be greater than {$ruleValue} characters.";
                        }
                        break;
                        
                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field][] = "The {$field} must be a number.";
                        }
                        break;
                }
            }
        }
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . json_encode($errors));
        }
        
        return $data;
    }
    
    /**
     * Get the current authenticated user
     * 
     * @return mixed|null
     */
    protected function getCurrentUser()
    {
        if ($this->app->has('session')) {
            $session = $this->app->make('session');
            $userId = $session->get('user_id');
            
            if ($userId && $this->app->has('UserRepository')) {
                $userRepo = $this->app->make('UserRepository');
                return $userRepo->find($userId);
            }
        }
        
        return null;
    }
    
    /**
     * Format a response array
     * 
     * @param bool $success Whether the operation was successful
     * @param mixed $data The response data
     * @param string|null $message Optional message
     * @param array $errors Optional errors
     * @return array
     */
    protected function formatResponse($success, $data = null, $message = null, $errors = [])
    {
        $response = [
            'success' => $success,
            'data' => $data,
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return $response;
    }
}