<?php

namespace App\Core\Logging;

/**
 * Handler Interface
 * 
 * Defines the contract for log handlers
 */
interface HandlerInterface
{
    /**
     * Handle a log record
     * 
     * @param array $record
     * @return bool Whether the record was handled
     */
    public function handle(array $record);
    
    /**
     * Check if the handler can handle the given record
     * 
     * @param array $record
     * @return bool
     */
    public function isHandling(array $record);
    
    /**
     * Close the handler
     * 
     * @return void
     */
    public function close();
}