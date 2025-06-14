<?php

namespace App\Models;

use App\Core\Database\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shareable extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'data',
        'animation_config',
        'share_url',
        'is_public',
        'expires_at',
        'view_count',
        'download_count',
    ];

    protected $casts = [
        'data' => 'array',
        'animation_config' => 'array',
        'is_public' => 'boolean',
        'expires_at' => 'datetime',
        'view_count' => 'integer',
        'download_count' => 'integer',
    ];

    /**
     * Get the user that owns the shareable.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new shareable.
     *
     * @param array $data
     * @return static|null
     */
    public static function createShareable(array $data): ?static
    {
        return static::create($data);
    }

    /**
     * Get shareable by ID.
     *
     * @param int $id
     * @return static|null
     */
    public static function findShareableById(int $id): ?static
    {
        return static::find($id);
    }

    /**
     * Get shareable by share URL.
     *
     * @param string $shareUrl
     * @return static|null
     */
    public static function findByShareUrl(string $shareUrl): ?static
    {
        return static::where('share_url', $shareUrl)->first();
    }

    /**
     * Get shareables by user ID.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function findByUserId(int $userId, int $limit = 20, int $offset = 0): array
    {
        return static::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    /**
     * Get public shareables by type.
     *
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function findByType(string $type, int $limit = 20, int $offset = 0): array
    {
        return static::query()
            ->where('type', $type)
            ->where('is_public', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    /**
     * Increment view count.
     *
     * @param int $id
     * @return bool
     */
    public static function incrementViewCount(int $id): bool
    {
        return static::where('id', $id)->increment('view_count');
    }

    /**
     * Increment download count.
     *
     * @param int $id
     * @return bool
     */
    public static function incrementDownloadCount(int $id): bool
    {
        return static::where('id', $id)->increment('download_count');
    }

    /**
     * Update shareable.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateShareable(int $id, array $data): bool
    {
        $shareable = static::find($id);
        if ($shareable) {
            return $shareable->update($data);
        }
        return false;
    }

    /**
     * Delete shareable.
     *
     * @param int $id
     * @return bool|null
     */
    public static function deleteShareable(int $id): ?bool
    {
        return static::destroy($id);
    }

    /**
     * Clean up expired shareables.
     *
     * @return int Number of rows affected.
     */
    public static function cleanupExpired(): int
    {
        return static::whereNotNull('expires_at')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();
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