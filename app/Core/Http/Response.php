<?php

namespace App\Core\Http;

/**
 * HTTP Response class
 */
class Response
{
    /**
     * @var int The response status code
     */
    protected $statusCode = 200;
    
    /**
     * @var array The response headers
     */
    protected $headers = [];
    
    /**
     * @var string The response content
     */
    protected $content = '';
    
    /**
     * @var array HTTP status codes and messages
     */
    protected static $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];
    
    /**
     * Create a new response instance
     * 
     * @param string $content The response content
     * @param int $status The response status code
     * @param array $headers The response headers
     */
    public function __construct($content = '', $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
    }
    
    /**
     * Create a new response instance
     * 
     * @param string $content The response content
     * @param int $status The response status code
     * @param array $headers The response headers
     * @return Response
     */
    public static function create($content = '', $status = 200, array $headers = [])
    {
        return new static($content, $status, $headers);
    }
    
    /**
     * Create a JSON response
     * 
     * @param mixed $data The data to encode as JSON
     * @param int $status The response status code
     * @param array $headers The response headers
     * @param int $options JSON encoding options
     * @return Response
     */
    public static function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        $content = json_encode($data, $options);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }
        
        $headers['Content-Type'] = 'application/json;charset=UTF-8';
        
        return new static($content, $status, $headers);
    }
    
    /**
     * Create a redirect response
     * 
     * @param string $url The URL to redirect to
     * @param int $status The response status code
     * @param array $headers The response headers
     * @return Response
     */
    public static function redirect($url, $status = 302, array $headers = [])
    {
        $headers['Location'] = $url;
        
        return new static('', $status, $headers);
    }
    
    /**
     * Create a view response
     * 
     * @param string $view The view name
     * @param array $data The view data
     * @param int $status The response status code
     * @param array $headers The response headers
     * @return Response
     */
    public static function view($view, array $data = [], $status = 200, array $headers = [])
    {
        $content = view($view, $data);
        
        return new static($content, $status, $headers);
    }
    
    /**
     * Get the response content
     * 
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * Set the response content
     * 
     * @param string $content The response content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;
        
        return $this;
    }
    
    /**
     * Get the response status code
     * 
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    /**
     * Set the response status code
     * 
     * @param int $statusCode The response status code
     * @return $this
     * @throws \InvalidArgumentException If the status code is invalid
     */
    public function setStatusCode($statusCode)
    {
        if (!isset(static::$statusTexts[$statusCode])) {
            throw new \InvalidArgumentException("Invalid status code: {$statusCode}");
        }
        
        $this->statusCode = $statusCode;
        
        return $this;
    }
    
    /**
     * Get the response status text
     * 
     * @return string
     */
    public function getStatusText()
    {
        return static::$statusTexts[$this->statusCode] ?? '';
    }
    
    /**
     * Get the response headers
     * 
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * Get a response header
     * 
     * @param string $name The header name
     * @param mixed $default The default value if the header doesn't exist
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        return $this->headers[$name] ?? $default;
    }
    
    /**
     * Set a response header
     * 
     * @param string $name The header name
     * @param string $value The header value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        
        return $this;
    }
    
    /**
     * Set multiple response headers
     * 
     * @param array $headers The headers to set
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        
        return $this;
    }
    
    /**
     * Remove a response header
     * 
     * @param string $name The header name
     * @return $this
     */
    public function removeHeader($name)
    {
        unset($this->headers[$name]);
        
        return $this;
    }
    
    /**
     * Check if the response has a header
     * 
     * @param string $name The header name
     * @return bool
     */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }
    
    /**
     * Send the response
     * 
     * @return $this
     */
    public function send()
    {
        // Send the status code
        http_response_code($this->statusCode);
        
        // Send the headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", true);
        }
        
        // Send the content
        echo $this->content;
        
        return $this;
    }
    
    /**
     * Convert the response to a string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->content;
    }
}