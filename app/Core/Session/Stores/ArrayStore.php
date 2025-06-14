<?php

namespace App\Core\Session\Stores;

use App\Core\Session\Contracts\SessionStoreInterface;

/**
 * Array Session Store
 * 
 * Stores session data in memory using PHP arrays.
 * Note: Data is lost when the process ends.
 */
class ArrayStore implements SessionStoreInterface
{
    /**
     * Session data storage
     * 
     * @var array
     */
    protected static array $sessions = [];
    
    /**
     * Session timestamps
     * 
     * @var array
     */
    protected static array $timestamps = [];
    
    /**
     * Maximum number of sessions
     * 
     * @var int
     */
    protected int $maxSessions;
    
    /**
     * Constructor
     * 
     * @param array $config Store configuration
     */
    public function __construct(array $config = [])
    {
        $this->maxSessions = $config['max_sessions'] ?? 1000;
    }
    
    /**
     * Read session data
     * 
     * @param string $sessionId Session ID
     * @return array|null Session data or null if not found
     */
    public function read(string $sessionId): ?array
    {
        if (!isset(static::$sessions[$sessionId])) {
            return null;
        }
        
        // Update access time
        static::$timestamps[$sessionId] = time();
        
        return static::$sessions[$sessionId];
    }
    
    /**
     * Write session data
     * 
     * @param string $sessionId Session ID
     * @param array $data Session data
     * @return bool Success status
     */
    public function write(string $sessionId, array $data): bool
    {
        // Check if we need to make room for new sessions
        if (!isset(static::$sessions[$sessionId]) && count(static::$sessions) >= $this->maxSessions) {
            $this->evictOldestSession();
        }
        
        static::$sessions[$sessionId] = $data;
        static::$timestamps[$sessionId] = time();
        
        return true;
    }
    
    /**
     * Destroy session
     * 
     * @param string $sessionId Session ID
     * @return bool Success status
     */
    public function destroy(string $sessionId): bool
    {
        unset(static::$sessions[$sessionId]);
        unset(static::$timestamps[$sessionId]);
        
        return true;
    }
    
    /**
     * Garbage collection
     * 
     * @param int $maxLifetime Maximum session lifetime in seconds
     * @return bool Success status
     */
    public function gc(int $maxLifetime): bool
    {
        $now = time();
        $expired = [];
        
        foreach (static::$timestamps as $sessionId => $timestamp) {
            if (($now - $timestamp) > $maxLifetime) {
                $expired[] = $sessionId;
            }
        }
        
        foreach ($expired as $sessionId) {
            $this->destroy($sessionId);
        }
        
        return true;
    }
    
    /**
     * Check if session exists
     * 
     * @param string $sessionId Session ID
     * @return bool
     */
    public function exists(string $sessionId): bool
    {
        return isset(static::$sessions[$sessionId]);
    }
    
    /**
     * Get session timestamp
     * 
     * @param string $sessionId Session ID
     * @return int|null Timestamp or null if not found
     */
    public function getTimestamp(string $sessionId): ?int
    {
        return static::$timestamps[$sessionId] ?? null;
    }
    
    /**
     * Update session timestamp
     * 
     * @param string $sessionId Session ID
     * @return bool Success status
     */
    public function touch(string $sessionId): bool
    {
        if (!isset(static::$sessions[$sessionId])) {
            return false;
        }
        
        static::$timestamps[$sessionId] = time();
        
        return true;
    }
    
    /**
     * Get all active sessions
     * 
     * @return array Array of session IDs
     */
    public function getAllSessions(): array
    {
        return array_keys(static::$sessions);
    }
    
    /**
     * Count active sessions
     * 
     * @return int Number of active sessions
     */
    public function countSessions(): int
    {
        return count(static::$sessions);
    }
    
    /**
     * Clear all sessions
     * 
     * @return bool Success status
     */
    public function clear(): bool
    {
        static::$sessions = [];
        static::$timestamps = [];
        
        return true;
    }
    
    /**
     * Evict the oldest session to make room for new ones
     * 
     * @return void
     */
    protected function evictOldestSession(): void
    {
        if (empty(static::$timestamps)) {
            return;
        }
        
        $oldestSessionId = array_keys(static::$timestamps, min(static::$timestamps))[0];
        $this->destroy($oldestSessionId);
    }
    
    /**
     * Get store statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        $totalSize = 0;
        $oldestSession = null;
        $newestSession = null;
        
        foreach (static::$sessions as $sessionId => $data) {
            $totalSize += strlen(serialize($data));
        }
        
        if (!empty(static::$timestamps)) {
            $oldestSession = min(static::$timestamps);
            $newestSession = max(static::$timestamps);
        }
        
        return [
            'store_type' => 'array',
            'total_sessions' => count(static::$sessions),
            'max_sessions' => $this->maxSessions,
            'memory_usage' => $totalSize,
            'oldest_session' => $oldestSession,
            'newest_session' => $newestSession,
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
            'sessions' => static::$sessions,
            'timestamps' => static::$timestamps,
        ];
    }
}