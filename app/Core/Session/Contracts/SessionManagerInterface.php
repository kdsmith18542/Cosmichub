<?php

namespace App\Core\Session\Contracts;

/**
 * Session Manager Interface
 * 
 * Defines the contract for session managers.
 */
interface SessionManagerInterface
{
    /**
     * Get a session store
     * 
     * @param string|null $name Store name
     * @return SessionStoreInterface
     */
    public function store(?string $name = null): SessionStoreInterface;
    
    /**
     * Start the session
     * 
     * @param string|null $sessionId Optional session ID
     * @return bool
     */
    public function start(?string $sessionId = null): bool;
    
    /**
     * Save the session
     * 
     * @return bool
     */
    public function save(): bool;
    
    /**
     * Destroy the session
     * 
     * @return bool
     */
    public function destroy(): bool;
    
    /**
     * Regenerate session ID
     * 
     * @param bool $deleteOld Whether to delete old session data
     * @return bool
     */
    public function regenerate(bool $deleteOld = false): bool;
    
    /**
     * Get session ID
     * 
     * @return string|null
     */
    public function getId(): ?string;
    
    /**
     * Set session ID
     * 
     * @param string $id Session ID
     * @return void
     */
    public function setId(string $id): void;
    
    /**
     * Get session data
     * 
     * @param string $key Data key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, $default = null);
    
    /**
     * Set session data
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function set(string $key, $value): void;
    
    /**
     * Check if session has data
     * 
     * @param string $key Data key
     * @return bool
     */
    public function has(string $key): bool;
    
    /**
     * Remove session data
     * 
     * @param string $key Data key
     * @return void
     */
    public function remove(string $key): void;
    
    /**
     * Get all session data
     * 
     * @return array
     */
    public function all(): array;
    
    /**
     * Clear all session data
     * 
     * @return void
     */
    public function clear(): void;
    
    /**
     * Flash data for next request
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function flash(string $key, $value): void;
    
    /**
     * Get flash data
     * 
     * @param string $key Data key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getFlash(string $key, $default = null);
    
    /**
     * Check if session is started
     * 
     * @return bool
     */
    public function isStarted(): bool;
    
    /**
     * Garbage collection
     * 
     * @param int $maxLifetime Maximum session lifetime
     * @return bool
     */
    public function gc(int $maxLifetime): bool;
    
    /**
     * Get session statistics
     * 
     * @return array
     */
    public function getStats(): array;
}