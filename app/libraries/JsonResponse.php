<?php
/**
 * JSON Response Class
 * 
 * Handles JSON responses with proper content type and encoding.
 */

class JsonResponse extends Response {
    /**
     * @var int JSON encoding options
     */
    protected $encodingOptions;
    
    /**
     * @var bool Whether the JSON should be pretty printed
     */
    protected $prettyPrint = false;
    
    /**
     * Create a new JSON response
     * 
     * @param mixed $data The response data
     * @param int $status The HTTP status code
     * @param array $headers Additional HTTP headers
     * @param int $options JSON encoding options
     * @param bool $prettyPrint Whether to pretty print the JSON
     */
    public function __construct($data = null, $status = 200, array $headers = [], $options = 0, $prettyPrint = false) {
        parent::__construct('', $status, $headers);
        
        $this->setEncodingOptions($options);
        $this->setPrettyPrint($prettyPrint);
        
        if (null !== $data) {
            $this->setData($data);
        }
    }
    
    /**
     * Factory method for creating a new JSON response
     * 
     * @param mixed $data The response data
     * @param int $status The HTTP status code
     * @param array $headers Additional HTTP headers
     * @param int $options JSON encoding options
     * @param bool $prettyPrint Whether to pretty print the JSON
     * @return static
     */
    public static function create($data = null, $status = 200, array $headers = [], $options = 0, $prettyPrint = false) {
        return new static($data, $status, $headers, $options, $prettyPrint);
    }
    
    /**
     * Set the response data
     * 
     * @param mixed $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setData($data = []) {
        try {
            $json = $this->jsonEncode($data);
            
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }
            
            $this->setContent($json);
            
            // Set the content type header if not already set
            if (!$this->hasHeader('Content-Type')) {
                $this->setContentType('application/json');
            }
            
            return $this;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Unable to encode data to JSON: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Get the response data
     * 
     * @return mixed
     */
    public function getData() {
        return json_decode($this->getContent(), true);
    }
    
    /**
     * Set the JSON encoding options
     * 
     * @param int $encodingOptions
     * @return $this
     */
    public function setEncodingOptions($encodingOptions) {
        $this->encodingOptions = (int) $encodingOptions;
        
        // Ensure we have the JSON_UNESCAPED_UNICODE option set by default
        $this->encodingOptions |= JSON_UNESCAPED_UNICODE;
        
        // Apply pretty print if enabled
        if ($this->prettyPrint) {
            $this->encodingOptions |= JSON_PRETTY_PRINT;
        } else {
            $this->encodingOptions &= ~JSON_PRETTY_PRINT;
        }
        
        // Re-encode the data if it exists
        if (null !== $this->content) {
            $data = json_decode($this->content, true);
            if (JSON_ERROR_NONE === json_last_error()) {
                $this->setData($data);
            }
        }
        
        return $this;
    }
    
    /**
     * Get the JSON encoding options
     * 
     * @return int
     */
    public function getEncodingOptions() {
        return $this->encodingOptions;
    }
    
    /**
     * Set whether to pretty print the JSON
     * 
     * @param bool $prettyPrint
     * @return $this
     */
    public function setPrettyPrint($prettyPrint) {
        $this->prettyPrint = (bool) $prettyPrint;
        
        // Update the encoding options
        if ($this->prettyPrint) {
            $this->encodingOptions |= JSON_PRETTY_PRINT;
        } else {
            $this->encodingOptions &= ~JSON_PRETTY_PRINT;
        }
        
        // Re-encode the data if it exists
        if (null !== $this->content) {
            $data = json_decode($this->content, true);
            if (JSON_ERROR_NONE === json_last_error()) {
                $this->setData($data);
            }
        }
        
        return $this;
    }
    
    /**
     * Check if pretty printing is enabled
     * 
     * @return bool
     */
    public function isPrettyPrint() {
        return $this->prettyPrint;
    }
    
    /**
     * Set a callback to be used to encode the JSON
     * 
     * @param callable $callback
     * @return $this
     */
    public function setJsonEncodeCallback(callable $callback) {
        $this->jsonEncodeCallback = $callback;
        return $this;
    }
    
    /**
     * Encode the data as JSON
     * 
     * @param mixed $data
     * @return string
     */
    protected function jsonEncode($data) {
        if (is_resource($data)) {
            throw new \InvalidArgumentException('Resources cannot be encoded as JSON.');
        }
        
        // Handle JSON encoding with error checking
        $json = json_encode($data, $this->encodingOptions);
        
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }
        
        return $json;
    }
    
    /**
     * Create a JSON response for a success case
     * 
     * @param mixed $data The response data
     * @param string $message A success message
     * @param int $status The HTTP status code
     * @param array $headers Additional HTTP headers
     * @param int $options JSON encoding options
     * @return static
     */
    public static function success($data = null, $message = 'Success', $status = 200, array $headers = [], $options = 0) {
        $responseData = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status' => $status
        ];
        
        return new static($responseData, $status, $headers, $options);
    }
    
    /**
     * Create a JSON response for an error case
     * 
     * @param string $message An error message
     * @param int $status The HTTP status code
     * @param mixed $errors Additional error details
     * @param array $headers Additional HTTP headers
     * @param int $options JSON encoding options
     * @return static
     */
    public static function error($message = 'Error', $status = 400, $errors = null, array $headers = [], $options = 0) {
        $responseData = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'status' => $status
        ];
        
        return new static($responseData, $status, $headers, $options);
    }
    
    /**
     * Create a JSON response for a validation error
     * 
     * @param array $errors Validation errors
     * @param string $message An error message
     * @param int $status The HTTP status code
     * @param array $headers Additional HTTP headers
     * @param int $options JSON encoding options
     * @return static
     */
    public static function validationError($errors, $message = 'Validation failed', $status = 422, array $headers = [], $options = 0) {
        return static::error($message, $status, $errors, $headers, $options);
    }
    
    /**
     * Create a JSON response for a not found error
     * 
     * @param string $message An error message
     * @param array $headers Additional HTTP headers
     * @param int $options JSON encoding options
     * @return static
     */
    public static function notFound($message = 'Resource not found', array $headers = [], $options = 0) {
        return static::error($message, 404, null, $headers, $options);
    }
    
    /**
     * Create a JSON response for an unauthorized error
     * 
     * @param string $message An error message
     * @param array $headers Additional HTTP headers
     * @param int $options JSON encoding options
     * @return static
     */
    public static function unauthorized($message = 'Unauthorized', array $headers = [], $options = 0) {
        return static::error($message, 401, null, $headers, $options);
    }
    
    /**
     * Create a JSON response for a forbidden error
     * 
     * @param string $message An error message
     * @param array $headers Additional HTTP headers
     * @param int $options JSON encoding options
     * @return static
     */
    public static function forbidden($message = 'Forbidden', array $headers = [], $options = 0) {
        return static::error($message, 403, null, $headers, $options);
    }
    
    /**
     * Create a JSON response for a server error
     * 
     * @param string $message An error message
     * @param mixed $errors Additional error details
     * @param array $headers Additional HTTP headers
     * @param int $options JSON encoding options
     * @return static
     */
    public static function serverError($message = 'Internal Server Error', $errors = null, array $headers = [], $options = 0) {
        return static::error($message, 500, $errors, $headers, $options);
    }
}
