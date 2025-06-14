<?php

namespace App\Core\Logging;

use Psr\Log\LogLevel;
use DateTime;

/**
 * Daily File Logger
 * 
 * File logger that creates daily log files and manages retention
 */
class DailyFileLogger extends FileLogger
{
    /**
     * @var string The log directory
     */
    protected $logDirectory;
    
    /**
     * @var int Number of days to retain logs
     */
    protected $retentionDays;
    
    /**
     * Constructor
     * 
     * @param string $logDirectory
     * @param string $level
     * @param int $retentionDays
     */
    public function __construct($logDirectory, $level = LogLevel::DEBUG, $retentionDays = 14)
    {
        $this->logDirectory = rtrim($logDirectory, DIRECTORY_SEPARATOR);
        $this->level = $level;
        $this->retentionDays = $retentionDays;
        
        // Ensure directory exists
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0755, true);
        }
        
        // Set current file path
        $this->updateFilePath();
        
        // Clean old logs
        $this->cleanOldLogs();
    }
    
    /**
     * Log a message
     * 
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        // Update file path in case date has changed
        $this->updateFilePath();
        
        parent::log($level, $message, $context);
    }
    
    /**
     * Update the current file path based on today's date
     * 
     * @return void
     */
    protected function updateFilePath()
    {
        $date = date('Y-m-d');
        $this->filePath = $this->logDirectory . DIRECTORY_SEPARATOR . "app-{$date}.log";
    }
    
    /**
     * Clean old log files based on retention policy
     * 
     * @return void
     */
    protected function cleanOldLogs()
    {
        if ($this->retentionDays <= 0) {
            return;
        }
        
        $cutoffDate = new DateTime();
        $cutoffDate->modify("-{$this->retentionDays} days");
        
        $files = glob($this->logDirectory . DIRECTORY_SEPARATOR . 'app-*.log');
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Extract date from filename (app-YYYY-MM-DD.log)
            if (preg_match('/app-(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches)) {
                $fileDate = DateTime::createFromFormat('Y-m-d', $matches[1]);
                
                if ($fileDate && $fileDate < $cutoffDate) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Get all log files in the directory
     * 
     * @return array
     */
    public function getLogFiles()
    {
        $files = glob($this->logDirectory . DIRECTORY_SEPARATOR . 'app-*.log');
        
        // Sort by date (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        return $files;
    }
    
    /**
     * Get log content for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @return string|null
     */
    public function getLogForDate($date)
    {
        $filePath = $this->logDirectory . DIRECTORY_SEPARATOR . "app-{$date}.log";
        
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }
        
        return null;
    }
    
    /**
     * Get recent log entries
     * 
     * @param int $lines Number of lines to retrieve
     * @return array
     */
    public function getRecentEntries($lines = 100)
    {
        $files = $this->getLogFiles();
        $entries = [];
        $totalLines = 0;
        
        foreach ($files as $file) {
            $fileLines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $fileLines = array_reverse($fileLines);
            
            foreach ($fileLines as $line) {
                if ($totalLines >= $lines) {
                    break 2;
                }
                
                $entries[] = $line;
                $totalLines++;
            }
        }
        
        return array_reverse($entries);
    }
}