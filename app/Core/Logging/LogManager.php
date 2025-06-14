<?php

namespace App\Core\Logging;

use App\Core\Application;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;

/**
 * Enhanced Log Manager
 * 
 * Provides centralized logging functionality with multiple channels,
 * formatters, and handlers following PSR-3 standards.
 */
class LogManager implements LoggerInterface
{
    /**
     * @var Application The application instance
     */
    protected $app;
    
    /**
     * @var array The log channels
     */
    protected $channels = [];
    
    /**
     * @var string The default channel
     */
    protected $defaultChannel = 'single';
    
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
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->configureChannels();
    }
    
    /**
     * Configure log channels
     * 
     * @return void
     */
    protected function configureChannels()
    {
        $config = $this->app->make('config');
        $loggingConfig = $config['logging'] ?? [];
        
        $this->defaultChannel = $loggingConfig['default'] ?? 'single';
        
        // Configure default channels
        $this->channels = [
            'single' => new FileLogger(
                $this->getLogPath('app.log'),
                $loggingConfig['level'] ?? LogLevel::DEBUG
            ),
            'daily' => new DailyFileLogger(
                $this->getLogPath(''),
                $loggingConfig['level'] ?? LogLevel::DEBUG,
                $loggingConfig['days'] ?? 14
            ),
            'error' => new FileLogger(
                $this->getLogPath('error.log'),
                LogLevel::ERROR
            ),
            'security' => new FileLogger(
                $this->getLogPath('security.log'),
                LogLevel::WARNING
            ),
        ];
    }
    
    /**
     * Get log file path
     * 
     * @param string $filename
     * @return string
     */
    protected function getLogPath($filename)
    {
        $logDir = storage_path('logs');
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        return $logDir . DIRECTORY_SEPARATOR . $filename;
    }
    
    /**
     * Get a log channel
     * 
     * @param string|null $channel
     * @return LoggerInterface
     */
    public function channel($channel = null)
    {
        $channel = $channel ?: $this->defaultChannel;
        
        if (!isset($this->channels[$channel])) {
            throw new RuntimeException("Log channel [{$channel}] not found.");
        }
        
        return $this->channels[$channel];
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
        $this->channel()->log($level, $message, $context);
    }
    
    /**
     * Log security events
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function security($message, array $context = [])
    {
        $this->channel('security')->warning($message, $context);
    }
    
    /**
     * Log performance metrics
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function performance($message, array $context = [])
    {
        if ($this->app->isDebug()) {
            $this->info("[PERFORMANCE] {$message}", $context);
        }
    }
    
    /**
     * Log database queries
     * 
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @return void
     */
    public function query($query, array $bindings = [], $time = null)
    {
        if ($this->app->isDebug()) {
            $message = "[QUERY] {$query}";
            if ($bindings) {
                $message .= " [BINDINGS: " . json_encode($bindings) . "]";
            }
            if ($time !== null) {
                $message .= " [TIME: {$time}ms]";
            }
            $this->debug($message);
        }
    }
}