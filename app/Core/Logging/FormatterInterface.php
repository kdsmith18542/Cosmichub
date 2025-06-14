<?php

namespace App\Core\Logging;

/**
 * Formatter Interface
 * 
 * Defines the contract for log formatters
 */
interface FormatterInterface
{
    /**
     * Format a log record
     * 
     * @param array $record
     * @return string
     */
    public function format(array $record);
    
    /**
     * Format multiple log records
     * 
     * @param array $records
     * @return string
     */
    public function formatBatch(array $records);
}