<?php

namespace App\Support;

/**
 * Log helper class
 */
class Log
{
    /**
     * Log levels
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * Log level priorities
     *
     * @var array
     */
    protected static array $levels = [
        self::EMERGENCY => 0,
        self::ALERT => 1,
        self::CRITICAL => 2,
        self::ERROR => 3,
        self::WARNING => 4,
        self::NOTICE => 5,
        self::INFO => 6,
        self::DEBUG => 7,
    ];

    /**
     * The log directory
     *
     * @var string
     */
    protected static string $directory = '';

    /**
     * The minimum log level
     *
     * @var string
     */
    protected static string $minLevel = self::DEBUG;

    /**
     * Whether to log to file
     *
     * @var bool
     */
    protected static bool $logToFile = true;

    /**
     * Whether to log to console
     *
     * @var bool
     */
    protected static bool $logToConsole = false;

    /**
     * Maximum log file size in bytes
     *
     * @var int
     */
    protected static int $maxFileSize = 10485760; // 10MB

    /**
     * Maximum number of log files to keep
     *
     * @var int
     */
    protected static int $maxFiles = 5;

    /**
     * Set the log directory
     *
     * @param string $directory
     * @return void
     */
    public static function setDirectory(string $directory): void
    {
        static::$directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        
        if (!is_dir(static::$directory)) {
            mkdir(static::$directory, 0755, true);
        }
    }

    /**
     * Set the minimum log level
     *
     * @param string $level
     * @return void
     */
    public static function setMinLevel(string $level): void
    {
        if (isset(static::$levels[$level])) {
            static::$minLevel = $level;
        }
    }

    /**
     * Enable or disable file logging
     *
     * @param bool $enabled
     * @return void
     */
    public static function setFileLogging(bool $enabled): void
    {
        static::$logToFile = $enabled;
    }

    /**
     * Enable or disable console logging
     *
     * @param bool $enabled
     * @return void
     */
    public static function setConsoleLogging(bool $enabled): void
    {
        static::$logToConsole = $enabled;
    }

    /**
     * Set maximum file size
     *
     * @param int $size
     * @return void
     */
    public static function setMaxFileSize(int $size): void
    {
        static::$maxFileSize = $size;
    }

    /**
     * Set maximum number of files
     *
     * @param int $files
     * @return void
     */
    public static function setMaxFiles(int $files): void
    {
        static::$maxFiles = $files;
    }

    /**
     * Log an emergency message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function emergency(string $message, array $context = []): void
    {
        static::log(static::EMERGENCY, $message, $context);
    }

    /**
     * Log an alert message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function alert(string $message, array $context = []): void
    {
        static::log(static::ALERT, $message, $context);
    }

    /**
     * Log a critical message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        static::log(static::CRITICAL, $message, $context);
    }

    /**
     * Log an error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        static::log(static::ERROR, $message, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        static::log(static::WARNING, $message, $context);
    }

    /**
     * Log a notice message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function notice(string $message, array $context = []): void
    {
        static::log(static::NOTICE, $message, $context);
    }

    /**
     * Log an info message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        static::log(static::INFO, $message, $context);
    }

    /**
     * Log a debug message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        static::log(static::DEBUG, $message, $context);
    }

    /**
     * Log a message with the given level
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        if (!static::shouldLog($level)) {
            return;
        }

        $formattedMessage = static::formatMessage($level, $message, $context);

        if (static::$logToFile && static::$directory) {
            static::writeToFile($level, $formattedMessage);
        }

        if (static::$logToConsole) {
            static::writeToConsole($level, $formattedMessage);
        }
    }

    /**
     * Log an exception
     *
     * @param \Throwable $exception
     * @param string $level
     * @param array $context
     * @return void
     */
    public static function exception(\Throwable $exception, string $level = self::ERROR, array $context = []): void
    {
        $message = sprintf(
            '%s: %s in %s:%d',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        static::log($level, $message, $context);
    }

    /**
     * Check if a level should be logged
     *
     * @param string $level
     * @return bool
     */
    protected static function shouldLog(string $level): bool
    {
        if (!isset(static::$levels[$level])) {
            return false;
        }

        return static::$levels[$level] <= static::$levels[static::$minLevel];
    }

    /**
     * Format a log message
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected static function formatMessage(string $level, string $message, array $context = []): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Replace placeholders in message
        $message = static::interpolate($message, $context);
        
        $formatted = "[{$timestamp}] {$levelUpper}: {$message}";
        
        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
    protected static function interpolate(string $message, array $context = []): string
    {
        $replace = [];
        
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        
        return strtr($message, $replace);
    }

    /**
     * Write log message to file
     *
     * @param string $level
     * @param string $message
     * @return void
     */
    protected static function writeToFile(string $level, string $message): void
    {
        $filename = static::getLogFilename($level);
        
        // Check if file needs rotation
        if (file_exists($filename) && filesize($filename) >= static::$maxFileSize) {
            static::rotateLogFile($filename);
        }
        
        file_put_contents($filename, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Write log message to console
     *
     * @param string $level
     * @param string $message
     * @return void
     */
    protected static function writeToConsole(string $level, string $message): void
    {
        $colors = [
            static::EMERGENCY => "\033[1;41m", // Red background
            static::ALERT => "\033[1;41m",     // Red background
            static::CRITICAL => "\033[1;31m",  // Red
            static::ERROR => "\033[0;31m",     // Red
            static::WARNING => "\033[0;33m",   // Yellow
            static::NOTICE => "\033[0;36m",    // Cyan
            static::INFO => "\033[0;32m",      // Green
            static::DEBUG => "\033[0;37m",     // White
        ];
        
        $reset = "\033[0m";
        $color = $colors[$level] ?? $colors[static::DEBUG];
        
        if (PHP_SAPI === 'cli') {
            echo $color . $message . $reset . PHP_EOL;
        } else {
            echo $message . PHP_EOL;
        }
    }

    /**
     * Get the log filename for a level
     *
     * @param string $level
     * @return string
     */
    protected static function getLogFilename(string $level): string
    {
        $date = date('Y-m-d');
        return static::$directory . "app-{$date}.log";
    }

    /**
     * Rotate log file when it gets too large
     *
     * @param string $filename
     * @return void
     */
    protected static function rotateLogFile(string $filename): void
    {
        $info = pathinfo($filename);
        $base = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'];
        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';
        
        // Rotate existing files
        for ($i = static::$maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $base . '.' . $i . $extension;
            $newFile = $base . '.' . ($i + 1) . $extension;
            
            if (file_exists($oldFile)) {
                if ($i === static::$maxFiles - 1) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Move current file to .1
        if (file_exists($filename)) {
            rename($filename, $base . '.1' . $extension);
        }
    }

    /**
     * Get log entries from file
     *
     * @param string|null $level
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getEntries(string $level = null, int $limit = 100, int $offset = 0): array
    {
        if (!static::$directory || !is_dir(static::$directory)) {
            return [];
        }
        
        $files = glob(static::$directory . '*.log');
        rsort($files); // Most recent first
        
        $entries = [];
        $count = 0;
        
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse($lines); // Most recent first
            
            foreach ($lines as $line) {
                if ($count < $offset) {
                    $count++;
                    continue;
                }
                
                if (count($entries) >= $limit) {
                    break 2;
                }
                
                $entry = static::parseLogEntry($line);
                if ($entry && ($level === null || $entry['level'] === strtoupper($level))) {
                    $entries[] = $entry;
                }
                
                $count++;
            }
        }
        
        return $entries;
    }

    /**
     * Parse a log entry line
     *
     * @param string $line
     * @return array|null
     */
    protected static function parseLogEntry(string $line): ?array
    {
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+): (.+)$/', $line, $matches)) {
            $message = $matches[3];
            $context = [];
            
            // Try to extract JSON context
            if (preg_match('/^(.+?) (\{.+\})$/', $message, $contextMatches)) {
                $message = $contextMatches[1];
                $context = json_decode($contextMatches[2], true) ?: [];
            }
            
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => $message,
                'context' => $context
            ];
        }
        
        return null;
    }

    /**
     * Clear all log files
     *
     * @return bool
     */
    public static function clear(): bool
    {
        if (!static::$directory || !is_dir(static::$directory)) {
            return false;
        }
        
        $files = glob(static::$directory . '*.log*');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }

    /**
     * Get log statistics
     *
     * @return array
     */
    public static function stats(): array
    {
        if (!static::$directory || !is_dir(static::$directory)) {
            return [
                'files' => 0,
                'total_size' => 0,
                'entries' => 0
            ];
        }
        
        $files = glob(static::$directory . '*.log*');
        $totalSize = 0;
        $totalEntries = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $totalEntries += count($lines);
        }
        
        return [
            'files' => count($files),
            'total_size' => $totalSize,
            'entries' => $totalEntries,
            'directory' => static::$directory
        ];
    }
}