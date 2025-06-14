<?php

namespace App\Core\Session;

use App\Core\Session\Contracts\SessionManagerInterface;
use App\Core\Session\Contracts\SessionStoreInterface;
use App\Core\Session\Stores\FileStore;
use App\Core\Session\Stores\ArrayStore;
use App\Core\Session\Stores\NullStore;
use App\Core\Session\Handlers\EncryptedHandler;
use App\Core\Session\Handlers\DatabaseHandler;
use InvalidArgumentException;

/**
 * Session Manager
 * 
 * Manages session stores and provides a unified interface for session operations.
 */
class SessionManager implements SessionManagerInterface
{
    /**
     * Session configuration
     * 
     * @var array
     */
    protected array $config;
    
    /**
     * Session stores
     * 
     * @var array
     */
    protected array $stores = [];
    
    /**
     * Default store name
     * 
     * @var string
     */
    protected string $defaultStore;
    
    /**
     * Current session ID
     * 
     * @var string|null
     */
    protected ?string $sessionId = null;
    
    /**
     * Session data
     * 
     * @var array
     */
    protected array $data = [];
    
    /**
     * Session started flag
     * 
     * @var bool
     */
    protected bool $started = false;
    
    /**
     * Constructor
     * 
     * @param array $config Session configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultStore = $config['default'] ?? 'file';
        
        $this->configureStores();
    }
    
    /**
     * Configure session stores
     * 
     * @return void
     */
    protected function configureStores(): void
    {
        $stores = $this->config['stores'] ?? [];
        
        foreach ($stores as $name => $config) {
            $this->stores[$name] = $this->createStore($config);
        }
    }
    
    /**
     * Create a session store
     * 
     * @param array $config Store configuration
     * @return SessionStoreInterface
     * @throws InvalidArgumentException
     */
    protected function createStore(array $config): SessionStoreInterface
    {
        $driver = $config['driver'] ?? 'file';
        
        switch ($driver) {
            case 'file':
                return new FileStore($config);
                
            case 'array':
                return new ArrayStore($config);
                
            case 'null':
                return new NullStore($config);
                
            case 'encrypted':
                $baseStore = $this->createStore($config['store'] ?? ['driver' => 'file']);
                return new EncryptedHandler($baseStore, $config);
                
            case 'database':
                return new DatabaseHandler($config);
                
            default:
                throw new InvalidArgumentException("Unsupported session driver: {$driver}");
        }
    }
    
    /**
     * Get a session store
     * 
     * @param string|null $name Store name
     * @return SessionStoreInterface
     * @throws InvalidArgumentException
     */
    public function store(?string $name = null): SessionStoreInterface
    {
        $name = $name ?: $this->defaultStore;
        
        if (!isset($this->stores[$name])) {
            throw new InvalidArgumentException("Session store [{$name}] not configured.");
        }
        
        return $this->stores[$name];
    }
    
    /**
     * Start the session
     * 
     * @param string|null $sessionId Optional session ID
     * @return bool
     */
    public function start(?string $sessionId = null): bool
    {
        if ($this->started) {
            return true;
        }
        
        $this->sessionId = $sessionId ?: $this->generateSessionId();
        $this->data = $this->store()->read($this->sessionId) ?: [];
        $this->started = true;
        
        return true;
    }
    
    /**
     * Save the session
     * 
     * @return bool
     */
    public function save(): bool
    {
        if (!$this->started) {
            return false;
        }
        
        return $this->store()->write($this->sessionId, $this->data);
    }
    
    /**
     * Destroy the session
     * 
     * @return bool
     */
    public function destroy(): bool
    {
        if (!$this->started) {
            return false;
        }
        
        $result = $this->store()->destroy($this->sessionId);
        $this->data = [];
        $this->sessionId = null;
        $this->started = false;
        
        return $result;
    }
    
    /**
     * Regenerate session ID
     * 
     * @param bool $deleteOld Whether to delete old session data
     * @return bool
     */
    public function regenerate(bool $deleteOld = false): bool
    {
        if (!$this->started) {
            return false;
        }
        
        $oldId = $this->sessionId;
        $this->sessionId = $this->generateSessionId();
        
        // Save data with new ID
        $this->store()->write($this->sessionId, $this->data);
        
        // Delete old session if requested
        if ($deleteOld) {
            $this->store()->destroy($oldId);
        }
        
        return true;
    }
    
    /**
     * Get session ID
     * 
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->sessionId;
    }
    
    /**
     * Set session ID
     * 
     * @param string $id Session ID
     * @return void
     */
    public function setId(string $id): void
    {
        $this->sessionId = $id;
    }
    
    /**
     * Get session data
     * 
     * @param string $key Data key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Set session data
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }
    
    /**
     * Check if session has data
     * 
     * @param string $key Data key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }
    
    /**
     * Remove session data
     * 
     * @param string $key Data key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }
    
    /**
     * Get all session data
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }
    
    /**
     * Clear all session data
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->data = [];
    }
    
    /**
     * Flash data for next request
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function flash(string $key, $value): void
    {
        $this->set('_flash.' . $key, $value);
    }
    
    /**
     * Get flash data
     * 
     * @param string $key Data key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getFlash(string $key, $default = null)
    {
        $value = $this->get('_flash.' . $key, $default);
        $this->remove('_flash.' . $key);
        return $value;
    }
    
    /**
     * Check if session is started
     * 
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }
    
    /**
     * Generate a new session ID
     * 
     * @return string
     */
    protected function generateSessionId(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Garbage collection
     * 
     * @param int $maxLifetime Maximum session lifetime
     * @return bool
     */
    public function gc(int $maxLifetime): bool
    {
        return $this->store()->gc($maxLifetime);
    }
    
    /**
     * Get session statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        $store = $this->store();
        
        $stats = [
            'session_id' => $this->sessionId,
            'started' => $this->started,
            'data_count' => count($this->data),
            'store' => $this->defaultStore,
        ];
        
        if (method_exists($store, 'getStats')) {
            $stats['store_stats'] = $store->getStats();
        }
        
        return $stats;
    }
}