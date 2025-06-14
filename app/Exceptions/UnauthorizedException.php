<?php

namespace App\Exceptions;

/**
 * Exception thrown when a user is not authorized to perform an action
 */
class UnauthorizedException extends BaseException
{
    /**
     * @var string The exception type
     */
    protected $type = 'unauthorized';
    
    /**
     * @var int HTTP status code
     */
    protected $statusCode = 401;
    
    /**
     * Constructor
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Exception|null $previous The previous exception
     * @param array $context Additional context data
     */
    public function __construct($message = 'Unauthorized', $code = 0, \Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
    
    /**
     * Create an authentication required exception
     * 
     * @param string $message Custom message
     * @return static
     */
    public static function authenticationRequired($message = 'Authentication required')
    {
        return new static($message, 0, null, [
            'reason' => 'authentication_required'
        ]);
    }
    
    /**
     * Create an invalid credentials exception
     * 
     * @param string $message Custom message
     * @return static
     */
    public static function invalidCredentials($message = 'Invalid credentials')
    {
        return new static($message, 0, null, [
            'reason' => 'invalid_credentials'
        ]);
    }
    
    /**
     * Create a token expired exception
     * 
     * @param string $message Custom message
     * @return static
     */
    public static function tokenExpired($message = 'Token has expired')
    {
        return new static($message, 0, null, [
            'reason' => 'token_expired'
        ]);
    }
    
    /**
     * Create an invalid token exception
     * 
     * @param string $message Custom message
     * @return static
     */
    public static function invalidToken($message = 'Invalid token')
    {
        return new static($message, 0, null, [
            'reason' => 'invalid_token'
        ]);
    }
    
    /**
     * Check if this exception should be reported
     * 
     * @return bool
     */
    public function shouldReport()
    {
        return false; // Unauthorized errors are usually not reported
    }
}