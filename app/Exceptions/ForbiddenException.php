<?php

namespace App\Exceptions;

/**
 * Exception thrown when a user is forbidden from performing an action
 */
class ForbiddenException extends BaseException
{
    /**
     * @var string The exception type
     */
    protected $type = 'forbidden';
    
    /**
     * @var int HTTP status code
     */
    protected $statusCode = 403;
    
    /**
     * Constructor
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Exception|null $previous The previous exception
     * @param array $context Additional context data
     */
    public function __construct($message = 'Forbidden', $code = 0, \Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
    
    /**
     * Create an insufficient permissions exception
     * 
     * @param string $permission The required permission
     * @param string $resource The resource being accessed
     * @return static
     */
    public static function insufficientPermissions($permission = null, $resource = null)
    {
        $message = 'Insufficient permissions';
        
        if ($permission && $resource) {
            $message = "Insufficient permissions to {$permission} {$resource}";
        } elseif ($permission) {
            $message = "Insufficient permissions: {$permission} required";
        } elseif ($resource) {
            $message = "Insufficient permissions to access {$resource}";
        }
        
        return new static($message, 0, null, [
            'reason' => 'insufficient_permissions',
            'permission' => $permission,
            'resource' => $resource
        ]);
    }
    
    /**
     * Create an account suspended exception
     * 
     * @param string $reason The suspension reason
     * @return static
     */
    public static function accountSuspended($reason = null)
    {
        $message = 'Account has been suspended';
        
        if ($reason) {
            $message .= ": {$reason}";
        }
        
        return new static($message, 0, null, [
            'reason' => 'account_suspended',
            'suspension_reason' => $reason
        ]);
    }
    
    /**
     * Create an account not verified exception
     * 
     * @return static
     */
    public static function accountNotVerified()
    {
        return new static('Account email not verified', 0, null, [
            'reason' => 'account_not_verified'
        ]);
    }
    
    /**
     * Create a subscription required exception
     * 
     * @param string $feature The feature requiring subscription
     * @return static
     */
    public static function subscriptionRequired($feature = null)
    {
        $message = 'Subscription required';
        
        if ($feature) {
            $message = "Subscription required to access {$feature}";
        }
        
        return new static($message, 0, null, [
            'reason' => 'subscription_required',
            'feature' => $feature
        ]);
    }
    
    /**
     * Create a rate limit exceeded exception
     * 
     * @param int $retryAfter Seconds until retry is allowed
     * @return static
     */
    public static function rateLimitExceeded($retryAfter = null)
    {
        $message = 'Rate limit exceeded';
        
        if ($retryAfter) {
            $message .= ". Try again in {$retryAfter} seconds";
        }
        
        return new static($message, 0, null, [
            'reason' => 'rate_limit_exceeded',
            'retry_after' => $retryAfter
        ]);
    }
    
    /**
     * Check if this exception should be reported
     * 
     * @return bool
     */
    public function shouldReport()
    {
        return false; // Forbidden errors are usually not reported
    }
}