<?php

namespace App\Core\Exceptions;

use Exception;

/**
 * Configuration Exception
 * 
 * Thrown when configuration-related errors occur
 */
class ConfigException extends Exception
{
    /**
     * The configuration key that caused the error
     *
     * @var string|null
     */
    protected $configKey;

    /**
     * The configuration file that caused the error
     *
     * @var string|null
     */
    protected $configFile;

    /**
     * Create a new configuration exception
     *
     * @param string $message
     * @param string|null $configKey
     * @param string|null $configFile
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(
        $message = '',
        $configKey = null,
        $configFile = null,
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->configKey = $configKey;
        $this->configFile = $configFile;
    }

    /**
     * Get the configuration key that caused the error
     *
     * @return string|null
     */
    public function getConfigKey()
    {
        return $this->configKey;
    }

    /**
     * Get the configuration file that caused the error
     *
     * @return string|null
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Create an exception for a missing configuration key
     *
     * @param string $key
     * @param string|null $file
     * @return static
     */
    public static function missingKey($key, $file = null)
    {
        $message = "Configuration key '{$key}' is missing";
        if ($file) {
            $message .= " in file '{$file}'";
        }
        
        return new static($message, $key, $file);
    }

    /**
     * Create an exception for an invalid configuration value
     *
     * @param string $key
     * @param mixed $value
     * @param string $expected
     * @param string|null $file
     * @return static
     */
    public static function invalidValue($key, $value, $expected, $file = null)
    {
        $valueType = is_object($value) ? get_class($value) : gettype($value);
        $message = "Configuration key '{$key}' has invalid value of type '{$valueType}', expected '{$expected}'";
        
        if ($file) {
            $message .= " in file '{$file}'";
        }
        
        return new static($message, $key, $file);
    }

    /**
     * Create an exception for a missing configuration file
     *
     * @param string $file
     * @return static
     */
    public static function missingFile($file)
    {
        $message = "Configuration file '{$file}' not found";
        return new static($message, null, $file);
    }

    /**
     * Create an exception for an invalid configuration file
     *
     * @param string $file
     * @param string $reason
     * @return static
     */
    public static function invalidFile($file, $reason = 'must return an array')
    {
        $message = "Configuration file '{$file}' is invalid: {$reason}";
        return new static($message, null, $file);
    }

    /**
     * Create an exception for environment file errors
     *
     * @param string $file
     * @param string $reason
     * @return static
     */
    public static function environmentError($file, $reason)
    {
        $message = "Environment file '{$file}' error: {$reason}";
        return new static($message, null, $file);
    }

    /**
     * Create an exception for validation errors
     *
     * @param string $key
     * @param string $rule
     * @param mixed $value
     * @return static
     */
    public static function validationError($key, $rule, $value = null)
    {
        $message = "Configuration validation failed for key '{$key}': {$rule}";
        if ($value !== null) {
            $message .= " (value: " . json_encode($value) . ")";
        }
        
        return new static($message, $key);
    }
}