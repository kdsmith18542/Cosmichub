<?php

namespace App\Core\Logging;

/**
 * Null Handler
 * 
 * A handler that does nothing - useful for testing or disabling logging
 */
class NullHandler implements HandlerInterface
{
    /**
     * Handle a log record
     * 
     * @param array $record
     * @return bool
     */
    public function handle(array $record)
    {
        return true;
    }
    
    /**
     * Check if the handler can handle the given record
     * 
     * @param array $record
     * @return bool
     */
    public function isHandling(array $record)
    {
        return true;
    }
    
    /**
     * Close the handler
     * 
     * @return void
     */
    public function close()
    {
        // Nothing to close
    }
}