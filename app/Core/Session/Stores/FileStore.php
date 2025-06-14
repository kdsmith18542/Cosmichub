<?php

namespace App\Core\Session\Stores;

use App\Core\Session\Contracts\SessionStoreInterface;
use RuntimeException;

/**
 * File Session Store
 * 
 * Stores session data in files on the filesystem.
 */
class FileStore implements SessionStoreInterface
{
    /**
     * Session storage path
     * 
     * @var string
     */
    protected string $path;
    
    /**
     * File permissions
     * 
     * @var int
     */
    protected int $permissions;
    
    /**
     * File prefix
     * 
     * @var string
     */
    protected string $prefix;
    
    /**
     * Constructor
     * 
     * @param array $config Store configuration
     */
    public function __construct(array $config = [])
    {
        $this->path = $config['path'] ?? sys_get_temp_dir() . '/sessions';
        $this->permissions = $config['permissions'] ?? 0644;
        $this->prefix = $config['prefix'] ?? 'sess_';
        
        $this->ensureDirectoryExists();
    }
    
    /**
     * Ensure the session directory exists
     * 
     * @return void
     * @throws RuntimeException
     */
    protected function ensureDirectoryExists(): void
    {
        if (!is_dir($this->path)) {
            if (!mkdir($this->path, 0755, true)) {
                throw new RuntimeException("Failed to create session directory: {$this->path}");
            }
        }
        
        if (!is_writable($this->path)) {
            throw new RuntimeException("Session directory is not writable: {$this->path}");
        }
    }
    
    /**
     * Get the file path for a session
     * 
     * @param string $sessionId Session ID
     * @return string
     */
    protected function getFilePath(string $sessionId): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->prefix . $sessionId;
    }
    
    /**
     * Read session data
     * 
     * @param string $sessionId Session ID
     * @return array|null Session data or null if not found
     */
    public function read(string $sessionId): ?array
    {
        $filePath = $this->getFilePath($sessionId);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            return null;
        }
        
        $data = unserialize($content);
        
        if ($data === false) {
            return null;
        }
        
        return $data;
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
        $filePath = $this->getFilePath($sessionId);
        $serialized = serialize($data);
        
        $result = file_put_contents($filePath, $serialized, LOCK_EX);
        
        if ($result !== false) {
            chmod($filePath, $this->permissions);
            return true;
        }
        
        return false;
    }
    
    /**
     * Destroy session
     * 
     * @param string $sessionId Session ID
     * @return bool Success status
     */
    public function destroy(string $sessionId): bool
    {
        $filePath = $this->getFilePath($sessionId);
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
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
        $files = glob($this->path . DIRECTORY_SEPARATOR . $this->prefix . '*');
        
        if ($files === false) {
            return false;
        }
        
        $now = time();
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $lastModified = filemtime($file);
                
                if ($lastModified !== false && ($now - $lastModified) > $maxLifetime) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
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
        return file_exists($this->getFilePath($sessionId));
    }
    
    /**
     * Get session timestamp
     * 
     * @param string $sessionId Session ID
     * @return int|null Timestamp or null if not found
     */
    public function getTimestamp(string $sessionId): ?int
    {
        $filePath = $this->getFilePath($sessionId);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $timestamp = filemtime($filePath);
        
        return $timestamp !== false ? $timestamp : null;
    }
    
    /**
     * Update session timestamp
     * 
     * @param string $sessionId Session ID
     * @return bool Success status
     */
    public function touch(string $sessionId): bool
    {
        $filePath = $this->getFilePath($sessionId);
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        return touch($filePath);
    }
    
    /**
     * Get all active sessions
     * 
     * @return array Array of session IDs
     */
    public function getAllSessions(): array
    {
        $files = glob($this->path . DIRECTORY_SEPARATOR . $this->prefix . '*');
        
        if ($files === false) {
            return [];
        }
        
        $sessions = [];
        $prefixLength = strlen($this->prefix);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                if (strpos($filename, $this->prefix) === 0) {
                    $sessions[] = substr($filename, $prefixLength);
                }
            }
        }
        
        return $sessions;
    }
    
    /**
     * Count active sessions
     * 
     * @return int Number of active sessions
     */
    public function countSessions(): int
    {
        return count($this->getAllSessions());
    }
    
    /**
     * Clear all sessions
     * 
     * @return bool Success status
     */
    public function clear(): bool
    {
        $files = glob($this->path . DIRECTORY_SEPARATOR . $this->prefix . '*');
        
        if ($files === false) {
            return false;
        }
        
        $success = true;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if (!unlink($file)) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Get store statistics
     * 
     * @return array
     */
    public function getStats(): array
    {
        $sessions = $this->getAllSessions();
        $totalSize = 0;
        $oldestSession = null;
        $newestSession = null;
        
        foreach ($sessions as $sessionId) {
            $filePath = $this->getFilePath($sessionId);
            
            if (file_exists($filePath)) {
                $size = filesize($filePath);
                $timestamp = filemtime($filePath);
                
                if ($size !== false) {
                    $totalSize += $size;
                }
                
                if ($timestamp !== false) {
                    if ($oldestSession === null || $timestamp < $oldestSession) {
                        $oldestSession = $timestamp;
                    }
                    
                    if ($newestSession === null || $timestamp > $newestSession) {
                        $newestSession = $timestamp;
                    }
                }
            }
        }
        
        return [
            'store_type' => 'file',
            'path' => $this->path,
            'total_sessions' => count($sessions),
            'total_size' => $totalSize,
            'oldest_session' => $oldestSession,
            'newest_session' => $newestSession,
            'permissions' => decoct($this->permissions),
        ];
    }
}