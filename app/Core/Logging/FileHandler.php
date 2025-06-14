<?php

namespace App\Core\Logging;

use Psr\Log\LogLevel;

/**
 * File Handler
 * 
 * Handles logging to files with rotation and formatting support
 */
class FileHandler implements HandlerInterface
{
    /**
     * @var string Log file path
     */
    protected $file;
    
    /**
     * @var string Minimum log level
     */
    protected $level;
    
    /**
     * @var FormatterInterface Log formatter
     */
    protected $formatter;
    
    /**
     * @var resource File handle
     */
    protected $handle;
    
    /**
     * @var int Maximum file size in bytes
     */
    protected $maxFileSize;
    
    /**
     * @var int Maximum number of files to keep
     */
    protected $maxFiles;
    
    /**
     * @var int File permissions
     */
    protected $filePermissions;
    
    /**
     * @var bool Whether to use file locking
     */
    protected $useLocking;
    
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
     * Create a new file handler
     * 
     * @param string $file
     * @param string $level
     * @param int $maxFileSize
     * @param int $maxFiles
     * @param int $filePermissions
     * @param bool $useLocking
     */
    public function __construct(
        $file = null,
        $level = LogLevel::DEBUG,
        $maxFileSize = 10485760, // 10MB
        $maxFiles = 5,
        $filePermissions = 0644,
        $useLocking = true
    ) {
        $this->file = $file ?: $this->getDefaultLogFile();
        $this->level = $level;
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;
        $this->filePermissions = $filePermissions;
        $this->useLocking = $useLocking;
        $this->formatter = new LineFormatter();
    }
    
    /**
     * Handle a log record
     * 
     * @param array $record
     * @return bool
     */
    public function handle(array $record)
    {
        if (!$this->isHandling($record)) {
            return false;
        }
        
        // Check if file rotation is needed
        $this->rotateFiles();
        
        // Format the record
        $formatted = $this->formatter->format($record);
        
        // Write to file
        $this->write($formatted);
        
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
        $recordLevel = $record['level'] ?? LogLevel::DEBUG;
        
        return isset(self::$levels[$recordLevel]) &&
               isset(self::$levels[$this->level]) &&
               self::$levels[$recordLevel] >= self::$levels[$this->level];
    }
    
    /**
     * Close the handler
     * 
     * @return void
     */
    public function close()
    {
        if ($this->handle && is_resource($this->handle)) {
            fclose($this->handle);
            $this->handle = null;
        }
    }
    
    /**
     * Set the formatter
     * 
     * @param FormatterInterface $formatter
     * @return $this
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
        return $this;
    }
    
    /**
     * Get the formatter
     * 
     * @return FormatterInterface
     */
    public function getFormatter()
    {
        return $this->formatter;
    }
    
    /**
     * Set the log file path
     * 
     * @param string $file
     * @return $this
     */
    public function setFile($file)
    {
        if ($this->file !== $file) {
            $this->close();
            $this->file = $file;
        }
        return $this;
    }
    
    /**
     * Get the log file path
     * 
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }
    
    /**
     * Set the minimum log level
     * 
     * @param string $level
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = $level;
        return $this;
    }
    
    /**
     * Get the minimum log level
     * 
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }
    
    /**
     * Set maximum file size
     * 
     * @param int $size
     * @return $this
     */
    public function setMaxFileSize($size)
    {
        $this->maxFileSize = $size;
        return $this;
    }
    
    /**
     * Set maximum number of files
     * 
     * @param int $files
     * @return $this
     */
    public function setMaxFiles($files)
    {
        $this->maxFiles = $files;
        return $this;
    }
    
    /**
     * Write formatted log entry to file
     * 
     * @param string $formatted
     * @return void
     */
    protected function write($formatted)
    {
        if (!$this->handle) {
            $this->openFile();
        }
        
        if ($this->handle && is_resource($this->handle)) {
            if ($this->useLocking) {
                flock($this->handle, LOCK_EX);
            }
            
            fwrite($this->handle, $formatted);
            
            if ($this->useLocking) {
                flock($this->handle, LOCK_UN);
            }
        }
    }
    
    /**
     * Open the log file
     * 
     * @return void
     */
    protected function openFile()
    {
        // Ensure directory exists
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $this->handle = fopen($this->file, 'a');
        
        if ($this->handle && $this->filePermissions) {
            chmod($this->file, $this->filePermissions);
        }
    }
    
    /**
     * Rotate log files if needed
     * 
     * @return void
     */
    protected function rotateFiles()
    {
        if (!file_exists($this->file)) {
            return;
        }
        
        if (filesize($this->file) < $this->maxFileSize) {
            return;
        }
        
        $this->close();
        
        // Rotate existing files
        for ($i = $this->maxFiles - 1; $i > 0; $i--) {
            $oldFile = $this->file . '.' . $i;
            $newFile = $this->file . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i === $this->maxFiles - 1) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Move current file to .1
        if (file_exists($this->file)) {
            rename($this->file, $this->file . '.1');
        }
    }
    
    /**
     * Get default log file path
     * 
     * @return string
     */
    protected function getDefaultLogFile()
    {
        $logDir = sys_get_temp_dir() . '/logs';
        return $logDir . '/app.log';
    }
    
    /**
     * Get file size in bytes
     * 
     * @return int
     */
    public function getFileSize()
    {
        return file_exists($this->file) ? filesize($this->file) : 0;
    }
    
    /**
     * Check if file rotation is needed
     * 
     * @return bool
     */
    public function needsRotation()
    {
        return $this->getFileSize() >= $this->maxFileSize;
    }
    
    /**
     * Get all rotated log files
     * 
     * @return array
     */
    public function getRotatedFiles()
    {
        $files = [$this->file];
        
        for ($i = 1; $i <= $this->maxFiles; $i++) {
            $rotatedFile = $this->file . '.' . $i;
            if (file_exists($rotatedFile)) {
                $files[] = $rotatedFile;
            }
        }
        
        return $files;
    }
    
    /**
     * Clear all log files
     * 
     * @return $this
     */
    public function clear()
    {
        $this->close();
        
        $files = $this->getRotatedFiles();
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        return $this;
    }
    
    /**
     * Get total size of all log files
     * 
     * @return int
     */
    public function getTotalSize()
    {
        $totalSize = 0;
        $files = $this->getRotatedFiles();
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $totalSize += filesize($file);
            }
        }
        
        return $totalSize;
    }
    
    /**
     * Destructor - close file handle
     */
    public function __destruct()
    {
        $this->close();
    }
}