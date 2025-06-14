<?php

namespace App\Core\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use DateTime;

/**
 * File Logger
 * 
 * Simple file-based logger implementation
 */
class FileLogger implements LoggerInterface
{
    /**
     * @var string The log file path
     */
    protected $filePath;
    
    /**
     * @var string The minimum log level
     */
    protected $level;
    
    /**
     * @var array The log levels
     */
    protected $levels = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];
    
    /**
     * Constructor
     * 
     * @param string $filePath
     * @param string $level
     */
    public function __construct($filePath, $level = LogLevel::DEBUG)
    {
        $this->filePath = $filePath;
        $this->level = $level;
        
        // Ensure directory exists
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    /**
     * Log an emergency message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    
    /**
     * Log an alert message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }
    
    /**
     * Log a critical message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
    
    /**
     * Log an error message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    
    /**
     * Log a warning message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }
    
    /**
     * Log a notice message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }
    
    /**
     * Log an info message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
    
    /**
     * Log a debug message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
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
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $formattedMessage = $this->formatMessage($level, $message, $context);
        
        file_put_contents(
            $this->filePath,
            $formattedMessage . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
    
    /**
     * Check if message should be logged
     * 
     * @param string $level
     * @return bool
     */
    protected function shouldLog($level)
    {
        return isset($this->levels[$level]) && 
               $this->levels[$level] <= $this->levels[$this->level];
    }
    
    /**
     * Format log message
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function formatMessage($level, $message, array $context = [])
    {
        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Interpolate context values into message
        $message = $this->interpolate($message, $context);
        
        $formatted = "[{$timestamp}] {$levelUpper}: {$message}";
        
        // Add context if present
        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        
        return $formatted;
    }
    
    /**
     * Interpolate context values into message placeholders
     * 
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function interpolate($message, array $context = [])
    {
        $replace = [];
        
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        
        return strtr($message, $replace);
    }
}