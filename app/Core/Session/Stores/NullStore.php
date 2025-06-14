<?php

namespace App\Core\Session\Stores;

use App\Core\Session\Contracts\SessionStoreInterface;

/**
 * Null Session Store
 * 
 * A session store that doesn't actually store any data.
 * Useful for testing or when sessions are disabled.
 */
class NullStore implements SessionStoreInterface
{
    /**
     * Constructor
     * 
     * @param array $config Store configuration (ignored)
     */
    public function __construct(array $config = [])
    {
        // No configuration needed for null store
    }
    
    /**
     * Read session data
     * 
     * @param string $sessionId Session ID
     * @return array|null Always returns null
     */
    public function read(string $sessionId): ?array
    {
        return null;
    }
    
    /**
     * Write session data
     * 
     * @param string $sessionId Session ID
     * @param array $data Session data
     * @return bool Always returns true
     */
    public function write(string $sessionId, array $data): bool
    {
        return true;
    }
    
    /**
     * Destroy session
     * 
     * @param string $sessionId Session ID
     * @return bool Always returns true
     */
    public function destroy(string $sessionId): bool
    {
        return true;
    }
    
    /**
     * Garbage collection
     * 
     * @param int $maxLifetime Maximum session lifetime in seconds
     * @return bool Always returns true
     */
    public function gc(int $maxLifetime): bool
    {
        return true;
    }
    
    /**
     * Check if session exists
     * 
     * @param string $sessionId Session ID
     * @return bool Always returns false
     */
    public function exists(string $sessionId): bool
    {
        return false;
    }
    
    /**
     * Get session timestamp
     * 
     * @param string $sessionId Session ID
     * @return int|null Always returns null
     */
    public function getTimestamp(string $sessionId): ?int
    {
        return null;
    }
    
    /**
     * Update session timestamp
     * 
     * @param string $sessionId Session ID
     * @return bool Always returns true
     */
    public function touch(string $sessionId): bool
    {
        return true;
    }
    
    /**
     * Get all active sessions
     * 
     * @return array Always returns empty array
     */
    public function getAllSessions(): array
    {
        return [];
    }
    
    /**
     * Count active sessions
     * 
     * @return int Always returns 0
     */
    public function countSessions(): int
    {
        return 0;
    }
    
    /**
     * Clear all sessions
     * 
     * @return bool Always returns true
     */
    public function clear(): bool
    {
        return true;
    }
    
    /**
     * Get store statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        return [
            'store_type' => 'null',
            'total_sessions' => 0,
            'memory_usage' => 0,
            'oldest_session' => null,
            'newest_session' => null,
        ];
    }
    
    /**
     * Get session data for debugging
     * 
     * @return array
     */
    public function getDebugInfo(): array
    {
        return [
            'message' => 'Null store does not store any data',
            'sessions' => [],
            'timestamps' => [],
        ];
    }
}