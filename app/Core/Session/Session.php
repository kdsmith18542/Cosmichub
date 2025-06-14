<?php

namespace App\Core\Session;

/**
 * Session class for managing sessions
 */
class Session
{
    /**
     * @var bool Whether the session has been started
     */
    protected $started = false;
    
    /**
     * @var array The session data
     */
    protected $data = [];
    
    /**
     * @var array The flash data
     */
    protected $flash = [];
    
    /**
     * @var array The old flash data
     */
    protected $oldFlash = [];
    
    /**
     * Create a new session instance
     */
    public function __construct()
    {
        // Nothing to do here
    }
    
    /**
     * Start the session
     * 
     * @return bool
     */
    public function start()
    {
        if ($this->started) {
            return true;
        }
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            $this->loadSession();
            return true;
        }
        
        if (session_start()) {
            $this->started = true;
            $this->loadSession();
            return true;
        }
        
        return false;
    }
    
    /**
     * Load the session data
     * 
     * @return void
     */
    protected function loadSession()
    {
        $this->data = &$_SESSION;
        
        // Load flash data
        if (isset($this->data['_flash'])) {
            $this->oldFlash = $this->data['_flash'];
            $this->flash = [];
        } else {
            $this->oldFlash = [];
            $this->flash = [];
        }
    }
    
    /**
     * Save the session data
     * 
     * @return void
     */
    public function save()
    {
        if (!$this->started) {
            return;
        }
        
        // Save flash data
        $this->data['_flash'] = $this->flash;
        
        session_write_close();
    }
    
    /**
     * Get a session value
     * 
     * @param string $key The key
     * @param mixed $default The default value
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!$this->started) {
            $this->start();
        }
        
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Set a session value
     * 
     * @param string $key The key
     * @param mixed $value The value
     * @return $this
     */
    public function set($key, $value)
    {
        if (!$this->started) {
            $this->start();
        }
        
        $this->data[$key] = $value;
        
        return $this;
    }
    
    /**
     * Check if a session value exists
     * 
     * @param string $key The key
     * @return bool
     */
    public function has($key)
    {
        if (!$this->started) {
            $this->start();
        }
        
        return isset($this->data[$key]);
    }
    
    /**
     * Remove a session value
     * 
     * @param string $key The key
     * @return $this
     */
    public function remove($key)
    {
        if (!$this->started) {
            $this->start();
        }
        
        unset($this->data[$key]);
        
        return $this;
    }
    
    /**
     * Get all session data
     * 
     * @return array
     */
    public function all()
    {
        if (!$this->started) {
            $this->start();
        }
        
        return $this->data;
    }
    
    /**
     * Clear all session data
     * 
     * @return $this
     */
    public function clear()
    {
        if (!$this->started) {
            $this->start();
        }
        
        $this->data = [];
        
        return $this;
    }
    
    /**
     * Flash a value for the next request
     * 
     * @param string $key The key
     * @param mixed $value The value
     * @return $this
     */
    public function flash($key, $value)
    {
        if (!$this->started) {
            $this->start();
        }
        
        $this->flash[$key] = $value;
        
        return $this;
    }
    
    /**
     * Get a flashed value
     * 
     * @param string $key The key
     * @param mixed $default The default value
     * @return mixed
     */
    public function getFlash($key, $default = null)
    {
        if (!$this->started) {
            $this->start();
        }
        
        return $this->oldFlash[$key] ?? $default;
    }
    
    /**
     * Check if a flashed value exists
     * 
     * @param string $key The key
     * @return bool
     */
    public function hasFlash($key)
    {
        if (!$this->started) {
            $this->start();
        }
        
        return isset($this->oldFlash[$key]);
    }
    
    /**
     * Keep all flashed data for the next request
     * 
     * @return $this
     */
    public function reflash()
    {
        if (!$this->started) {
            $this->start();
        }
        
        $this->flash = array_merge($this->flash, $this->oldFlash);
        
        return $this;
    }
    
    /**
     * Keep specific flashed data for the next request
     * 
     * @param array|string $keys The keys
     * @return $this
     */
    public function keep($keys)
    {
        if (!$this->started) {
            $this->start();
        }
        
        $keys = is_array($keys) ? $keys : func_get_args();
        
        foreach ($keys as $key) {
            if (isset($this->oldFlash[$key])) {
                $this->flash[$key] = $this->oldFlash[$key];
            }
        }
        
        return $this;
    }
    
    /**
     * Generate a CSRF token
     * 
     * @return string
     */
    public function generateCsrfToken()
    {
        if (!$this->started) {
            $this->start();
        }
        
        $token = bin2hex(random_bytes(32));
        
        $this->set('_csrf_token', $token);
        
        return $token;
    }
    
    /**
     * Get the CSRF token
     * 
     * @return string|null
     */
    public function getCsrfToken()
    {
        if (!$this->started) {
            $this->start();
        }
        
        if (!$this->has('_csrf_token')) {
            return $this->generateCsrfToken();
        }
        
        return $this->get('_csrf_token');
    }
    
    /**
     * Validate a CSRF token
     * 
     * @param string $token The token
     * @return bool
     */
    public function validateCsrfToken($token)
    {
        if (!$this->started) {
            $this->start();
        }
        
        return $this->has('_csrf_token') && hash_equals($this->get('_csrf_token'), $token);
    }
    
    /**
     * Regenerate the session ID
     * 
     * @param bool $deleteOldSession Whether to delete the old session
     * @return bool
     */
    public function regenerate($deleteOldSession = false)
    {
        if (!$this->started) {
            $this->start();
        }
        
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Get the session ID
     * 
     * @return string
     */
    public function getId()
    {
        if (!$this->started) {
            $this->start();
        }
        
        return session_id();
    }
    
    /**
     * Set the session ID
     * 
     * @param string $id The session ID
     * @return bool
     */
    public function setId($id)
    {
        if ($this->started) {
            return false;
        }
        
        return session_id($id);
    }
    
    /**
     * Get the session name
     * 
     * @return string
     */
    public function getName()
    {
        return session_name();
    }
    
    /**
     * Set the session name
     * 
     * @param string $name The session name
     * @return bool
     */
    public function setName($name)
    {
        if ($this->started) {
            return false;
        }
        
        return session_name($name);
    }
    
    /**
     * Destroy the session
     * 
     * @return bool
     */
    public function destroy()
    {
        if (!$this->started) {
            return false;
        }
        
        $this->clear();
        
        $this->started = false;
        
        return session_destroy();
    }
}