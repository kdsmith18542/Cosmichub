<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\Notification;
use DateTime;

/**
 * Notification Repository for handling notification data operations
 */
class NotificationRepository extends Repository
{
    /**
     * @var string The model class
     */
    protected $model = Notification::class;

    /**
     * Find notifications by user ID.
     *
     * @param int $userId The user ID.
     * @return Notification[] An array of Notification objects.
     */
    public function findByUserId(int $userId): array
    {
        $results = $this->newQuery()->where('user_id', $userId)->orderBy('created_at', 'DESC')->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get unread notifications for user
     *
     * @param int $userId The user ID
     * @return Notification[]
     */
    public function getUnread(int $userId): array
    {
        $results = $this->newQuery()
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('created_at', 'DESC')
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get read notifications for user
     *
     * @param int $userId The user ID
     * @return Notification[]
     */
    public function getRead(int $userId): array
    {
        $results = $this->newQuery()
            ->where('user_id', $userId)
            ->where('is_read', 1)
            ->orderBy('created_at', 'DESC')
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get recent notifications for user
     *
     * @param int $userId The user ID
     * @param int $limit Number of notifications to retrieve
     * @return Notification[]
     */
    public function getRecent(int $userId, int $limit = 10): array
    {
        $results = $this->newQuery()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get notifications by type
     *
     * @param int $userId The user ID
     * @param string $type The notification type
     * @return Notification[]
     */
    public function getByType(int $userId, string $type): array
    {
        $results = $this->newQuery()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->orderBy('created_at', 'DESC')
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Count unread notifications for user
     *
     * @param int $userId The user ID
     * @return int
     */
    public function countUnread(int $userId): int
    {
        return $this->newQuery()
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }

    /**
     * Mark notification as read
     *
     * @param int $id The notification ID
     * @return bool
     */
    public function markAsRead(int $id): bool
    {
        $result = $this->newQuery()->where((new $this->model)->getKeyName(), $id)->update([
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);
        return (bool) $result; // update returns number of affected rows or false
    }

    /**
     * Mark all notifications as read for user
     *
     * @param int $userId The user ID
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        $result = $this->newQuery()
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update([
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);
        return (bool) $result; // update returns number of affected rows or false
    }

    /**
     * Mark notification as unread
     *
     * @param int $id The notification ID
     * @return bool
     */
    public function markAsUnread(int $id): bool
    {
        $result = $this->newQuery()->where((new $this->model)->getKeyName(), $id)->update([
            'is_read' => 0,
            'read_at' => null
        ]);
        return (bool) $result; // update returns number of affected rows or false
    }

    /**
     * Create notification for user
     *
     * @param int $userId The user ID
     * @param int $userId The user ID.
     * @param string $type The notification type.
     * @param string $message The notification message.
     * @param string|null $url Optional URL associated with the notification.
     * @param array $additionalData Optional additional data (ensure model's $fillable and $casts are set up if this is to be stored).
     * @return Notification|null The created Notification object or null on failure.
     */
    public function createNotification(int $userId, string $type, string $message, ?string $url = null, array $additionalData = []): ?Notification
    {
        $attributes = [
            'user_id' => $userId,
            'type' => $type,
            'message' => $message, // Model's fillable has 'message'
            'url' => $url,         // Model's fillable has 'url'
            'is_read' => 0,
            // 'title' was in the original method but not in model's fillable. Assuming 'message' covers this or 'title' needs to be added to model.
            // 'data' was json_encoded, if it needs to be stored, model's $fillable and $casts should handle it.
        ];

        if (!empty($additionalData)) {
            // If you intend to store generic data, ensure the 'data' attribute (or similar) 
            // is in the Notification model's $fillable array and $casts (e.g., 'data' => 'array').
            // For now, this example assumes 'data' is a fillable attribute.
            // $attributes['data'] = $additionalData; 
        }

        return parent::create($attributes);
    }

    /**
     * Create bulk notifications
     *
     * @param array $userIds Array of user IDs
     * @param string $type The notification type
     * @param string $title The notification title
     * @param string $message The notification message.
     * @param string|null $url Optional URL associated with the notifications.
     * @param array $additionalData Optional additional data for each notification (ensure model handles this).
     * @return bool True on success, false on failure.
     */
    public function createBulkNotifications(array $userIds, string $type, string $message, ?string $url = null, array $additionalData = []): bool
    {
        $notificationsData = [];
        $now = date('Y-m-d H:i:s');

        foreach ($userIds as $userId) {
            $notificationEntry = [
                'user_id' => $userId,
                'type' => $type,
                'message' => $message,
                'url' => $url,
                'is_read' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            if (!empty($additionalData)) {
                $notificationEntry['data'] = json_encode($additionalData);
            }
            
            $notificationsData[] = $notificationEntry;
        }

        if (empty($notificationsData)) {
            return true;
        }

        return $this->newQuery()->insert($notificationsData);
    }

    /**
     * Get notification statistics
     *
     * @param int|null $userId Optional user ID filter
     * @return array{
     *   total: int,
     *   unread: int,
     *   read: int,
     *   types: array<object{type: string, count: int}>
     * }
     */
    public function getStatistics(?int $userId = null): array
    {
        $baseQuery = $this->newQuery();
        if ($userId) {
            $baseQuery = $baseQuery->where('user_id', $userId);
        }

        $total = (clone $baseQuery)->count();
        $unread = (clone $baseQuery)->where('is_read', 0)->count();
        $read = (clone $baseQuery)->where('is_read', 1)->count();

        $typeQuery = $this->newQuery()
            ->select('type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('type');
            
        if ($userId) {
            $typeQuery = $typeQuery->where('user_id', $userId);
        }
        
        $typeStatsResults = $typeQuery->get();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $read,
            'types' => $typeStatsResults
        ];
    }

    /**
     * Get notifications sent over time
     *
     * @param string $period Period for statistics (day, week, month)
     * @param int $limit Number of periods to retrieve
     * @return array<object{period: string, total: int, read_count: int, unread_count: int}>
     */
    public function getNotificationsOverTime(string $period = 'day', int $limit = 30): array
    {
        $dateFormat = match($period) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $results = $this->newQuery()
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count')
            ->selectRaw('SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
            ->groupBy('period')
            ->orderBy('period', 'DESC')
            ->limit($limit)
            ->get();
            
        return $results;
    }

    /**
     * Search notifications
     *
     * @param int $userId The user ID
     * @param string $search The search term
     * @return Notification[]
     */
    public function search(int $userId, string $search): array
    {
        $results = $this->newQuery()
            ->where('user_id', $userId)
            ->where(function($query) use ($search) {
                $query->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('message', 'LIKE', "%{$search}%")
                      ->orWhere('type', 'LIKE', "%{$search}%");
            })
            ->orderBy('created_at', 'DESC')
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get notifications with pagination
     *
     * @param int $userId The user ID
     * @param int $page The page number
     * @param int $perPage Items per page
     * @param string|null $type Optional type filter
     * @param bool $unreadOnly Show only unread notifications
     * @return array{
     *  items: Notification[],
     *  total: int,
     *  page: int,
     *  per_page: int,
     *  total_pages: float
     * }
     */
    public function paginate(int $userId, int $page = 1, int $perPage = 10, ?string $type = null, bool $unreadOnly = false): array
    {
        $offset = ($page - 1) * $perPage;

        $query = $this->newQuery()->where('user_id', $userId);

        if ($type) {
            $query = $query->where('type', $type);
        }

        if ($unreadOnly) {
            $query = $query->where('is_read', 0);
        }

        $totalQuery = clone $query;
        $total = $totalQuery->count();

        $itemResults = $query
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();
        
        $items = array_map(fn($data) => new $this->model((array)$data), $itemResults);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Delete old notifications
     *
     * @param int $daysOld Number of days old to delete
     * @param bool $readOnly Delete only read notifications
     * @return int Number of deleted records
     */
    public function deleteOld(int $daysOld = 30, bool $readOnly = true): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        $query = $this->newQuery()->where('created_at', '<', $cutoffDate);

        if ($readOnly) {
            $query = $query->where('is_read', 1);
        }

        return $query->delete(); // delete returns number of affected rows
    }

    /**
     * Get notification preferences for user
     *
     * @param int $userId The user ID
     * @return array<string, mixed>
     */
    public function getUserPreferences(int $userId): array
    {
        // This would typically come from a user_notification_preferences table or a dedicated service.
        // For now, return default preferences. This method does not interact with the 'notifications' table directly.
        // If it were to fetch preferences from a DB table, newQuery() would be used for that table's repository.
        return [
            'email' => true,
            'push' => true,
            'sms' => false,
            'types' => [
                'report_ready' => true,
                'subscription_expiry' => true,
                'daily_vibe' => true,
                'system_update' => false
            ]
        ];
    }
}