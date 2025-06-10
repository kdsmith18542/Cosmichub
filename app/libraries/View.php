<?php
/**
 * View Class
 * 
 * Handles view rendering with support for layouts and sections.
 */

class View {
    /**
     * @var string The path to the views directory
     */
    protected $viewsPath;
    
    /**
     * @var array The data to pass to the view
     */
    protected $data = [];
    
    /**
     * @var string The current view being rendered
     */
    protected $currentView;
    
    /**
     * @var string The current layout being used
     */
    protected $layout;
    
    /**
     * @var array Sections defined in views
     */
    protected $sections = [];
    
    /**
     * @var string The current section being captured
     */
    protected $currentSection;
    
    /**
     * @var array Stack of sections being rendered
     */
    protected $sectionStack = [];
    
    /**
     * Create a new View instance
     * 
     * @param string $viewsPath The path to the views directory
     */
    public function __construct($viewsPath = null) {
        $this->viewsPath = $viewsPath ?: __DIR__ . '/../views';
    }
    
    /**
     * Create a new view instance
     * 
     * @param string $view The view name
     * @param array $data Data to pass to the view
     * @return static
     */
    public static function make($view, $data = []) {
        static $instance = null;
        
        if (is_null($instance)) {
            $instance = new static();
        }
        
        return $instance->makeView($view, $data);
    }
    
    /**
     * Create a new view instance (non-static)
     * 
     * @param string $view The view name
     * @param array $data Data to pass to the view
     * @return $this
     */
    public function makeView($view, $data = []) {
        $this->currentView = $this->normalizeViewName($view);
        $this->data = $data;
        return $this;
    }
    
    /**
     * Set the layout to use
     * 
     * @param string $layout The layout name
     * @return $this
     */
    public function layout($layout) {
        $this->layout = $this->normalizeViewName($layout, 'layouts');
        return $this;
    }
    
    /**
     * Normalize view name and path
     * 
     * @param string $view The view name
     * @param string $type The view type (e.g., 'layouts')
     * @return string
     */
    protected function normalizeViewName($view, $type = '') {
        $view = str_replace('.', '/', $view);
        
        if ($type) {
            $view = "{$type}/{$view}";
        }
        
        return $view . '.php';
    }
    
    /**
     * Add a piece of data to the view
     * 
     * @param string|array $key
     * @param mixed $value
     * @return $this
     */
    public function with($key, $value = null) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Render the view
     * 
     * @return string
     * @throws \RuntimeException If the view file doesn't exist
     */
    public function render() {
        $viewPath = $this->viewsPath . '/' . $this->currentView;
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View [{$this->currentView}] not found.");
        }
        
        // Extract the data to be available in the view
        extract($this->data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        include $viewPath;
        
        // If a layout is set, capture the view content and render the layout
        if ($this->layout) {
            $content = ob_get_clean();
            
            $layoutPath = $this->viewsPath . '/' . $this->layout;
            
            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout [{$this->layout}] not found.");
            }
            
            // Start a new output buffer for the layout
            ob_start();
            
            // Include the layout file
            include $layoutPath;
            
            // Get the final output
            $content = ob_get_clean();
        } else {
            // No layout, just get the view content
            $content = ob_get_clean();
        }
        
        return $content;
    }
    
    /**
     * Render the view and return it as a string
     * 
     * @return string
     */
    public function __toString() {
        return $this->render();
    }
    
    /**
     * Start a section
     * 
     * @param string $name The section name
     * @return void
     */
    public function section($name) {
        $this->currentSection = $name;
        ob_start();
    }
    
    /**
     * End the current section
     * 
     * @return void
     */
    public function endSection() {
        if (is_null($this->currentSection)) {
            throw new \RuntimeException('Cannot end a section without first starting one.');
        }
        
        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }
    
    /**
     * Show the content of a section
     * 
     * @param string $name The section name
     * @param string $default Default content if section doesn't exist
     * @return void
     */
    public function show($name, $default = '') {
        echo $this->sections[$name] ?? $default;
    }
    
    /**
     * Extend a layout
     * 
     * @param string $layout The layout to extend
     * @return void
     */
    public function extend($layout) {
        $this->layout($layout);
    }
    
    /**
     * Include a sub-view
     * 
     * @param string $view The view to include
     * @param array $data Additional data to pass to the included view
     * @return void
     */
    public function include($view, $data = []) {
        $view = $this->normalizeViewName($view);
        $viewPath = $this->viewsPath . '/' . $view;
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View [{$view}] not found.");
        }
        
        // Extract the data to be available in the included view
        extract(array_merge($this->data, $data));
        
        // Include the view file
        include $viewPath;
    }
    
    /**
     * Check if a section exists
     * 
     * @param string $name The section name
     * @return bool
     */
    public function hasSection($name) {
        return isset($this->sections[$name]);
    }
    
    /**
     * Get the content of a section
     * 
     * @param string $name The section name
     * @param string $default Default content if section doesn't exist
     * @return string
     */
    public function getSection($name, $default = '') {
        return $this->sections[$name] ?? $default;
    }
    
    /**
     * Start pushing to a stack
     * 
     * @param string $name The stack name
     * @return void
     */
    public function push($name) {
        if (!isset($this->sectionStack[$name])) {
            $this->sectionStack[$name] = [];
        }
        
        $this->currentSection = $name;
        ob_start();
    }
    
    /**
     * End pushing to a stack
     * 
     * @return void
     */
    public function endPush() {
        if (is_null($this->currentSection)) {
            throw new \RuntimeException('Cannot end a stack push without first starting one.');
        }
        
        if (!isset($this->sectionStack[$this->currentSection])) {
            $this->sectionStack[$this->currentSection] = [];
        }
        
        $this->sectionStack[$this->currentSection][] = ob_get_clean();
        $this->currentSection = null;
    }
    
    /**
     * Get the content of a stack
     * 
     * @param string $name The stack name
     * @param string $default Default content if stack is empty
     * @return string
     */
    public function stack($name, $default = '') {
        if (!isset($this->sectionStack[$name]) || empty($this->sectionStack[$name])) {
            return $default;
        }
        
        return implode('', $this->sectionStack[$name]);
    }
}
