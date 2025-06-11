<?php
namespace App\Controllers;

/**
 * Test controller for routing tests
 */
class TestController
{
    /**
     * Handle GET requests
     */
    public function getMethod()
    {
        echo "GET method handler called\n";
    }
    
    /**
     * Handle POST requests
     */
    public function postMethod()
    {
        echo "POST method handler called\n";
    }
    
    /**
     * Handle PUT requests
     */
    public function putMethod()
    {
        echo "PUT method handler called\n";
    }
    
    /**
     * Handle DELETE requests
     */
    public function deleteMethod()
    {
        echo "DELETE method handler called\n";
    }
    
    /**
     * Handle PATCH requests
     */
    public function patchMethod()
    {
        echo "PATCH method handler called\n";
    }
    
    /**
     * Handle ANY HTTP method
     */
    public function anyMethod()
    {
        echo "ANY method handler called (supports all HTTP methods)\n";
    }
    
    /**
     * Test action (legacy)
     */
    public function index()
    {
        echo "Test route is working!\n";
        return true;
    }
}
