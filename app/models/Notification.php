<?php

namespace App\Models;

class Notification extends Model
{
    protected $table = 'notifications';
    
    // Define the properties that can be set
    protected $fillable = ['user_id', 'type', 'message', 'url', 'is_read'];
    
    /**
     * Create a new notification for a user
     * 
     * @param int $userId The user ID
     * @param string $type Notification type
     * @param string $message Notification message
     * @param string|null $url Optional URL for the notification
     * @return int|bool The new notification ID or false on failure
     */
    public function createNotification($userId, $type, $message, $url = null)
    {
        try {
            $data = [
                'user_id' => $userId,
                'type' => $type,
                'message' => $message,
                'url' => $url,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->query(
                "INSERT INTO {$this->table} (user_id, type, message, url, is_read, created_at) 
                VALUES (:user_id, :type, :message, :url, :is_read, :created_at)",
                $data
            );
            
            if ($result) {
                return $this->getDb()->lastInsertId();
            }
            
            return false;
        } catch (\Exception $e) {
            error_log('Error creating notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications($userId, $limit = 5)
    {
        try {
            return $this->query(
                "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND is_read = 0 
                ORDER BY created_at DESC 
                LIMIT :limit",
                ['user_id' => $userId, 'limit' => (int)$limit],
                \PDO::FETCH_OBJ
            );
        } catch (\Exception $e) {
            error_log('Error getting notifications: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark a notification as read
     */
    public function markAsRead($notificationId, $userId)
    {
        try {
            return $this->query(
                "UPDATE {$this->table} SET is_read = 1 
                WHERE id = :id AND user_id = :user_id",
                ['id' => $notificationId, 'user_id' => $userId]
            );
        } catch (\Exception $e) {
            error_log('Error marking notification as read: ' . $e->getMessage());
            return false;
        }
    }
}
