<?php
namespace App\Libraries\Core;

/**
 * Simple class autoloader implementation
 */
class ClassLoader
{
    /**
     * @var array Namespace to directory mapping
     */
    protected $prefixes = [];

    /**
     * Register the autoloader
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Add a base directory for a namespace prefix
     *
     * @param string $prefix The namespace prefix
     * @param string $baseDir Base directory for the namespace
     * @param bool $prepend If true, prepend to the stack
     */
    public function addNamespace($prefix, $baseDir, $prepend = false)
    {
        // Normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';
        
        // Normalize the base directory with a trailing separator
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
        
        // Initialize the namespace prefix array if needed
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = [];
        }
        
        // Retain the base directory for the namespace prefix
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            array_push($this->prefixes[$prefix], $baseDir);
        }
    }

    /**
     * Load the class file for a given class name
     * 
     * @param string $class The fully-qualified class name
     * @return string|bool The mapped file name on success, or boolean false on failure
     */
    public function loadClass($class)
    {
        // The current namespace prefix
        $prefix = $class;
        
        // Work backwards through the namespace names to find a matching file
        while (false !== $pos = strrpos($prefix, '\\')) {
            // Retain the trailing namespace separator in the prefix
            $prefix = substr($class, 0, $pos + 1);
            
            // The rest is the relative class name
            $relativeClass = substr($class, $pos + 1);
            
            // Try to load a mapped file for the prefix and relative class
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return $mappedFile;
            }
            
            // Remove the trailing namespace separator for the next iteration
            $prefix = rtrim($prefix, '\\');
        }
        
        // Never found a mapped file
        return false;
    }
    
    /**
     * Load the mapped file for a namespace prefix and relative class
     * 
     * @param string $prefix The namespace prefix
     * @param string $relativeClass The relative class name
     * @return mixed Boolean false if no mapped file can be loaded, or the
     *               name of the mapped file that was loaded
     */
    protected function loadMappedFile($prefix, $relativeClass)
    {
        // Are there any base directories for this namespace prefix?
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }
        
        // Look through base directories for this namespace prefix
        foreach ($this->prefixes[$prefix] as $baseDir) {
            // Replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $baseDir
                  . str_replace('\\', '/', $relativeClass)
                  . '.php';
            
            // If the mapped file exists, require it
            if ($this->requireFile($file)) {
                // Yes, we're done
                return $file;
            }
        }
        
        // Never found it
        return false;
    }
    
    /**
     * If a file exists, require it from the file system
     * 
     * @param string $file The file to require
     * @return bool True if the file exists, false if not
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
}
