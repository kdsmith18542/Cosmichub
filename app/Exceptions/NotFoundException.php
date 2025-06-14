<?php

namespace App\Exceptions;

/**
 * Exception thrown when a resource is not found
 */
class NotFoundException extends BaseException
{
    /**
     * @var string The exception type
     */
    protected $type = 'not_found';
    
    /**
     * @var int HTTP status code
     */
    protected $statusCode = 404;
    
    /**
     * Constructor
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Exception|null $previous The previous exception
     * @param array $context Additional context data
     */
    public function __construct($message = 'Resource not found', $code = 0, \Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
    
    /**
     * Create a model not found exception
     * 
     * @param string $model The model name
     * @param mixed $id The model ID
     * @return static
     */
    public static function model($model, $id = null)
    {
        $message = $id !== null 
            ? "No query results for model [{$model}] {$id}"
            : "No query results for model [{$model}]";
            
        return new static($message, 0, null, [
            'model' => $model,
            'id' => $id
        ]);
    }
    
    /**
     * Create a route not found exception
     * 
     * @param string $route The route
     * @return static
     */
    public static function route($route)
    {
        return new static("Route [{$route}] not found", 0, null, [
            'route' => $route
        ]);
    }
    
    /**
     * Create a file not found exception
     * 
     * @param string $file The file path
     * @return static
     */
    public static function file($file)
    {
        return new static("File [{$file}] not found", 0, null, [
            'file' => $file
        ]);
    }
    
    /**
     * Create a view not found exception
     * 
     * @param string $view The view name
     * @return static
     */
    public static function view($view)
    {
        return new static("View [{$view}] not found", 0, null, [
            'view' => $view
        ]);
    }
    
    /**
     * Check if this exception should be reported
     * 
     * @return bool
     */
    public function shouldReport()
    {
        return false; // 404 errors are usually not reported
    }
}