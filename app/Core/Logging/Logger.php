<?php

namespace App\Core\Logging;

use App\Core\Exceptions\ServiceException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

/**
 * Logger Class
 * 
 * PSR-3 compliant logger with support for multiple channels,
 * handlers, formatters, and log rotation
 */
class Logger implements LoggerInterface
{
    /**
     * @var string Logger name/channel
     */
    protected $name;
    
    /**
     * @var array Log handlers
     */
    protected $handlers = [];
    
    /**
     * @var array Log processors
     */
    protected $processors = [];
    
    /**
     * @var string Default log level
     */
    protected $level = LogLevel::DEBUG;
    
    /**
     * @var array Log level hierarchy
     */
    protected static $levels = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];
    
    /**
     * @var array Context data
     */
    protected $context = [];
    
    /**
     * @var bool Whether logging is enabled
     */
    protected $enabled = true;
    
    /**
     * @var array Statistics
     */
    protected $stats = [
        'total' => 0,
        'by_level' => [],
        'errors' => 0
    ];
    
    /**
     * Create a new logger instance
     * 
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct($name = 'app', array $handlers = [], array $processors = [])
    {
        $this->name = $name;
        $this->handlers = $handlers;
        $this->processors = $processors;
        
        // Initialize level statistics
        foreach (self::$levels as $level => $priority) {
            $this->stats['by_level'][$level] = 0;
        }
        
        // Add default file handler if no handlers provided
        if (empty($this->handlers)) {
            $this->addHandler(new FileHandler());
        }
    }
    
    /**
     * System is unusable.
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
     * Action must be taken immediately.
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
     * Critical conditions.
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
     * Runtime errors that do not require immediate action.
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
     * Exceptional occurrences that are not errors.
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
     * Normal but significant events.
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
     * Interesting events.
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
     * Detailed debug information.
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
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->enabled) {
            return;
        }
        
        if (!isset(self::$levels[$level])) {
            throw new InvalidArgumentException("Invalid log level: {$level}");
        }
        
        // Check if level should be logged
        if (self::$levels[$level] < self::$levels[$this->level]) {
            return;
        }
        
        // Create log record
        $record = $this->createRecord($level, $message, $context);
        
        // Process record through processors
        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }
        
        // Handle record through handlers
        foreach ($this->handlers as $handler) {
            try {
                $handler->handle($record);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                // Don't let handler errors break logging
                // This error_log is intentionally kept as a fallback for critical logger handler errors
                error_log("Logger handler error: {$e->getMessage()}");
            }
        }
        
        // Update statistics
        $this->stats['total']++;
        $this->stats['by_level'][$level]++;
    }
    
    /**
     * Add a log handler
     * 
     * @param HandlerInterface $handler
     * @return $this
     */
    public function addHandler(HandlerInterface $handler)
    {
        $this->handlers[] = $handler;
        return $this;
    }
    
    /**
     * Remove a log handler
     * 
     * @param HandlerInterface $handler
     * @return $this
     */
    public function removeHandler(HandlerInterface $handler)
    {
        $key = array_search($handler, $this->handlers, true);
        if ($key !== false) {
            unset($this->handlers[$key]);
            $this->handlers = array_values($this->handlers);
        }
        return $this;
    }
    
    /**
     * Get all handlers
     * 
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }
    
    /**
     * Add a log processor
     * 
     * @param callable $processor
     * @return $this
     */
    public function addProcessor(callable $processor)
    {
        $this->processors[] = $processor;
        return $this;
    }
    
    /**
     * Remove a log processor
     * 
     * @param callable $processor
     * @return $this
     */
    public function removeProcessor(callable $processor)
    {
        $key = array_search($processor, $this->processors, true);
        if ($key !== false) {
            unset($this->processors[$key]);
            $this->processors = array_values($this->processors);
        }
        return $this;
    }
    
    /**
     * Get all processors
     * 
     * @return array
     */
    public function getProcessors()
    {
        return $this->processors;
    }
    
    /**
     * Set minimum log level
     * 
     * @param string $level
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setLevel($level)
    {
        if (!isset(self::$levels[$level])) {
            throw new InvalidArgumentException("Invalid log level: {$level}");
        }
        
        $this->level = $level;
        return $this;
    }
    
    /**
     * Get current log level
     * 
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }
    
    /**
     * Set logger name
     * 
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Get logger name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Add context data
     * 
     * @param array $context
     * @return $this
     */
    public function withContext(array $context)
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }
    
    /**
     * Set context data
     * 
     * @param array $context
     * @return $this
     */
    public function setContext(array $context)
    {
        $this->context = $context;
        return $this;
    }
    
    /**
     * Get context data
     * 
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
    
    /**
     * Enable or disable logging
     * 
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
    
    /**
     * Check if logging is enabled
     * 
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
    
    /**
     * Get logging statistics
     * 
     * @return array
     */
    public function getStats()
    {
        return $this->stats;
    }
    
    /**
     * Reset statistics
     * 
     * @return $this
     */
    public function resetStats()
    {
        $this->stats = [
            'total' => 0,
            'by_level' => [],
            'errors' => 0
        ];
        
        foreach (self::$levels as $level => $priority) {
            $this->stats['by_level'][$level] = 0;
        }
        
        return $this;
    }
    
    /**
     * Check if a log level should be logged
     * 
     * @param string $level
     * @return bool
     */
    public function isHandling($level)
    {
        return isset(self::$levels[$level]) && 
               self::$levels[$level] >= self::$levels[$this->level];
    }
    
    /**
     * Create a log record
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return array
     */
    protected function createRecord($level, $message, array $context)
    {
        return [
            'message' => $message,
            'context' => array_merge($this->context, $context),
            'level' => $level,
            'level_name' => strtoupper($level),
            'channel' => $this->name,
            'datetime' => new \DateTime(),
            'extra' => [],
        ];
    }
    
    /**
     * Interpolate context values into message placeholders
     * 
     * @param string $message
     * @param array $context
     * @return string
     */
    public static function interpolate($message, array $context = [])
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
     * Create logger with file handler
     * 
     * @param string $name
     * @param string $file
     * @param string $level
     * @return static
     */
    public static function createFileLogger($name, $file, $level = LogLevel::DEBUG)
    {
        $logger = new static($name);
        $logger->addHandler(new FileHandler($file));
        $logger->setLevel($level);
        return $logger;
    }
    
    /**
     * Create logger with multiple handlers
     * 
     * @param string $name
     * @param array $handlers
     * @param string $level
     * @return static
     */
    public static function createMultiHandler($name, array $handlers, $level = LogLevel::DEBUG)
    {
        $logger = new static($name, $handlers);
        $logger->setLevel($level);
        return $logger;
    }
    
    /**
     * Create null logger (no output)
     * 
     * @param string $name
     * @return static
     */
    public static function createNullLogger($name = 'null')
    {
        $logger = new static($name, [new NullHandler()]);
        return $logger;
    }
    
    /**
     * Get available log levels
     * 
     * @return array
     */
    public static function getLevels()
    {
        return self::$levels;
    }
    
    /**
     * Check if a level is valid
     * 
     * @param string $level
     * @return bool
     */
    public static function isValidLevel($level)
    {
        return isset(self::$levels[$level]);
    }
}