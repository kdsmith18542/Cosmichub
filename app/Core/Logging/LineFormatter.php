<?php

namespace App\Core\Logging;

/**
 * Line Formatter
 * 
 * Formats log records as single lines with customizable format
 */
class LineFormatter implements FormatterInterface
{
    /**
     * @var string Log format template
     */
    protected $format;
    
    /**
     * @var string Date format
     */
    protected $dateFormat;
    
    /**
     * @var bool Whether to include stack traces
     */
    protected $includeStacktraces;
    
    /**
     * @var bool Whether to ignore empty context
     */
    protected $ignoreEmptyContextAndExtra;
    
    /**
     * @var int Maximum length for context/extra data
     */
    protected $maxNormalizeDepth;
    
    /**
     * @var int Maximum items in normalized arrays
     */
    protected $maxNormalizeItemCount;
    
    /**
     * Default log format
     */
    const SIMPLE_FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
    
    /**
     * Create a new line formatter
     * 
     * @param string|null $format
     * @param string|null $dateFormat
     * @param bool $includeStacktraces
     * @param bool $ignoreEmptyContextAndExtra
     */
    public function __construct(
        $format = null,
        $dateFormat = null,
        $includeStacktraces = false,
        $ignoreEmptyContextAndExtra = false
    ) {
        $this->format = $format ?: static::SIMPLE_FORMAT;
        $this->dateFormat = $dateFormat ?: 'Y-m-d H:i:s';
        $this->includeStacktraces = $includeStacktraces;
        $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
        $this->maxNormalizeDepth = 9;
        $this->maxNormalizeItemCount = 1000;
    }
    
    /**
     * Format a log record
     * 
     * @param array $record
     * @return string
     */
    public function format(array $record)
    {
        $vars = $this->normalize($record);
        
        $output = $this->format;
        
        // Replace placeholders
        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%' . $var . '%')) {
                $output = str_replace('%' . $var . '%', $this->stringify($val), $output);
            }
        }
        
        // Handle special formatting
        if (isset($vars['datetime']) && $vars['datetime'] instanceof \DateTime) {
            $output = str_replace('%datetime%', $vars['datetime']->format($this->dateFormat), $output);
        }
        
        // Format context and extra data
        if (isset($vars['context']) && !empty($vars['context'])) {
            if ($this->ignoreEmptyContextAndExtra) {
                $output = str_replace('%context%', $this->toJson($vars['context']), $output);
            } else {
                $output = str_replace('%context%', $this->toJson($vars['context']), $output);
            }
        } else {
            $output = str_replace('%context%', '', $output);
        }
        
        if (isset($vars['extra']) && !empty($vars['extra'])) {
            if ($this->ignoreEmptyContextAndExtra) {
                $output = str_replace('%extra%', $this->toJson($vars['extra']), $output);
            } else {
                $output = str_replace('%extra%', $this->toJson($vars['extra']), $output);
            }
        } else {
            $output = str_replace('%extra%', '', $output);
        }
        
        // Clean up extra spaces
        $output = preg_replace('/\s+/', ' ', $output);
        $output = trim($output) . "\n";
        
        return $output;
    }
    
    /**
     * Format multiple log records
     * 
     * @param array $records
     * @return string
     */
    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }
        return $message;
    }
    
    /**
     * Set the log format
     * 
     * @param string $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }
    
    /**
     * Get the log format
     * 
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }
    
    /**
     * Set the date format
     * 
     * @param string $dateFormat
     * @return $this
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }
    
    /**
     * Get the date format
     * 
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }
    
    /**
     * Enable or disable stack traces
     * 
     * @param bool $include
     * @return $this
     */
    public function includeStacktraces($include = true)
    {
        $this->includeStacktraces = $include;
        return $this;
    }
    
    /**
     * Enable or disable ignoring empty context and extra
     * 
     * @param bool $ignore
     * @return $this
     */
    public function ignoreEmptyContextAndExtra($ignore = true)
    {
        $this->ignoreEmptyContextAndExtra = $ignore;
        return $this;
    }
    
    /**
     * Normalize a log record
     * 
     * @param array $record
     * @return array
     */
    protected function normalize($record)
    {
        return $this->normalizeValue($record, 0);
    }
    
    /**
     * Normalize a value
     * 
     * @param mixed $data
     * @param int $depth
     * @return mixed
     */
    protected function normalizeValue($data, $depth = 0)
    {
        if ($depth > $this->maxNormalizeDepth) {
            return 'Over ' . $this->maxNormalizeDepth . ' levels deep, aborting normalization';
        }
        
        if (null === $data || is_bool($data)) {
            return $data;
        }
        
        if (is_numeric($data)) {
            return $data;
        }
        
        if (is_string($data)) {
            return $data;
        }
        
        if (is_array($data)) {
            $normalized = [];
            $count = 1;
            foreach ($data as $key => $value) {
                if ($count++ > $this->maxNormalizeItemCount) {
                    $normalized['...'] = 'Over ' . $this->maxNormalizeItemCount . ' items (' . count($data) . ' total), aborting normalization';
                    break;
                }
                $normalized[$key] = $this->normalizeValue($value, $depth + 1);
            }
            return $normalized;
        }
        
        if (is_object($data)) {
            if ($data instanceof \DateTime) {
                return $data;
            }
            
            if ($data instanceof \Throwable) {
                return $this->normalizeException($data);
            }
            
            if (method_exists($data, '__toString')) {
                return (string) $data;
            }
            
            return sprintf('[object] (%s)', get_class($data));
        }
        
        if (is_resource($data)) {
            return sprintf('[resource] (%s)', get_resource_type($data));
        }
        
        return '[unknown(' . gettype($data) . ')]';
    }
    
    /**
     * Normalize an exception
     * 
     * @param \Throwable $e
     * @return array
     */
    protected function normalizeException(\Throwable $e)
    {
        $data = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile() . ':' . $e->getLine(),
        ];
        
        if ($this->includeStacktraces) {
            $data['trace'] = $e->getTraceAsString();
        }
        
        if ($previous = $e->getPrevious()) {
            $data['previous'] = $this->normalizeException($previous);
        }
        
        return $data;
    }
    
    /**
     * Convert value to string
     * 
     * @param mixed $value
     * @return string
     */
    protected function stringify($value)
    {
        if (null === $value) {
            return 'null';
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_scalar($value)) {
            return (string) $value;
        }
        
        return $this->toJson($value);
    }
    
    /**
     * Convert value to JSON
     * 
     * @param mixed $data
     * @return string
     */
    protected function toJson($data)
    {
        if (empty($data)) {
            return '';
        }
        
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            return 'JSON encoding failed: ' . json_last_error_msg();
        }
        
        return $json;
    }
    
    /**
     * Create a simple formatter
     * 
     * @return static
     */
    public static function simple()
    {
        return new static();
    }
    
    /**
     * Create a detailed formatter
     * 
     * @return static
     */
    public static function detailed()
    {
        $format = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        return new static($format, 'Y-m-d H:i:s.u', true, false);
    }
    
    /**
     * Create a minimal formatter
     * 
     * @return static
     */
    public static function minimal()
    {
        $format = "%level_name%: %message%\n";
        return new static($format, null, false, true);
    }
}