<?php

namespace App\Core\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Not Found Exception
 * 
 * Can be used for general resource not found scenarios (e.g., in repositories)
 * and is also PSR-11 compliant for container 'service not found' exceptions.
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * The identifier of the resource or service that was not found.
     * Can be a service ID, model ID, class name, etc.
     *
     * @var string|int|null
     */
    protected $identifier;

    /**
     * Create a new not found exception.
     *
     * @param string $message The exception message.
     * @param string|int|null $identifier The identifier of the missing resource/service.
     * @param int $code The exception code (defaults to 404 for HTTP contexts).
     * @param Exception|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct(
        $message = 'Resource not found.', // More generic default message
        $identifier = null,
        $code = 404, // Default to 404 for general resource not found
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->identifier = $identifier;
    }

    /**
     * Get the identifier of the resource or service that was not found.
     *
     * @return string|int|null
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }



    /**
     * Create an exception for service not found
     *
     * @param string $serviceId
     * @return static
     */
    public static function service($serviceId)
    {
        $message = "Service '{$serviceId}' not found in container";
        return new static($message, $serviceId, 0); // PSR-11 doesn't mandate a specific code for service not found
    }

    /**
     * Create an exception for alias not found
     *
     * @param string $alias
     * @return static
     */
    public static function alias($alias)
    {
        $message = "Alias '{$alias}' not found in container";
        return new static($message, $alias, 0);
    }

    /**
     * Create an exception for tagged services not found
     *
     * @param string $tag
     * @return static
     */
    public static function tag($tag)
    {
        $message = "No services found with tag '{$tag}'";
        return new static($message, $tag, 0);
    }

    /**
     * Create an exception for class not found
     *
     * @param string $class
     * @return static
     */
    public static function class($class)
    {
        $message = "Class '{$class}' not found";
        // Retaining 0 code for class not found, as it might not always be an HTTP 404 context
        return new static($message, $class, 0);
    }

    /**
     * Create an exception for a model not found by its ID.
     *
     * @param string $modelName The name of the model (e.g., 'User', 'Product').
     * @param mixed $id The ID that was not found.
     * @return static
     */
    public static function model($modelName, $id)
    {
        $message = "{$modelName} with ID '{$id}' not found.";
        return new static($message, $id, 404);
    }

    /**
     * Create an exception for a generic resource not found.
     *
     * @param string $resourceType The type of resource (e.g., 'File', 'Configuration').
     * @param mixed $identifier The identifier of the resource.
     * @return static
     */
    public static function resource($resourceType, $identifier)
    {
        $message = "{$resourceType} '{$identifier}' not found.";
        return new static($message, $identifier, 404);
    }
}