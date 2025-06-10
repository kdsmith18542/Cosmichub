<?php
namespace App\Libraries;

/**
 * Base controller class
 */
class Controller
{
    /**
     * Render a view file
     * 
     * @param string $view View file name (without .php)
     * @param array $data Data to pass to the view
     */
    protected function view($view, $data = [])
    {
        extract($data);
        $viewFile = VIEWS_PATH . "/{$view}.php";
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file {$viewFile} not found");
        }
        
        // Start output buffering
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        
        // Include layout if it exists
        $layoutFile = VIEWS_PATH . '/layouts/main.php';
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content;
        }
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Redirect to a different URL
     */
    protected function redirect($url, $statusCode = 302)
    {
        header("Location: $url", true, $statusCode);
        exit;
    }
}
