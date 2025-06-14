<?php

namespace App\Core\Config\Loaders;

use App\Core\Exceptions\ConfigException;

/**
 * Environment Loader
 * 
 * Handles loading and parsing environment variables from .env files
 */
class EnvironmentLoader
{
    /**
     * Environment variables cache
     *
     * @var array
     */
    protected $variables = [];

    /**
     * Load environment variables from a file
     *
     * @param string $path
     * @return void
     * @throws ConfigException
     */
    public function load($path)
    {
        if (!file_exists($path)) {
            return; // Silently skip missing files
        }

        if (!is_readable($path)) {
            throw new ConfigException("Environment file not readable: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new ConfigException("Failed to read environment file: {$path}");
        }

        foreach ($lines as $lineNumber => $line) {
            $this->parseLine($line, $lineNumber + 1, $path);
        }
    }

    /**
     * Parse a single line from the environment file
     *
     * @param string $line
     * @param int $lineNumber
     * @param string $file
     * @return void
     * @throws ConfigException
     */
    protected function parseLine($line, $lineNumber, $file)
    {
        $line = trim($line);

        // Skip empty lines and comments
        if (empty($line) || strpos($line, '#') === 0) {
            return;
        }

        // Check for variable assignment
        if (strpos($line, '=') === false) {
            throw new ConfigException(
                "Invalid environment variable format at line {$lineNumber} in {$file}: {$line}"
            );
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Validate variable name
        if (!$this->isValidVariableName($key)) {
            throw new ConfigException(
                "Invalid environment variable name at line {$lineNumber} in {$file}: {$key}"
            );
        }

        // Parse the value
        $parsedValue = $this->parseValue($value);

        // Set the environment variable
        $this->setEnvironmentVariable($key, $parsedValue);
    }

    /**
     * Parse environment variable value
     *
     * @param string $value
     * @return mixed
     */
    protected function parseValue($value)
    {
        // Handle quoted values
        if ($this->isQuoted($value)) {
            return $this->parseQuotedValue($value);
        }

        // Handle unquoted values
        return $this->parseUnquotedValue($value);
    }

    /**
     * Check if value is quoted
     *
     * @param string $value
     * @return bool
     */
    protected function isQuoted($value)
    {
        $length = strlen($value);
        
        if ($length < 2) {
            return false;
        }

        $firstChar = $value[0];
        $lastChar = $value[$length - 1];

        return ($firstChar === '"' && $lastChar === '"') ||
               ($firstChar === "'" && $lastChar === "'");
    }

    /**
     * Parse quoted value
     *
     * @param string $value
     * @return string
     */
    protected function parseQuotedValue($value)
    {
        $quote = $value[0];
        $content = substr($value, 1, -1);

        if ($quote === '"') {
            // Handle escape sequences in double quotes
            return $this->parseEscapeSequences($content);
        }

        // Single quotes preserve literal values
        return $content;
    }

    /**
     * Parse unquoted value
     *
     * @param string $value
     * @return mixed
     */
    protected function parseUnquotedValue($value)
    {
        // Handle special values
        $lowerValue = strtolower($value);

        if ($lowerValue === 'true') {
            return true;
        }

        if ($lowerValue === 'false') {
            return false;
        }

        if ($lowerValue === 'null') {
            return null;
        }

        if ($lowerValue === 'empty') {
            return '';
        }

        // Handle numeric values
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        // Handle variable expansion
        return $this->expandVariables($value);
    }

    /**
     * Parse escape sequences in double-quoted strings
     *
     * @param string $value
     * @return string
     */
    protected function parseEscapeSequences($value)
    {
        $replacements = [
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\\\' => "\\",
            '\\"' => '"',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $value);
    }

    /**
     * Expand variables in value (${VAR} or $VAR)
     *
     * @param string $value
     * @return string
     */
    protected function expandVariables($value)
    {
        // Handle ${VAR} syntax
        $value = preg_replace_callback('/\$\{([A-Z_][A-Z0-9_]*)\}/', function ($matches) {
            return $this->get($matches[1], '');
        }, $value);

        // Handle $VAR syntax
        $value = preg_replace_callback('/\$([A-Z_][A-Z0-9_]*)/', function ($matches) {
            return $this->get($matches[1], '');
        }, $value);

        return $value;
    }

    /**
     * Set environment variable
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setEnvironmentVariable($key, $value)
    {
        $stringValue = is_string($value) ? $value : json_encode($value);
        
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$stringValue}");
        
        $this->variables[$key] = $value;
    }

    /**
     * Get environment variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        // Check cache first
        if (isset($this->variables[$key])) {
            return $this->variables[$key];
        }

        // Check various sources
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        // Convert string representations to proper types
        $convertedValue = $this->convertValue($value);
        $this->variables[$key] = $convertedValue;

        return $convertedValue;
    }

    /**
     * Convert string value to appropriate type
     *
     * @param string $value
     * @return mixed
     */
    protected function convertValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $lowerValue = strtolower($value);

        if ($lowerValue === 'true') {
            return true;
        }

        if ($lowerValue === 'false') {
            return false;
        }

        if ($lowerValue === 'null') {
            return null;
        }

        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        return $value;
    }

    /**
     * Check if variable name is valid
     *
     * @param string $name
     * @return bool
     */
    protected function isValidVariableName($name)
    {
        return preg_match('/^[A-Z_][A-Z0-9_]*$/', $name) === 1;
    }

    /**
     * Get all loaded environment variables
     *
     * @return array
     */
    public function all()
    {
        return $this->variables;
    }

    /**
     * Check if environment variable exists
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->variables[$key]) ||
               isset($_ENV[$key]) ||
               isset($_SERVER[$key]) ||
               getenv($key) !== false;
    }

    /**
     * Clear all loaded environment variables
     *
     * @return void
     */
    public function clear()
    {
        $this->variables = [];
    }

    /**
     * Load multiple environment files
     *
     * @param array $paths
     * @return void
     */
    public function loadMultiple(array $paths)
    {
        foreach ($paths as $path) {
            $this->load($path);
        }
    }
}