<?php

namespace App\Core\Routing;

use App\Core\Http\Request;

/**
 * UrlGenerator class for generating URLs
 */
class UrlGenerator
{
    /**
     * @var RouteCollection The route collection
     */
    protected $routes;
    
    /**
     * @var Request The current request
     */
    protected $request;
    
    /**
     * @var string The root URL
     */
    protected $rootUrl;
    
    /**
     * Create a new URL generator instance
     * 
     * @param RouteCollection $routes The route collection
     * @param Request $request The current request
     * @param string $rootUrl The root URL
     */
    public function __construct(RouteCollection $routes, Request $request, $rootUrl = null)
    {
        $this->routes = $routes;
        $this->request = $request;
        $this->rootUrl = $rootUrl ?: $this->getRootUrlFromRequest($request);
    }
    
    /**
     * Generate a URL for a named route
     * 
     * @param string $name The route name
     * @param array $parameters The route parameters
     * @param bool $absolute Whether to generate an absolute URL
     * @return string
     */
    public function route($name, array $parameters = [], $absolute = false)
    {
        // Get the route path
        $path = $this->routes->url($name, $parameters);
        
        // If the route doesn't exist, throw an exception
        if ($path === null) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }
        
        // If the URL should be absolute, prepend the root URL
        if ($absolute) {
            return $this->rootUrl . $path;
        }
        
        return $path;
    }
    
    /**
     * Generate a URL for a path
     * 
     * @param string $path The path
     * @param array $query The query parameters
     * @param bool $absolute Whether to generate an absolute URL
     * @return string
     */
    public function to($path, array $query = [], $absolute = false)
    {
        // Normalize the path
        $path = $this->normalizePath($path);
        
        // Add the query parameters
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }
        
        // If the URL should be absolute, prepend the root URL
        if ($absolute) {
            return $this->rootUrl . $path;
        }
        
        return $path;
    }
    
    /**
     * Generate an absolute URL for a path
     * 
     * @param string $path The path
     * @param array $query The query parameters
     * @return string
     */
    public function asset($path, array $query = [])
    {
        // Normalize the path
        $path = $this->normalizePath($path);
        
        // Add the query parameters
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }
        
        return $this->rootUrl . $path;
    }
    
    /**
     * Generate a URL for the current path with different query parameters
     * 
     * @param array $query The query parameters
     * @param bool $absolute Whether to generate an absolute URL
     * @return string
     */
    public function current(array $query = [], $absolute = false)
    {
        // Get the current path
        $path = $this->request->getPath();
        
        // Add the query parameters
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }
        
        // If the URL should be absolute, prepend the root URL
        if ($absolute) {
            return $this->rootUrl . $path;
        }
        
        return $path;
    }
    
    /**
     * Generate a URL for the previous path
     * 
     * @param string $fallback The fallback path
     * @param bool $absolute Whether to generate an absolute URL
     * @return string
     */
    public function previous($fallback = '/', $absolute = false)
    {
        // Get the referer from the request
        $referer = $this->request->getReferer();
        
        // If the referer is empty, use the fallback
        if (empty($referer)) {
            return $this->to($fallback, [], $absolute);
        }
        
        // If the URL should be absolute, return the referer
        if ($absolute) {
            return $referer;
        }
        
        // Remove the root URL from the referer
        $path = str_replace($this->rootUrl, '', $referer);
        
        return $path;
    }
    
    /**
     * Get the root URL from the request
     * 
     * @param Request $request The request
     * @return string
     */
    protected function getRootUrlFromRequest(Request $request)
    {
        // Get the scheme
        $scheme = $request->isSecure() ? 'https' : 'http';
        
        // Get the host
        $host = $request->getHost();
        
        // Get the port
        $port = $request->getPort();
        
        // Build the root URL
        $rootUrl = $scheme . '://' . $host;
        
        // Add the port if it's not the default port
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $rootUrl .= ':' . $port;
        }
        
        return $rootUrl;
    }
    
    /**
     * Normalize a path
     * 
     * @param string $path The path
     * @return string
     */
    protected function normalizePath($path)
    {
        // Remove leading and trailing slashes
        $path = trim($path, '/');
        
        // Add a leading slash if the path is not empty
        if (!empty($path)) {
            $path = '/' . $path;
        }
        
        return $path;
    }
    
    /**
     * Set the root URL
     * 
     * @param string $rootUrl The root URL
     * @return $this
     */
    public function setRootUrl($rootUrl)
    {
        $this->rootUrl = $rootUrl;
        
        return $this;
    }
    
    /**
     * Get the root URL
     * 
     * @return string
     */
    public function getRootUrl()
    {
        return $this->rootUrl;
    }
}