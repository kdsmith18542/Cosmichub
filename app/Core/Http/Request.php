<?php

namespace App\Core\Http;

/**
 * HTTP Request class
 */
class Request
{
    /**
     * @var array The request query parameters ($_GET)
     */
    protected $query = [];
    
    /**
     * @var array The request post parameters ($_POST)
     */
    protected $request = [];
    
    /**
     * @var array The request cookies ($_COOKIE)
     */
    protected $cookies = [];
    
    /**
     * @var array The request files ($_FILES)
     */
    protected $files = [];
    
    /**
     * @var array The request server parameters ($_SERVER)
     */
    protected $server = [];
    
    /**
     * @var array The request headers
     */
    protected $headers = [];
    
    /**
     * @var string The request method
     */
    protected $method;
    
    /**
     * @var string The request URI
     */
    protected $uri;
    
    /**
     * @var string The request path
     */
    protected $path;
    
    /**
     * @var array The route parameters
     */
    protected $routeParams = [];
    
    /**
     * Create a new request instance
     * 
     * @param array $query The GET parameters
     * @param array $request The POST parameters
     * @param array $cookies The COOKIE parameters
     * @param array $files The FILES parameters
     * @param array $server The SERVER parameters
     */
    public function __construct(array $query = [], array $request = [], array $cookies = [], array $files = [], array $server = [])
    {
        $this->query = $query;
        $this->request = $request;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
        
        $this->headers = $this->getHeadersFromServer($server);
        $this->method = $this->getMethodFromServer($server);
        $this->uri = $this->getUriFromServer($server);
        $this->path = $this->getPathFromUri($this->uri);
    }
    
    /**
     * Create a request from the current global variables
     * 
     * @return Request
     */
    public static function capture()
    {
        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }
    
    /**
     * Get the request method
     * 
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
    
    /**
     * Check if the request method is the given method
     * 
     * @param string $method The method to check
     * @return bool
     */
    public function isMethod($method)
    {
        return strtoupper($this->method) === strtoupper($method);
    }
    
    /**
     * Check if the request method is GET
     * 
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }
    
    /**
     * Check if the request method is POST
     * 
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }
    
    /**
     * Check if the request method is PUT
     * 
     * @return bool
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }
    
    /**
     * Check if the request method is DELETE
     * 
     * @return bool
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }
    
    /**
     * Check if the request method is PATCH
     * 
     * @return bool
     */
    public function isPatch()
    {
        return $this->isMethod('PATCH');
    }
    
    /**
     * Get the request URI
     * 
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }
    
    /**
     * Get the request path
     * 
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * Get a query parameter
     * 
     * @param string $key The parameter key
     * @param mixed $default The default value if the parameter doesn't exist
     * @return mixed
     */
    public function query($key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        
        return $this->query[$key] ?? $default;
    }
    
    /**
     * Get a request parameter
     * 
     * @param string $key The parameter key
     * @param mixed $default The default value if the parameter doesn't exist
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        if ($key === null) {
            return $this->request;
        }
        
        return $this->request[$key] ?? $default;
    }
    
    /**
     * Get all input parameters
     * 
     * @return array
     */
    public function all()
    {
        return array_merge($this->query, $this->request);
    }
    
    /**
     * Check if the request has a parameter
     * 
     * @param string $key The parameter key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->query[$key]) || isset($this->request[$key]);
    }
    
    /**
     * Get a cookie
     * 
     * @param string $key The cookie key
     * @param mixed $default The default value if the cookie doesn't exist
     * @return mixed
     */
    public function cookie($key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }
        
        return $this->cookies[$key] ?? $default;
    }
    
    /**
     * Get a file
     * 
     * @param string $key The file key
     * @return mixed
     */
    public function file($key = null)
    {
        if ($key === null) {
            return $this->files;
        }
        
        return $this->files[$key] ?? null;
    }
    
    /**
     * Check if the request has a file
     * 
     * @param string $key The file key
     * @return bool
     */
    public function hasFile($key)
    {
        return isset($this->files[$key]) && !empty($this->files[$key]['tmp_name']);
    }
    
    /**
     * Get a server parameter
     * 
     * @param string $key The parameter key
     * @param mixed $default The default value if the parameter doesn't exist
     * @return mixed
     */
    public function server($key = null, $default = null)
    {
        if ($key === null) {
            return $this->server;
        }
        
        return $this->server[$key] ?? $default;
    }
    
    /**
     * Get a header
     * 
     * @param string $key The header key
     * @param mixed $default The default value if the header doesn't exist
     * @return mixed
     */
    public function header($key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        
        $key = strtolower(str_replace('-', '_', $key));
        
        return $this->headers[$key] ?? $default;
    }
    
    /**
     * Check if the request has a header
     * 
     * @param string $key The header key
     * @return bool
     */
    public function hasHeader($key)
    {
        $key = strtolower(str_replace('-', '_', $key));
        
        return isset($this->headers[$key]);
    }
    
    /**
     * Get the route parameters
     * 
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }
    
    /**
     * Set the route parameters
     * 
     * @param array $params The route parameters
     * @return $this
     */
    public function setRouteParams(array $params)
    {
        $this->routeParams = $params;
        
        return $this;
    }
    
    /**
     * Get a route parameter
     * 
     * @param string $key The parameter key
     * @param mixed $default The default value if the parameter doesn't exist
     * @return mixed
     */
    public function param($key = null, $default = null)
    {
        if ($key === null) {
            return $this->routeParams;
        }
        
        return $this->routeParams[$key] ?? $default;
    }
    
    /**
     * Check if the request is an AJAX request
     * 
     * @return bool
     */
    public function isAjax()
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }
    
    /**
     * Check if the request is a JSON request
     * 
     * @return bool
     */
    public function isJson()
    {
        return strpos($this->header('Content-Type', ''), 'application/json') !== false;
    }
    
    /**
     * Get the request body as JSON
     * 
     * @param bool $assoc Whether to return as an associative array
     * @return mixed
     */
    public function json($assoc = true)
    {
        $content = file_get_contents('php://input');
        
        return json_decode($content, $assoc);
    }
    
    /**
     * Get the client IP address
     * 
     * @return string|null
     */
    public function ip()
    {
        return $this->server('REMOTE_ADDR');
    }
    
    /**
     * Get the request user agent
     * 
     * @return string|null
     */
    public function userAgent()
    {
        return $this->server('HTTP_USER_AGENT');
    }
    
    /**
     * Get the request referer
     * 
     * @return string|null
     */
    public function referer()
    {
        return $this->server('HTTP_REFERER');
    }
    
    /**
     * Get the headers from the server parameters
     * 
     * @param array $server The server parameters
     * @return array
     */
    protected function getHeadersFromServer(array $server)
    {
        $headers = [];
        
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $name = strtolower(str_replace('_', '-', $key));
                $headers[$name] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * Get the request method from the server parameters
     * 
     * @param array $server The server parameters
     * @return string
     */
    protected function getMethodFromServer(array $server)
    {
        return strtoupper($server['REQUEST_METHOD'] ?? 'GET');
    }
    
    /**
     * Get the request URI from the server parameters
     * 
     * @param array $server The server parameters
     * @return string
     */
    protected function getUriFromServer(array $server)
    {
        if (isset($server['REQUEST_URI'])) {
            return $server['REQUEST_URI'];
        }
        
        return '/';
    }
    
    /**
     * Get the request path from the URI
     * 
     * @param string $uri The request URI
     * @return string
     */
    protected function getPathFromUri($uri)
    {
        $path = parse_url($uri, PHP_URL_PATH);
        
        return $path ?: '/';
    }
}