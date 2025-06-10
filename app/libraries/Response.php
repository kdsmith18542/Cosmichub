<?php
/**
 * HTTP Response Class
 * 
 * Handles HTTP responses with support for different content types.
 */

class Response {
    /**
     * @var string The response content
     */
    protected $content;
    
    /**
     * @var int The HTTP status code
     */
    protected $statusCode;
    
    /**
     * @var array The HTTP headers
     */
    protected $headers = [];
    
    /**
     * @var array Status code texts
     */
    protected static $statusTexts = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        
        // Successful 2xx
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
        
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated but included for completeness
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        
        // Client Errors 4xx
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
        
        // Server Errors 5xx
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
     * Create a new response
     * 
     * @param mixed $content The response content
     * @param int $status The HTTP status code
     * @param array $headers The HTTP headers
     */
    public function __construct($content = '', $status = 200, array $headers = []) {
        $this->setContent($content);
        $this->setStatusCode($status);
        $this->setHeaders($headers);
    }
    
    /**
     * Set the response content
     * 
     * @param mixed $content
     * @return $this
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Get the response content
     * 
     * @return mixed
     */
    public function getContent() {
        return $this->content;
    }
    
    /**
     * Set the HTTP status code
     * 
     * @param int $code
     * @param string|null $text
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setStatusCode($code, $text = null) {
        $this->statusCode = $code = (int) $code;
        
        if ($this->isInvalid()) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
        }
        
        if (null === $text) {
            $this->statusText = self::$statusTexts[$code] ?? 'unknown status';
        } else {
            $this->statusText = $text;
        }
        
        return $this;
    }
    
    /**
     * Get the HTTP status code
     * 
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    /**
     * Set multiple HTTP headers at once
     * 
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers) {
        $this->headers = [];
        
        foreach ($headers as $key => $values) {
            $this->setHeader($key, $values);
        }
        
        return $this;
    }
    
    /**
     * Set an HTTP header
     * 
     * @param string $key The header name
     * @param string|string[] $values The header value(s)
     * @param bool $replace Whether to replace an existing header
     * @return $this
     */
    public function setHeader($key, $values, $replace = true) {
        $key = str_replace('_', '-', strtolower($key));
        
        if (is_array($values)) {
            $values = array_values($values);
            
            if (true === $replace || !isset($this->headers[$key])) {
                $this->headers[$key] = $values;
            } else {
                $this->headers[$key] = array_merge($this->headers[$key], $values);
            }
        } else {
            if (true === $replace || !isset($this->headers[$key])) {
                $this->headers[$key] = [$values];
            } else {
                $this->headers[$key][] = $values;
            }
        }
        
        return $this;
    }
    
    /**
     * Get all headers
     * 
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }
    
    /**
     * Check if a header exists
     * 
     * @param string $key
     * @return bool
     */
    public function hasHeader($key) {
        return array_key_exists(str_replace('_', '-', strtolower($key)), $this->headers);
    }
    
    /**
     * Remove a header
     * 
     * @param string $key
     * @return $this
     */
    public function removeHeader($key) {
        $key = str_replace('_', '-', strtolower($key));
        unset($this->headers[$key]);
        return $this;
    }
    
    /**
     * Set a cookie
     * 
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite
     * @return $this
     */
    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httpOnly = true, $sameSite = 'Lax') {
        $cookie = sprintf(
            '%s=%s%s%s',
            $name,
            rawurlencode($value),
            $expire ? '; expires=' . gmdate('D, d-M-Y H:i:s T', $expire) : '',
            $path ? '; path=' . $path : '',
            $domain ? '; domain=' . $domain : '',
            $secure ? '; secure' : '',
            $httpOnly ? '; HttpOnly' : '',
            $sameSite ? '; SameSite=' . $sameSite : ''
        );
        
        $this->setHeader('Set-Cookie', $cookie, false);
        
        return $this;
    }
    
    /**
     * Remove a cookie
     * 
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return $this
     */
    public function removeCookie($name, $path = '/', $domain = '', $secure = false, $httpOnly = true) {
        $this->setCookie($name, '', time() - 3600, $path, $domain, $secure, $httpOnly);
        return $this;
    }
    
    /**
     * Set the response content type
     * 
     * @param string $contentType
     * @param string $charset
     * @return $this
     */
    public function setContentType($contentType, $charset = 'UTF-8') {
        $this->setHeader('Content-Type', $contentType . '; charset=' . $charset);
        return $this;
    }
    
    /**
     * Set a redirect response
     * 
     * @param string $url
     * @param int $status
     * @return $this
     */
    public function redirect($url, $status = 302) {
        $this->setStatusCode($status);
        $this->setHeader('Location', $url);
        
        return $this;
    }
    
    /**
     * Set a JSON response
     * 
     * @param mixed $data
     * @param int $status
     * @param int $options
     * @return $this
     */
    public function json($data, $status = 200, $options = 0) {
        $this->setContentType('application/json');
        $this->setStatusCode($status);
        $this->content = json_encode($data, $options);
        
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException(json_last_error_msg());
        }
        
        return $this;
    }
    
    /**
     * Set a JSONP response
     * 
     * @param string $callback
     * @param mixed $data
     * @param int $status
     * @param int $options
     * @return $this
     */
    public function jsonp($callback, $data, $status = 200, $options = 0) {
        $json = json_encode($data, $options);
        
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException(json_last_error_msg());
        }
        
        $this->setContentType('application/javascript');
        $this->setStatusCode($status);
        $this->content = sprintf('%s(%s);', $callback, $json);
        
        return $this;
    }
    
    /**
     * Set a file download response
     * 
     * @param string $file The path to the file
     * @param string $name The file name
     * @param string $disposition The content disposition (attachment/inline)
     * @return $this
     */
    public function download($file, $name = null, $disposition = 'attachment') {
        if (!is_file($file)) {
            throw new \RuntimeException(sprintf('The file "%s" does not exist.', $file));
        }
        
        $name = $name ?: basename($file);
        $mimeType = mime_content_type($file) ?: 'application/octet-stream';
        
        $this->setContentType($mimeType);
        $this->setHeader('Content-Length', (string) filesize($file));
        $this->setHeader('Content-Disposition', sprintf('%s; filename="%s"', $disposition, $name));
        $this->content = file_get_contents($file);
        
        return $this;
    }
    
    /**
     * Set a file response
     * 
     * @param string $file The path to the file
     * @param string $name The file name
     * @return $this
     */
    public function file($file, $name = null) {
        return $this->download($file, $name, 'inline');
    }
    
    /**
     * Check if the response is invalid
     * 
     * @return bool
     */
    public function isInvalid() {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }
    
    /**
     * Check if the response is successful
     * 
     * @return bool
     */
    public function isSuccessful() {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
    
    /**
     * Check if the response is a redirection
     * 
     * @return bool
     */
    public function isRedirection() {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }
    
    /**
     * Check if the response indicates a client error
     * 
     * @return bool
     */
    public function isClientError() {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }
    
    /**
     * Check if the response indicates a server error
     * 
     * @return bool
     */
    public function isServerError() {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }
    
    /**
     * Check if the response is ok (status 200-299)
     * 
     * @return bool
     */
    public function isOk() {
        return 200 === $this->statusCode;
    }
    
    /**
     * Check if the response is forbidden (status 403)
     * 
     * @return bool
     */
    public function isForbidden() {
        return 403 === $this->statusCode;
    }
    
    /**
     * Check if the response is not found (status 404)
     * 
     * @return bool
     */
    public function isNotFound() {
        return 404 === $this->statusCode;
    }
    
    /**
     * Check if the response is empty (status 204 or 304)
     * 
     * @return bool
     */
    public function isEmpty() {
        return in_array($this->statusCode, [204, 304]);
    }
    
    /**
     * Send HTTP headers
     * 
     * @return $this
     */
    public function sendHeaders() {
        // Status line
        header(sprintf('HTTP/%s %s %s', '1.1', $this->statusCode, $this->statusText), true, $this->statusCode);
        
        // Headers
        foreach ($this->headers as $name => $values) {
            $replace = 0 === strcasecmp($name, 'Content-Type');
            
            foreach ((array) $values as $value) {
                header($name . ': ' . $value, $replace, $this->statusCode);
                $replace = false;
            }
        }
        
        // Cookies
        if (isset($this->headers['set-cookie'])) {
            foreach ($this->headers['set-cookie'] as $cookie) {
                header('Set-Cookie: ' . $cookie, false, $this->statusCode);
            }
        }
        
        return $this;
    }
    
    /**
     * Send the response content
     * 
     * @return $this
     */
    public function sendContent() {
        echo $this->content;
        return $this;
    }
    
    /**
     * Send the response
     * 
     * @return $this
     */
    public function send() {
        $this->sendHeaders();
        
        if (!$this->isEmpty()) {
            $this->sendContent();
        }
        
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            static::closeOutputBuffers(0, true);
        }
        
        return $this;
    }
    
    /**
     * Cleans or flushes output buffers up to target level.
     *
     * @param int $targetLevel The target output buffer level
     * @param bool $flush Whether to flush or clean the buffers
     */
    public static function closeOutputBuffers($targetLevel, $flush)
    {
        $status = ob_get_status(true);
        $level = count($status);
        $flags = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSH : PHP_OUTPUT_HANDLER_CLEAN);

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }
}
