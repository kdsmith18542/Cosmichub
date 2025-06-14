<?php

namespace App\Core\Controller;

use App\Core\Application;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\UrlGenerator;

/**
 * Base Controller class for all controllers
 */
class Controller
{
    /**
     * @var Application The application instance
     */
    protected $app;
    
    /**
     * Create a new controller instance
     * 
     * @param Application $app The application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    /**
     * Get the application instance
     * 
     * @return Application
     */
    public function getApplication()
    {
        return $this->app;
    }
    
    /**
     * Get the container instance
     * 
     * @return \App\Core\Container
     */
    public function getContainer()
    {
        return $this->app->getContainer();
    }
    
    /**
     * Get the request instance
     * 
     * @return Request
     */
    public function getRequest()
    {
        return $this->app->make(Request::class);
    }
    
    /**
     * Create a new response
     * 
     * @param mixed $content The response content
     * @param int $status The response status code
     * @param array $headers The response headers
     * @return Response
     */
    public function response($content = '', $status = 200, array $headers = [])
    {
        return new Response($content, $status, $headers);
    }
    
    /**
     * Create a new JSON response
     * 
     * @param mixed $data The response data
     * @param int $status The response status code
     * @param array $headers The response headers
     * @return Response
     */
    public function json($data, $status = 200, array $headers = [])
    {
        return Response::json($data, $status, $headers);
    }
    
    /**
     * Create a new redirect response
     * 
     * @param string $url The URL to redirect to
     * @param int $status The response status code
     * @param array $headers The response headers
     * @return Response
     */
    public function redirect($url, $status = 302, array $headers = [])
    {
        return Response::redirect($url, $status, $headers);
    }
    
    /**
     * Create a new view response
     * 
     * @param string $view The view name
     * @param array $data The view data
     * @param int $status The response status code
     * @param array $headers The response headers
     * @return Response
     */
    public function view($view, array $data = [], $status = 200, array $headers = [])
    {
        $viewRenderer = $this->app->make(\App\Core\View::class);
        $content = $viewRenderer->render($view, $data);
        return $this->response($content, $status, $headers);
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
        return $this->app->make(UrlGenerator::class)->route($name, $parameters, $absolute);
    }
    
    /**
     * Generate a URL for a path
     * 
     * @param string $path The path
     * @param array $query The query parameters
     * @param bool $absolute Whether to generate an absolute URL
     * @return string
     */
    public function url($path, array $query = [], $absolute = false)
    {
        return $this->app->make(UrlGenerator::class)->to($path, $query, $absolute);
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
        return $this->app->make(UrlGenerator::class)->asset($path, $query);
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key The configuration key
     * @param mixed $default The default value
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return $this->app->config($key, $default);
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $id The service ID
     * @return mixed
     */
    public function make($id)
    {
        return $this->app->make($id);
    }
    
    /**
     * Call a method on this controller with dependency injection
     * 
     * @param string $method The method name
     * @param array $parameters The method parameters
     * @return mixed
     */
    public function callAction($method, array $parameters = [])
    {
        return $this->app->call([$this, $method], $parameters);
    }
}