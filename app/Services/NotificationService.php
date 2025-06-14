<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;

/**
 * Notification Service for handling notification business logic
 */
class NotificationService extends Service
{
    /**
     * @var NotificationRepository
     */
    protected $notificationRepository;
    
    /**
     * @var UserRepository
     */
    protected $userRepository;
    
    /**
     * Initialize the service
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->notificationRepository = $this->getRepository('NotificationRepository');
        $this->userRepository = $this->getRepository('UserRepository');
    }
    
    /**
     * Get notifications for a user
     * 
     * @param int $userId User ID
     * @param int $limit Number of notifications to retrieve
     * @return array
     */
    public function getUserNotifications($userId, $limit = 10)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $notifications = $this->notificationRepository->findByUserId($userId, $limit);
            return $this->success('Notifications retrieved successfully', $notifications);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving user notifications: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while retrieving notifications');
        }
    }
    
    /**
     * Get unread notifications for a user
     * 
     * @param int $userId User ID
     * @param int $limit Number of notifications to retrieve
     * @return array
     */
    public function getUnreadNotifications($userId, $limit = 10)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $notifications = $this->notificationRepository->getUnread($userId, $limit);
            return $this->success('Unread notifications retrieved successfully', $notifications);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving unread notifications: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while retrieving unread notifications');
        }
    }
    
    /**
     * Get read notifications for a user
     * 
     * @param int $userId User ID
     * @param int $limit Number of notifications to retrieve
     * @return array
     */
    public function getReadNotifications($userId, $limit = 10)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $notifications = $this->notificationRepository->getRead($userId, $limit);
            return $this->success('Read notifications retrieved successfully', $notifications);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving read notifications: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while retrieving read notifications');
        }
    }
    
    /**
     * Get recent notifications for a user
     * 
     * @param int $userId User ID
     * @param int $limit Number of notifications to retrieve
     * @return array
     */
    public function getRecentNotifications($userId, $limit = 10)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $notifications = $this->notificationRepository->getRecent($userId, $limit);
            return $this->success('Recent notifications retrieved successfully', $notifications);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving recent notifications: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while retrieving recent notifications');
        }
    }
    
    /**
     * Get notifications by type for a user
     * 
     * @param int $userId User ID
     * @param string $type Notification type
     * @param int $limit Number of notifications to retrieve
     * @return array
     */
    public function getNotificationsByType($userId, $type, $limit = 10)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            if (empty($type)) {
                return $this->error('Notification type is required');
            }
            
            $notifications = $this->notificationRepository->getByType($userId, $type, $limit);
            return $this->success('Notifications retrieved successfully', $notifications);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving notifications by type: ' . $e->getMessage(), [
                'user_id' => $userId,
                'type' => $type
            ]);
            return $this->error('An error occurred while retrieving notifications');
        }
    }
    
    /**
     * Count unread notifications for a user
     * 
     * @param int $userId User ID
     * @return array
     */
    public function countUnreadNotifications($userId)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $count = $this->notificationRepository->countUnread($userId);
            return $this->success('Unread notifications counted successfully', ['count' => $count]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error counting unread notifications: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while counting unread notifications');
        }
    }
    
    /**
     * Mark notification as read
     * 
     * @param int $notificationId Notification ID
     * @param int $userId User ID (for verification)
     * @return array
     */
    public function markAsRead($notificationId, $userId)
    {
        try {
            // Check if notification exists and belongs to the user
            $notification = $this->notificationRepository->find($notificationId);
            
            if (!$notification) {
                return $this->error('Notification not found');
            }
            
            if ($notification['user_id'] != $userId) {
                return $this->error('Notification does not belong to this user');
            }
            
            $updated = $this->notificationRepository->markAsRead($notificationId);
            
            if ($updated) {
                return $this->success('Notification marked as read successfully');
            }
            
            return $this->error('Failed to mark notification as read');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error marking notification as read: ' . $e->getMessage(), [
                'notification_id' => $notificationId,
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while marking notification as read');
        }
    }
    
    /**
     * Mark all notifications as read for a user
     * 
     * @param int $userId User ID
     * @return array
     */
    public function markAllAsRead($userId)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $updated = $this->notificationRepository->markAllAsRead($userId);
            
            return $this->success('All notifications marked as read successfully', [
                'updated_count' => $updated
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error marking all notifications as read: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while marking all notifications as read');
        }
    }
    
    /**
     * Mark notification as unread
     * 
     * @param int $notificationId Notification ID
     * @param int $userId User ID (for verification)
     * @return array
     */
    public function markAsUnread($notificationId, $userId)
    {
        try {
            // Check if notification exists and belongs to the user
            $notification = $this->notificationRepository->find($notificationId);
            
            if (!$notification) {
                return $this->error('Notification not found');
            }
            
            if ($notification['user_id'] != $userId) {
                return $this->error('Notification does not belong to this user');
            }
            
            $updated = $this->notificationRepository->markAsUnread($notificationId);
            
            if ($updated) {
                return $this->success('Notification marked as unread successfully');
            }
            
            return $this->error('Failed to mark notification as unread');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error marking notification as unread: ' . $e->getMessage(), [
                'notification_id' => $notificationId,
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while marking notification as unread');
        }
    }
    
    /**
     * Create a notification
     * 
     * @param array $data Notification data
     * @return array
     */
    public function createNotification($data)
    {
        try {
            // Validate required fields
            $validation = $this->validateNotificationData($data);
            if (!empty($validation)) {
                return $this->error('Validation failed', $validation);
            }
            
            // Check if user exists
            $user = $this->userRepository->find($data['user_id']);
            if (!$user) {
                return $this->error('User not found');
            }
            
            // Check user notification preferences if applicable
            if (!$this->checkNotificationPreferences($user, $data['type'])) {
                return $this->success('Notification skipped based on user preferences');
            }
            
            // Set default values
            $data['read'] = $data['read'] ?? 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            
            $notification = $this->notificationRepository->create($data);
            
            if ($notification) {
                $this->log('info', 'Notification created successfully', [
                    'user_id' => $data['user_id'],
                    'type' => $data['type']
                ]);
                return $this->success('Notification created successfully', $notification);
            }
            
            return $this->error('Failed to create notification');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error creating notification: ' . $e->getMessage());
            return $this->error('An error occurred while creating notification');
        }
    }
    
    /**
     * Create bulk notifications
     * 
     * @param array $userIds Array of user IDs
     * @param array $data Notification data (without user_id)
     * @return array
     */
    public function createBulkNotifications($userIds, $data)
    {
        try {
            if (empty($userIds)) {
                return $this->error('User IDs are required');
            }
            
            // Validate notification data (except user_id)
            if (empty($data['title'])) {
                return $this->error('Notification title is required');
            }
            
            if (empty($data['message'])) {
                return $this->error('Notification message is required');
            }
            
            if (empty($data['type'])) {
                return $this->error('Notification type is required');
            }
            
            // Set default values
            $data['read'] = $data['read'] ?? 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            
            $createdCount = 0;
            $skippedCount = 0;
            
            foreach ($userIds as $userId) {
                // Check if user exists
                $user = $this->userRepository->find($userId);
                if (!$user) {
                    $skippedCount++;
                    continue;
                }
                
                // Check user notification preferences
                if (!$this->checkNotificationPreferences($user, $data['type'])) {
                    $skippedCount++;
                    continue;
                }
                
                // Create notification for this user
                $notificationData = array_merge($data, ['user_id' => $userId]);
                $notification = $this->notificationRepository->create($notificationData);
                
                if ($notification) {
                    $createdCount++;
                } else {
                    $skippedCount++;
                }
            }
            
            $this->log('info', 'Bulk notifications created', [
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'type' => $data['type']
            ]);
            
            return $this->success('Bulk notifications created successfully', [
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error creating bulk notifications: ' . $e->getMessage());
            return $this->error('An error occurred while creating bulk notifications');
        }
    }
    
    /**
     * Delete a notification
     * 
     * @param int $notificationId Notification ID
     * @param int $userId User ID (for verification)
     * @return array
     */
    public function deleteNotification($notificationId, $userId)
    {
        try {
            // Check if notification exists and belongs to the user
            $notification = $this->notificationRepository->find($notificationId);
            
            if (!$notification) {
                return $this->error('Notification not found');
            }
            
            if ($notification['user_id'] != $userId) {
                return $this->error('Notification does not belong to this user');
            }
            
            $deleted = $this->notificationRepository->delete($notificationId);
            
            if ($deleted) {
                return $this->success('Notification deleted successfully');
            }
            
            return $this->error('Failed to delete notification');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error deleting notification: ' . $e->getMessage(), [
                'notification_id' => $notificationId,
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while deleting notification');
        }
    }
    
    /**
     * Delete all notifications for a user
     * 
     * @param int $userId User ID
     * @return array
     */
    public function deleteAllNotifications($userId)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $deleted = $this->notificationRepository->deleteByUserId($userId);
            
            return $this->success('All notifications deleted successfully', [
                'deleted_count' => $deleted
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error deleting all notifications: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while deleting all notifications');
        }
    }
    
    /**
     * Delete old notifications
     * 
     * @param int $days Number of days to keep
     * @return array
     */
    public function deleteOldNotifications($days = 90)
    {
        try {
            $deleted = $this->notificationRepository->deleteOldNotifications($days);
            
            return $this->success('Old notifications deleted successfully', [
                'deleted_count' => $deleted
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error deleting old notifications: ' . $e->getMessage());
            return $this->error('An error occurred while deleting old notifications');
        }
    }
    
    /**
     * Get notification statistics
     * 
     * @return array
     */
    public function getNotificationStatistics()
    {
        try {
            $stats = $this->notificationRepository->getStatistics();
            return $this->success('Notification statistics retrieved successfully', $stats);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving notification statistics: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving notification statistics');
        }
    }
    
    /**
     * Get notification trends over time
     * 
     * @param int $days Number of days to analyze
     * @return array
     */
    public function getNotificationTrends($days = 30)
    {
        try {
            $trends = $this->notificationRepository->getTrendsOverTime($days);
            return $this->success('Notification trends retrieved successfully', $trends);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving notification trends: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving notification trends');
        }
    }
    
    /**
     * Search notifications
     * 
     * @param string $search Search term
     * @param int $userId User ID (optional, to limit search to a specific user)
     * @return array
     */
    public function searchNotifications($search, $userId = null)
    {
        try {
            if (empty($search)) {
                return $this->error('Search term is required');
            }
            
            $notifications = $this->notificationRepository->search($search, $userId);
            return $this->success('Search completed successfully', $notifications);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error searching notifications: ' . $e->getMessage(), [
                'search' => $search,
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while searching notifications');
        }
    }
    
    /**
     * Get paginated notifications
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param int $userId User ID (optional, to limit to a specific user)
     * @return array
     */
    public function getPaginatedNotifications($page = 1, $perPage = 10, $userId = null)
    {
        try {
            $result = $this->notificationRepository->paginate($page, $perPage, $userId);
            return $this->success('Notifications retrieved successfully', $result);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving paginated notifications: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving notifications');
        }
    }
    
    /**
     * Get user notification preferences
     * 
     * @param int $userId User ID
     * @return array
     */
    public function getUserNotificationPreferences($userId)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $preferences = $this->notificationRepository->getUserPreferences($userId);
            return $this->success('Notification preferences retrieved successfully', $preferences);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving user notification preferences: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while retrieving notification preferences');
        }
    }
    
    /**
     * Update user notification preferences
     * 
     * @param int $userId User ID
     * @param array $preferences Notification preferences
     * @return array
     */
    public function updateUserNotificationPreferences($userId, $preferences)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            if (empty($preferences) || !is_array($preferences)) {
                return $this->error('Invalid notification preferences');
            }
            
            $updated = $this->notificationRepository->updateUserPreferences($userId, $preferences);
            
            if ($updated) {
                return $this->success('Notification preferences updated successfully');
            }
            
            return $this->error('Failed to update notification preferences');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error updating user notification preferences: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while updating notification preferences');
        }
    }
    
    /**
     * Check if a notification should be sent based on user preferences
     * 
     * @param array $user User data
     * @param string $notificationType Notification type
     * @return bool
     */
    protected function checkNotificationPreferences($user, $notificationType)
    {
        // Get user preferences
        $preferences = $this->notificationRepository->getUserPreferences($user['id']);
        
        // If no preferences are set, default to sending all notifications
        if (empty($preferences)) {
            return true;
        }
        
        // Check if this notification type is enabled
        if (isset($preferences[$notificationType]) && $preferences[$notificationType] === false) {
            return false;
        }
        
        // Check if all notifications are disabled
        if (isset($preferences['all']) && $preferences['all'] === false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate notification data
     * 
     * @param array $data Notification data
     * @return array
     */
    protected function validateNotificationData($data)
    {
        $errors = [];
        
        if (empty($data['user_id'])) {
            $errors[] = 'User ID is required';
        }
        
        if (empty($data['title'])) {
            $errors[] = 'Notification title is required';
        }
        
        if (empty($data['message'])) {
            $errors[] = 'Notification message is required';
        }
        
        if (empty($data['type'])) {
            $errors[] = 'Notification type is required';
        }
        
        return $errors;
    }
}