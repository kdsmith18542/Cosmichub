<?php

namespace App\Core\Service;

use App\Core\Container\Container;
use Psr\Log\LoggerInterface;

/**
 * Base Service Class
 * 
 * Provides common functionality for all service classes
 * Part of the refactoring plan to establish a proper service layer
 */
abstract class BaseService
{
    /**
     * @var Container The application container
     */
    protected Container $container;
    
    /**
     * @var LoggerInterface The logger instance
     */
    protected LoggerInterface $logger;
    
    /**
     * Constructor
     * 
     * @param Container $container The application container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->logger = $container->get(\Psr\Log\LoggerInterface::class);
        
        // Call initialization hook
        $this->initialize();
    }
    
    /**
     * Initialize the service
     * Override this method in child classes for custom initialization
     */
    protected function initialize(): void
    {
        // Default implementation does nothing
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $service The service name
     * @return mixed The service instance
     */
    protected function get(string $service)
    {
        return $this->container->get($service);
    }
    
    /**
     * Check if a service exists in the container
     * 
     * @param string $service The service name
     * @return bool True if service exists
     */
    protected function has(string $service): bool
    {
        return $this->container->has($service);
    }
    
    /**
     * Log an info message
     * 
     * @param string $message The message
     * @param array $context Additional context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }
    
    /**
     * Log an error message
     * 
     * @param string $message The message
     * @param array $context Additional context
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
    
    /**
     * Log a warning message
     * 
     * @param string $message The message
     * @param array $context Additional context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }
    
    /**
     * Log a debug message
     * 
     * @param string $message The message
     * @param array $context Additional context
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }
    
    /**
     * Validate required parameters
     * 
     * @param array $data The data to validate
     * @param array $required The required keys
     * @throws \InvalidArgumentException If required parameters are missing
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        
        foreach ($required as $key) {
            if (!isset($data[$key]) || $data[$key] === null || $data[$key] === '') {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required parameters: ' . implode(', ', $missing)
            );
        }
    }
    
    /**
     * Sanitize input data
     * 
     * @param mixed $data The data to sanitize
     * @return mixed The sanitized data
     */
    protected function sanitize($data)
    {
        if (is_string($data)) {
            return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
        }
        
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return $data;
    }
    
    /**
     * Format response data
     * 
     * @param mixed $data The data to format
     * @param bool $success Whether the operation was successful
     * @param string|null $message Optional message
     * @return array Formatted response
     */
    protected function formatResponse($data = null, bool $success = true, ?string $message = null): array
    {
        $response = [
            'success' => $success,
            'data' => $data
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        return $response;
    }
    
    /**
     * Handle exceptions in a consistent way
     * 
     * @param \Exception $e The exception
     * @param string $operation The operation that failed
     * @return array Error response
     */
    protected function handleException(\Exception $e, string $operation = 'operation'): array
    {
        $this->logError("Error during {$operation}", [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return $this->formatResponse(
            null,
            false,
            "An error occurred during {$operation}: " . $e->getMessage()
        );
    }
}