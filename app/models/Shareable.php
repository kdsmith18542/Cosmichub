<?php

namespace App\Models;

use App\Libraries\Database;
use PDO;

class Shareable
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new shareable
     */
    public function create($data)
    {
        $sql = "INSERT INTO shareables (user_id, type, title, description, data, animation_config, share_url, is_public, expires_at) 
                VALUES (:user_id, :type, :title, :description, :data, :animation_config, :share_url, :is_public, :expires_at)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':user_id' => $data['user_id'] ?? null,
            ':type' => $data['type'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':data' => json_encode($data['data']),
            ':animation_config' => json_encode($data['animation_config']),
            ':share_url' => $data['share_url'],
            ':is_public' => $data['is_public'] ?? true,
            ':expires_at' => $data['expires_at'] ?? null
        ]);
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get shareable by ID
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM shareables WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $shareable = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shareable) {
            $shareable['data'] = json_decode($shareable['data'], true);
            $shareable['animation_config'] = json_decode($shareable['animation_config'], true);
        }
        
        return $shareable;
    }
    
    /**
     * Get shareable by share URL
     */
    public function findByShareUrl($shareUrl)
    {
        $sql = "SELECT * FROM shareables WHERE share_url = :share_url";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':share_url' => $shareUrl]);
        
        $shareable = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shareable) {
            $shareable['data'] = json_decode($shareable['data'], true);
            $shareable['animation_config'] = json_decode($shareable['animation_config'], true);
        }
        
        return $shareable;
    }
    
    /**
     * Get shareables by user ID
     */
    public function findByUserId($userId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT * FROM shareables WHERE user_id = :user_id 
                ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $shareables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($shareables as &$shareable) {
            $shareable['data'] = json_decode($shareable['data'], true);
            $shareable['animation_config'] = json_decode($shareable['animation_config'], true);
        }
        
        return $shareables;
    }
    
    /**
     * Get public shareables by type
     */
    public function findByType($type, $limit = 20, $offset = 0)
    {
        $sql = "SELECT * FROM shareables WHERE type = :type AND is_public = 1 
                AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $shareables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($shareables as &$shareable) {
            $shareable['data'] = json_decode($shareable['data'], true);
            $shareable['animation_config'] = json_decode($shareable['animation_config'], true);
        }
        
        return $shareables;
    }
    
    /**
     * Increment view count
     */
    public function incrementViewCount($id)
    {
        $sql = "UPDATE shareables SET view_count = view_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloadCount($id)
    {
        $sql = "UPDATE shareables SET download_count = download_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Update shareable
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'description', 'is_public', 'expires_at'])) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            } elseif (in_array($key, ['data', 'animation_config'])) {
                $fields[] = "$key = :$key";
                $params[":$key"] = json_encode($value);
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE shareables SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete shareable
     */
    public function delete($id)
    {
        $sql = "DELETE FROM shareables WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Clean up expired shareables
     */
    public function cleanupExpired()
    {
        $sql = "DELETE FROM shareables WHERE expires_at IS NOT NULL AND expires_at < NOW()";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }
    
    /**
     * Get popular shareables
     */
    public function getPopular($limit = 10, $days = 7)
    {
        $sql = "SELECT * FROM shareables 
                WHERE is_public = 1 
                AND (expires_at IS NULL OR expires_at > NOW())
                AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                ORDER BY (view_count + download_count * 2) DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $shareables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($shareables as &$shareable) {
            $shareable['data'] = json_decode($shareable['data'], true);
            $shareable['animation_config'] = json_decode($shareable['animation_config'], true);
        }
        
        return $shareables;
    }
    
    /**
     * Get statistics for a user
     */
    public function getUserStats($userId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_shareables,
                    SUM(view_count) as total_views,
                    SUM(download_count) as total_downloads,
                    AVG(view_count) as avg_views_per_shareable
                FROM shareables 
                WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate unique share URL
     */
    public function generateUniqueShareUrl($type, $baseId = null)
    {
        do {
            $hash = substr(md5(uniqid() . time() . ($baseId ?? '')), 0, 12);
            $shareUrl = "/shareables/{$hash}";
            
            // Check if URL already exists
            $existing = $this->findByShareUrl($shareUrl);
        } while ($existing);
        
        return $shareUrl;
    }
}