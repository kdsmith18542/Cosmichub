<?php

namespace App\Models;

use Psr\Log\LoggerInterface;

class Notification extends \App\Core\Database\Model
{
    // Table name will be inferred as 'notifications'
    // Timestamps (created_at, updated_at) are handled by the base Model by default

    protected $fillable = ['user_id', 'type', 'message', 'url', 'is_read'];

    /**
     * @var LoggerInterface
     */
    protected static $logger;

    /**
     * Set the logger instance.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public static function setLogger(LoggerInterface $logger): void
    {
        static::$logger = $logger;
    }

    /**
     * Create a new notification for a user.
     *
     * @param int $userId The user ID.
     * @param string $type Notification type.
     * @param string $message Notification message.
     * @param string|null $url Optional URL for the notification.
     * @return static|null The created Notification instance or null on failure.
     */
    public static function createNotification(int $userId, string $type, string $message, ?string $url = null): ?static
    {
        try {
            return static::create([
                'user_id' => $userId,
                'type' => $type,
                'message' => $message,
                'url' => $url,
                'is_read' => 0, // Default to unread
            ]);
        } catch (\Exception $e) {
            if (static::$logger) {
                static::$logger->error('Error creating notification: ' . $e->getMessage());
            }
            return null;
        }
    }

    // The getUnreadNotifications method has been removed as its functionality
    // is covered by NotificationRepository::getUnread()

    /**
     * Mark a notification as read.
     *
     * @param int $notificationId The ID of the notification to mark as read.
     * @param int $userId The ID of the user who owns the notification.
     * @return bool True if the update was successful, false otherwise.
     */
    public static function markNotificationAsRead(int $notificationId, int $userId): bool
    {
        try {
            $updatedRows = static::where('id', $notificationId)
                                 ->where('user_id', $userId)
                                 ->update(['is_read' => 1]);
            return $updatedRows > 0;
        } catch (\Exception $e) {
            // Log error
            if (static::$logger) {
                static::$logger->error('Error marking notification as read: ' . $e->getMessage());
            }
            return false;
        }
    }
}
