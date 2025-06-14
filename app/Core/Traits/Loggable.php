<?php

namespace App\Core\Traits;

/**
 * Trait for adding logging capabilities to classes
 */
use Psr\Log\LoggerInterface;

trait Loggable
{
    /**
     * @var LoggerInterface|null The logger instance
     */
    protected $logger = null;

    /**
     * Set the logger instance.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * Log levels
     */
    const LOG_EMERGENCY = 'emergency';
    const LOG_ALERT = 'alert';
    const LOG_CRITICAL = 'critical';
    const LOG_ERROR = 'error';
    const LOG_WARNING = 'warning';
    const LOG_NOTICE = 'notice';
    const LOG_INFO = 'info';
    const LOG_DEBUG = 'debug';
    
    /**
     * Log directory
     */
    protected $logDirectory = null;
    
    /**
     * Get the log directory
     * 
     * @return string
     */
    protected function getLogDirectory()
    {
        if ($this->logDirectory === null) {
            $this->logDirectory = $this->getBasePath() . '/storage/logs';
            
            // Create directory if it doesn't exist
            if (!is_dir($this->logDirectory)) {
                mkdir($this->logDirectory, 0755, true);
            }
        }
        
        return $this->logDirectory;
    }
    
    /**
     * Get base path - override this method in classes that use this trait
     * 
     * @return string
     */
    protected function getBasePath()
    {
        // Try to get from application instance if available
        if (isset($this->app) && method_exists($this->app, 'getBasePath')) {
            return $this->app->getBasePath();
        }
        
        // Fallback to current working directory
        return getcwd();
    }
    
    /**
     * Log an emergency message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logEmergency($message, array $context = [])
    {
        $this->log(self::LOG_EMERGENCY, $message, $context);
    }
    
    /**
     * Log an alert message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logAlert($message, array $context = [])
    {
        $this->log(self::LOG_ALERT, $message, $context);
    }
    
    /**
     * Log a critical message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logCritical($message, array $context = [])
    {
        $this->log(self::LOG_CRITICAL, $message, $context);
    }
    
    /**
     * Log an error message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError($message, array $context = [])
    {
        $this->log(self::LOG_ERROR, $message, $context);
    }
    
    /**
     * Log a warning message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logWarning($message, array $context = [])
    {
        $this->log(self::LOG_WARNING, $message, $context);
    }
    
    /**
     * Log a notice message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logNotice($message, array $context = [])
    {
        $this->log(self::LOG_NOTICE, $message, $context);
    }
    
    /**
     * Log an info message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo($message, array $context = [])
    {
        $this->log(self::LOG_INFO, $message, $context);
    }
    
    /**
     * Log a debug message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logDebug($message, array $context = [])
    {
        $this->log(self::LOG_DEBUG, $message, $context);
    }
    
    /**
     * Log a message with the given level
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log($level, $message, array $context = [])
    {
        try {
            $logEntry = $this->formatLogEntry($level, $message, $context);
            $logFile = $this->getLogFile($level);
            
            file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // Use the injected logger if available, otherwise fallback to error_log
            if ($this->logger) {
                $this->logger->error("Failed to write to log file: " . $e->getMessage());
            } else {
                \App\Support\Log::error("Failed to write to log file: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Format a log entry
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function formatLogEntry($level, $message, array $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Get calling class and method
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $caller = $this->getCallerInfo($backtrace);
        
        // Interpolate context variables into message
        $message = $this->interpolate($message, $context);
        
        // Format the log entry
        $logEntry = "[{$timestamp}] {$levelUpper}: {$message}";
        
        if ($caller) {
            $logEntry .= " [{$caller}]";
        }
        
        // Add context if present
        if (!empty($context)) {
            $logEntry .= " Context: " . json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        
        return $logEntry;
    }
    
    /**
     * Get caller information from backtrace
     * 
     * @param array $backtrace
     * @return string|null
     */
    protected function getCallerInfo(array $backtrace)
    {
        // Skip the log methods to find the actual caller
        foreach ($backtrace as $trace) {
            if (isset($trace['class']) && isset($trace['function'])) {
                $class = $trace['class'];
                $function = $trace['function'];
                
                // Skip logging trait methods
                if (strpos($function, 'log') !== 0 || $class !== static::class) {
                    return $class . '::' . $function;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Interpolate context values into the message placeholders
     * 
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function interpolate($message, array $context = [])
    {
        // Build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // Check that the value can be cast to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        
        // Interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
    
    /**
     * Get the log file path for the given level
     * 
     * @param string $level
     * @return string
     */
    protected function getLogFile($level)
    {
        $logDir = $this->getLogDirectory();
        $date = date('Y-m-d');
        
        // Use separate files for different log levels
        switch ($level) {
            case self::LOG_EMERGENCY:
            case self::LOG_ALERT:
            case self::LOG_CRITICAL:
            case self::LOG_ERROR:
                return $logDir . "/error-{$date}.log";
                
            case self::LOG_WARNING:
            case self::LOG_NOTICE:
                return $logDir . "/warning-{$date}.log";
                
            case self::LOG_INFO:
                return $logDir . "/info-{$date}.log";
                
            case self::LOG_DEBUG:
                return $logDir . "/debug-{$date}.log";
                
            default:
                return $logDir . "/app-{$date}.log";
        }
    }
    
    /**
     * Log an exception
     * 
     * @param \Exception|\Throwable $exception
     * @param string $level
     * @param array $context
     * @return void
     */
    protected function logException($exception, $level = self::LOG_ERROR, array $context = [])
    {
        $message = 'Exception: ' . $exception->getMessage();
        
        $context = array_merge($context, [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        $this->log($level, $message, $context);
    }
    
    /**
     * Log a database query
     * 
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @return void
     */
    protected function logQuery($query, array $bindings = [], $time = null)
    {
        $context = ['bindings' => $bindings];
        
        if ($time !== null) {
            $context['time'] = $time . 'ms';
        }
        
        $this->log(self::LOG_DEBUG, 'Database Query: ' . $query, $context);
    }
}