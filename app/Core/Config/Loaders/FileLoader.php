<?php

namespace App\Core\Config\Loaders;

use App\Core\Exceptions\ConfigException;

/**
 * File Loader
 * 
 * Handles loading configuration files from the filesystem
 */
class FileLoader
{
    /**
     * Load configuration from a single file
     *
     * @param string $path
     * @return array
     * @throws ConfigException
     */
    public function load($path)
    {
        if (!file_exists($path)) {
            throw new ConfigException("Configuration file not found: {$path}");
        }

        if (!is_readable($path)) {
            throw new ConfigException("Configuration file not readable: {$path}");
        }

        $config = require $path;

        if (!is_array($config)) {
            throw new ConfigException("Configuration file must return an array: {$path}");
        }

        return $config;
    }

    /**
     * Load all configuration files from a directory
     *
     * @param string $directory
     * @return array
     * @throws ConfigException
     */
    public function loadDirectory($directory)
    {
        if (!is_dir($directory)) {
            return [];
        }

        if (!is_readable($directory)) {
            throw new ConfigException("Configuration directory not readable: {$directory}");
        }

        $config = [];
        $files = $this->getConfigFiles($directory);

        foreach ($files as $file) {
            $key = $this->getConfigKey($file);
            $config[$key] = $this->load($file);
        }

        return $config;
    }

    /**
     * Get configuration files from directory
     *
     * @param string $directory
     * @return array
     */
    protected function getConfigFiles($directory)
    {
        $files = glob($directory . '/*.php');
        
        // Sort files to ensure consistent loading order
        sort($files);
        
        return $files;
    }

    /**
     * Get configuration key from file path
     *
     * @param string $file
     * @return string
     */
    protected function getConfigKey($file)
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    /**
     * Load configuration files recursively from directory
     *
     * @param string $directory
     * @param string $prefix
     * @return array
     * @throws ConfigException
     */
    public function loadDirectoryRecursive($directory, $prefix = '')
    {
        if (!is_dir($directory)) {
            return [];
        }

        if (!is_readable($directory)) {
            throw new ConfigException("Configuration directory not readable: {$directory}");
        }

        $config = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $relativePath = $iterator->getSubPathName();
                $key = $this->getNestedConfigKey($relativePath, $prefix);
                $config = $this->setNestedValue($config, $key, $this->load($file->getPathname()));
            }
        }

        return $config;
    }

    /**
     * Get nested configuration key from relative path
     *
     * @param string $relativePath
     * @param string $prefix
     * @return string
     */
    protected function getNestedConfigKey($relativePath, $prefix = '')
    {
        $key = str_replace(['/', '\\'], '.', pathinfo($relativePath, PATHINFO_DIRNAME));
        $filename = pathinfo($relativePath, PATHINFO_FILENAME);
        
        if ($key === '.') {
            $key = $filename;
        } else {
            $key .= '.' . $filename;
        }
        
        return $prefix ? $prefix . '.' . $key : $key;
    }

    /**
     * Set nested value in array using dot notation
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    protected function setNestedValue(array $array, $key, $value)
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
        return $array;
    }

    /**
     * Check if a file is a valid configuration file
     *
     * @param string $file
     * @return bool
     */
    public function isValidConfigFile($file)
    {
        return is_file($file) && 
               is_readable($file) && 
               pathinfo($file, PATHINFO_EXTENSION) === 'php';
    }

    /**
     * Get file modification time for cache invalidation
     *
     * @param string $file
     * @return int
     */
    public function getFileModificationTime($file)
    {
        return filemtime($file);
    }

    /**
     * Get directory modification time (latest file modification)
     *
     * @param string $directory
     * @return int
     */
    public function getDirectoryModificationTime($directory)
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $latestTime = 0;
        $files = $this->getConfigFiles($directory);

        foreach ($files as $file) {
            $time = $this->getFileModificationTime($file);
            if ($time > $latestTime) {
                $latestTime = $time;
            }
        }

        return $latestTime;
    }
}