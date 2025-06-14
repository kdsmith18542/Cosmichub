<?php

namespace App\Core\Session\Contracts;

/**
 * Session Store Interface
 * 
 * Defines the contract for session storage implementations.
 */
interface SessionStoreInterface
{
    /**
     * Read session data
     * 
     * @param string $sessionId Session ID
     * @return array|null Session data or null if not found
     */
    public function read(string $sessionId): ?array;
    
    /**
     * Write session data
     * 
     * @param string $sessionId Session ID
     * @param array $data Session data
     * @return bool Success status
     */
    public function write(string $sessionId, array $data): bool;
    
    /**
     * Destroy session
     * 
     * @param string $sessionId Session ID
     * @return bool Success status
     */
    public function destroy(string $sessionId): bool;
    
    /**
     * Garbage collection
     * 
     * @param int $maxLifetime Maximum session lifetime in seconds
     * @return bool Success status
     */
    public function gc(int $maxLifetime): bool;
    
    /**
     * Check if session exists
     * 
     * @param string $sessionId Session ID
     * @return bool
     */
    public function exists(string $sessionId): bool;
    
    /**
     * Get session timestamp
     * 
     * @param string $sessionId Session ID
     * @return int|null Timestamp or null if not found
     */
    public function getTimestamp(string $sessionId): ?int;
    
    /**
     * Update session timestamp
     * 
     * @param string $sessionId Session ID
     * @return bool Success status
     */
    public function touch(string $sessionId): bool;
    
    /**
     * Get all active sessions
     * 
     * @return array Array of session IDs
     */
    public function getAllSessions(): array;
    
    /**
     * Count active sessions
     * 
     * @return int Number of active sessions
     */
    public function countSessions(): int;
    
    /**
     * Clear all sessions
     * 
     * @return bool Success status
     */
    public function clear(): bool;
}