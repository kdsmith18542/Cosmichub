<?php

namespace App\Core\Services;

use App\Core\Container\Container;
use App\Core\Logging\Logger;
use App\Core\Exceptions\ServiceException;
use App\Core\Events\EventDispatcher;
use App\Core\Validation\Validator;

/**
 * Base Service Class
 * 
 * Provides a foundation for all service classes with common functionality
 * including dependency injection, logging, validation, and event handling
 */
abstract class Service
{
    /**
     * @var Container The application container
     */
    protected $container;
    
    /**
     * @var Logger The logger instance
     */
    protected $logger;
    
    /**
     * @var EventDispatcher The event dispatcher
     */
    protected $events;
    
    /**
     * @var Validator The validator instance
     */
    protected $validator;
    
    /**
     * @var array Service configuration
     */
    protected $config = [];
    
    /**
     * @var array Validation rules for service methods
     */
    protected $validationRules = [];
    
    /**
     * @var array Custom error messages for validation
     */
    protected $validationMessages = [];
    
    /**
     * @var bool Whether to log service operations
     */
    protected $enableLogging = true;
    
    /**
     * @var bool Whether to dispatch events
     */
    protected $enableEvents = true;
    
    /**
     * @var bool Whether to validate input data
     */
    protected $enableValidation = true;
    
    /**
     * Create a new service instance
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
        $this->events = $container->get('events');
        $this->validator = $container->get('validator');
        
        $this->boot();
    }
    
    /**
     * Boot the service
     * 
     * Override this method to perform initialization logic
     * 
     * @return void
     */
    protected function boot()
    {
        // Override in child classes
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $service
     * @return mixed
     */
    protected function service($service)
    {
        return $this->container->get($service);
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function config($key, $default = null)
    {
        return $this->container->get('config')->get($key, $default);
    }
    
    /**
     * Log a message
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log($level, $message, array $context = [])
    {
        if ($this->enableLogging && $this->logger) {
            $context['service'] = static::class;
            $this->logger->log($level, $message, $context);
        }
    }
    
    /**
     * Log an info message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }
    
    /**
     * Log an error message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }
    
    /**
     * Log a warning message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logWarning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Log a debug message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logDebug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }
    
    /**
     * Dispatch an event
     * 
     * @param string $event
     * @param array $payload
     * @return mixed
     */
    protected function dispatch($event, array $payload = [])
    {
        if ($this->enableEvents && $this->events) {
            return $this->events->dispatch($event, $payload);
        }
        
        return null;
    }
    
    /**
     * Validate data against rules
     * 
     * @param array $data
     * @param array|string $rules
     * @param array $messages
     * @return array
     * @throws ServiceException
     */
    protected function validate(array $data, $rules = null, array $messages = [])
    {
        if (!$this->enableValidation || !$this->validator) {
            return $data;
        }
        
        // Use method-specific rules if not provided
        if (is_null($rules)) {
            $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            $rules = $this->validationRules[$method] ?? [];
        }
        
        // Use method-specific messages if not provided
        if (empty($messages)) {
            $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            $messages = $this->validationMessages[$method] ?? $this->validationMessages;
        }
        
        if (empty($rules)) {
            return $data;
        }
        
        $validator = $this->validator->make($data, $rules, $messages);
        
        if ($validator->fails()) {
            throw ServiceException::validationFailed(
                'Validation failed',
                $validator->errors()
            );
        }
        
        return $validator->validated();
    }
    
    /**
     * Execute a service operation with error handling
     * 
     * @param callable $operation
     * @param string $operationName
     * @param array $context
     * @return mixed
     * @throws ServiceException
     */
    protected function execute(callable $operation, $operationName = 'operation', array $context = [])
    {
        $startTime = microtime(true);
        
        try {
            $this->logInfo("Starting {$operationName}", $context);
            
            $this->dispatch("service.{$operationName}.starting", [
                'service' => static::class,
                'context' => $context
            ]);
            
            $result = $operation();
            
            $duration = microtime(true) - $startTime;
            
            $this->logInfo("Completed {$operationName}", array_merge($context, [
                'duration' => $duration
            ]));
            
            $this->dispatch("service.{$operationName}.completed", [
                'service' => static::class,
                'result' => $result,
                'context' => $context,
                'duration' => $duration
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            $this->logError("Failed {$operationName}: {$e->getMessage()}", array_merge($context, [
                'exception' => $e,
                'duration' => $duration
            ]));
            
            $this->dispatch("service.{$operationName}.failed", [
                'service' => static::class,
                'exception' => $e,
                'context' => $context,
                'duration' => $duration
            ]);
            
            if ($e instanceof ServiceException) {
                throw $e;
            }
            
            throw ServiceException::operationFailed(
                "Service operation '{$operationName}' failed: {$e->getMessage()}",
                $e
            );
        }
    }
    
    /**
     * Execute a database transaction
     * 
     * @param callable $callback
     * @param string $connection
     * @return mixed
     * @throws ServiceException
     */
    protected function transaction(callable $callback, $connection = null)
    {
        $db = $this->container->get('database');
        
        try {
            return $db->transaction($callback, $connection);
        } catch (\Exception $e) {
            $this->logError('Transaction failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            throw ServiceException::transactionFailed(
                'Database transaction failed: ' . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Cache a value
     * 
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    protected function cache($key, $value, $ttl = 3600)
    {
        if ($cache = $this->container->get('cache')) {
            return $cache->set($key, $value, $ttl);
        }
        
        return false;
    }
    
    /**
     * Get a cached value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getCached($key, $default = null)
    {
        if ($cache = $this->container->get('cache')) {
            return $cache->get($key, $default);
        }
        
        return $default;
    }
    
    /**
     * Remove a cached value
     * 
     * @param string $key
     * @return bool
     */
    protected function forgetCache($key)
    {
        if ($cache = $this->container->get('cache')) {
            return $cache->delete($key);
        }
        
        return false;
    }
    
    /**
     * Remember a value in cache
     * 
     * @param string $key
     * @param callable $callback
     * @param int $ttl
     * @return mixed
     */
    protected function remember($key, callable $callback, $ttl = 3600)
    {
        $value = $this->getCached($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->cache($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Get the service name
     * 
     * @return string
     */
    public function getName()
    {
        return static::class;
    }
    
    /**
     * Enable or disable logging
     * 
     * @param bool $enable
     * @return $this
     */
    public function setLogging($enable)
    {
        $this->enableLogging = $enable;
        return $this;
    }
    
    /**
     * Enable or disable events
     * 
     * @param bool $enable
     * @return $this
     */
    public function setEvents($enable)
    {
        $this->enableEvents = $enable;
        return $this;
    }
    
    /**
     * Enable or disable validation
     * 
     * @param bool $enable
     * @return $this
     */
    public function setValidation($enable)
    {
        $this->enableValidation = $enable;
        return $this;
    }
    
    /**
     * Set validation rules for a method
     * 
     * @param string $method
     * @param array $rules
     * @return $this
     */
    public function setValidationRules($method, array $rules)
    {
        $this->validationRules[$method] = $rules;
        return $this;
    }
    
    /**
     * Set validation messages for a method
     * 
     * @param string $method
     * @param array $messages
     * @return $this
     */
    public function setValidationMessages($method, array $messages)
    {
        $this->validationMessages[$method] = $messages;
        return $this;
    }
    
    /**
     * Handle dynamic method calls
     * 
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws ServiceException
     */
    public function __call($method, $parameters)
    {
        throw ServiceException::methodNotFound(
            "Method '{$method}' not found in service '" . static::class . "'"
        );
    }
    
    /**
     * Handle dynamic static method calls
     * 
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws ServiceException
     */
    public static function __callStatic($method, $parameters)
    {
        throw ServiceException::methodNotFound(
            "Static method '{$method}' not found in service '" . static::class . "'"
        );
    }
}