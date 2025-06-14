<?php

namespace App\Core\Autoloader;

use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Enhanced Autoloader
 * 
 * Provides improved autoloading capabilities that complement Composer's autoloader
 * with additional features like class mapping, fallback directories, and debugging.
 */
class EnhancedAutoloader
{
    /**
     * @var array PSR-4 namespace to directory mapping
     */
    protected $psr4Prefixes = [];
    
    /**
     * @var array Class to file mapping for faster loading
     */
    protected $classMap = [];
    
    /**
     * @var array Fallback directories for classes not found in PSR-4
     */
    protected $fallbackDirs = [];
    
    /**
     * @var bool Whether to enable debug mode
     */
    protected $debug = false;

    /**
     * @var LoggerInterface|null The logger instance
     */
    protected $logger;
    
    /**
     * @var array Loading statistics for debugging
     */
    protected $stats = [
        'loaded' => 0,
        'failed' => 0,
        'cache_hits' => 0
    ];
    
    /**
     * @var array Cache of resolved file paths
     */
    protected $pathCache = [];
    
    /**
     * Constructor
     * 
     * @param bool $debug Enable debug mode
     * @param LoggerInterface|null $logger The logger instance
     */
    public function __construct(bool $debug = false, ?LoggerInterface $logger = null)
    {
        $this->debug = $debug;
        $this->logger = $logger;
    }
    
    /**
     * Register the autoloader
     * 
     * @param bool $prepend Whether to prepend to the autoloader stack
     * @return void
     */
    public function register(bool $prepend = false): void
    {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);
    }
    
    /**
     * Unregister the autoloader
     * 
     * @return void
     */
    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }
    
    /**
     * Add a PSR-4 namespace mapping
     * 
     * @param string $prefix The namespace prefix
     * @param string|array $paths Base directory/directories for the namespace
     * @param bool $prepend Whether to prepend to existing paths
     * @return void
     */
    public function addPsr4(string $prefix, $paths, bool $prepend = false): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $paths = (array) $paths;
        
        if (!isset($this->psr4Prefixes[$prefix])) {
            $this->psr4Prefixes[$prefix] = [];
        }
        
        foreach ($paths as $path) {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            
            if ($prepend) {
                array_unshift($this->psr4Prefixes[$prefix], $path);
            } else {
                $this->psr4Prefixes[$prefix][] = $path;
            }
        }
    }
    
    /**
     * Add a class to file mapping
     * 
     * @param string $class The fully qualified class name
     * @param string $file The file path
     * @return void
     */
    public function addClassMap(string $class, string $file): void
    {
        $this->classMap[$class] = $file;
    }
    
    /**
     * Add multiple class mappings
     * 
     * @param array $classMap Array of class => file mappings
     * @return void
     */
    public function addClassMaps(array $classMap): void
    {
        $this->classMap = array_merge($this->classMap, $classMap);
    }
    
    /**
     * Add a fallback directory
     * 
     * @param string $dir The directory path
     * @return void
     */
    public function addFallbackDir(string $dir): void
    {
        $this->fallbackDirs[] = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
    
    /**
     * Load a class
     * 
     * @param string $class The fully qualified class name
     * @return bool True if the class was loaded, false otherwise
     */
    public function loadClass(string $class): bool
    {
        // Check class map first for fastest loading
        if (isset($this->classMap[$class])) {
            $file = $this->classMap[$class];
            if ($this->requireFile($file)) {
                $this->stats['loaded']++;
                $this->stats['cache_hits']++;
                $this->debug && $this->log("Loaded '{$class}' from class map: {$file}");
                return true;
            }
        }
        
        // Check path cache
        if (isset($this->pathCache[$class])) {
            $file = $this->pathCache[$class];
            if ($this->requireFile($file)) {
                $this->stats['loaded']++;
                $this->stats['cache_hits']++;
                $this->debug && $this->log("Loaded '{$class}' from cache: {$file}");
                return true;
            }
        }
        
        // Try PSR-4 loading
        $file = $this->findFileWithPsr4($class);
        if ($file && $this->requireFile($file)) {
            $this->pathCache[$class] = $file;
            $this->stats['loaded']++;
            $this->debug && $this->log("Loaded '{$class}' via PSR-4: {$file}");
            return true;
        }
        
        // Try fallback directories
        $file = $this->findFileInFallbackDirs($class);
        if ($file && $this->requireFile($file)) {
            $this->pathCache[$class] = $file;
            $this->stats['loaded']++;
            $this->debug && $this->log("Loaded '{$class}' from fallback: {$file}");
            return true;
        }
        
        $this->stats['failed']++;
        $this->debug && $this->log("Failed to load class: {$class}");
        
        return false;
    }
    
    /**
     * Find file using PSR-4 rules
     * 
     * @param string $class The fully qualified class name
     * @return string|false The file path or false if not found
     */
    protected function findFileWithPsr4(string $class)
    {
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';
        
        foreach ($this->psr4Prefixes as $prefix => $dirs) {
            if (strpos($class, $prefix) === 0) {
                $subPath = substr($logicalPathPsr4, strlen($prefix));
                
                foreach ($dirs as $dir) {
                    $file = $dir . $subPath;
                    if (file_exists($file)) {
                        return $file;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Find file in fallback directories
     * 
     * @param string $class The fully qualified class name
     * @return string|false The file path or false if not found
     */
    protected function findFileInFallbackDirs(string $class)
    {
        $logicalPath = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';
        
        foreach ($this->fallbackDirs as $dir) {
            $file = $dir . $logicalPath;
            if (file_exists($file)) {
                return $file;
            }
        }
        
        return false;
    }
    
    /**
     * Require a file if it exists
     * 
     * @param string $file The file path
     * @return bool True if the file was successfully required
     */
    protected function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        
        return false;
    }
    
    /**
     * Get loading statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        return $this->stats;
    }
    
    /**
     * Clear the path cache
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->pathCache = [];
    }
    
    /**
     * Get all registered PSR-4 prefixes
     * 
     * @return array
     */
    public function getPsr4Prefixes(): array
    {
        return $this->psr4Prefixes;
    }
    
    /**
     * Get the class map
     * 
     * @return array
     */
    public function getClassMap(): array
    {
        return $this->classMap;
    }
    
    /**
     * Get fallback directories
     * 
     * @return array
     */
    public function getFallbackDirs(): array
    {
        return $this->fallbackDirs;
    }
    
    /**
     * Enable or disable debug mode
     * 
     * @param bool $debug
     * @return void
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }
    
    /**
     * Log a debug message
     * 
     * @param string $message
     * @return void
     */
    protected function log(string $message): void
    {
        if ($this->debug) {
            if ($this->logger) {
                $this->logger->debug('[EnhancedAutoloader] ' . $message);
            } else {
                // Fallback to error_log if no logger is set
                \App\Support\Log::error('[EnhancedAutoloader] ' . $message);
            }
        }
    }
    
    /**
     * Create autoloader from Composer configuration
     * 
     * @param string $composerFile Path to composer.json
     * @return static
     * @throws RuntimeException
     */
    public static function fromComposer(string $composerFile): self
    {
        if (!file_exists($composerFile)) {
            throw new RuntimeException("Composer file not found: {$composerFile}");
        }
        
        $composer = json_decode(file_get_contents($composerFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid composer.json file');
        }
        
        $autoloader = new static();
        
        // Add PSR-4 mappings
        if (isset($composer['autoload']['psr-4'])) {
            $basePath = dirname($composerFile) . DIRECTORY_SEPARATOR;
            foreach ($composer['autoload']['psr-4'] as $prefix => $paths) {
                $paths = (array) $paths;
                $absolutePaths = array_map(function($path) use ($basePath) {
                    return $basePath . ltrim($path, DIRECTORY_SEPARATOR);
                }, $paths);
                $autoloader->addPsr4($prefix, $absolutePaths);
            }
        }
        
        // Add class maps
        if (isset($composer['autoload']['classmap'])) {
            // This would require scanning directories, simplified for now
        }
        
        return $autoloader;
    }
}