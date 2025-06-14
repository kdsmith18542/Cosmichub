<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\Gift;
use PDO;

/**
 * Gift Repository for handling gift data operations
 */
class GiftRepository extends Repository
{
    protected string $model = Gift::class;
    protected string $table = 'gifts';

    /**
     * Find gift by gift code
     *
     * @param string $giftCode
     * @return Gift|null
     */
    public function findByCode(string $giftCode): ?Gift
    {
        return $this->newQuery()->where('gift_code', $giftCode)->first();
    }

    /**
     * Get gifts by sender user ID
     *
     * @param int $userId
     * @return array
     */
    public function getBySender(int $userId): array
    {
        $results = $this->newQuery()
            ->where('sender_user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->get();
        
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get pending gifts (not yet redeemed)
     *
     * @return array
     */
    public function getPendingGifts(): array
    {
        $results = $this->newQuery()
            ->where('status', 'pending')
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->get();
        
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get redeemed gifts
     *
     * @return array
     */
    public function getRedeemedGifts(): array
    {
        $results = $this->newQuery()->where('status', 'redeemed')->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get expired gifts
     *
     * @return array
     */
    public function getExpiredGifts(): array
    {
        $sql = "SELECT * FROM gifts 
                WHERE status = 'pending' 
                AND expires_at <= :now 
                ORDER BY expires_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':now' => date('Y-m-d H:i:s')]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($data) => new $this->model($data), $results);
    }

    /**
     * Mark expired gifts as expired
     *
     * @return bool
     */
    public function markExpiredGifts(): bool
    {
        $sql = "UPDATE gifts 
                SET status = 'expired', updated_at = :updated_at 
                WHERE status = 'pending' 
                AND expires_at <= :now";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':updated_at' => date('Y-m-d H:i:s'),
            ':now' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get gift statistics for a user
     *
     * @param int $userId
     * @return array
     */
    public function getUserGiftStats(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN status = 'redeemed' THEN 1 ELSE 0 END) as redeemed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                    SUM(credits_amount) as total_credits_gifted,
                    SUM(purchase_amount) as total_amount_spent
                FROM gifts 
                WHERE sender_user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get recent gift activity
     *
     * @param int $limit
     * @return array
     */
    public function getRecentActivity(int $limit = 10): array
    {
        $sql = "SELECT g.*, u.name as sender_full_name 
                FROM gifts g 
                LEFT JOIN users u ON g.sender_user_id = u.id 
                ORDER BY g.created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($data) => new $this->model($data), $results);
    }

    /**
     * Check if gift code is valid and available
     *
     * @param string $giftCode
     * @return array
     */
    public function isValidGiftCode(string $giftCode): array
    {
        $gift = $this->findByCode($giftCode);
        
        if (!$gift) {
            return ['valid' => false, 'reason' => 'Gift code not found'];
        }
        
        if ($gift->status === 'redeemed') {
            return ['valid' => false, 'reason' => 'Gift already redeemed'];
        }
        
        if ($gift->status === 'expired' || strtotime($gift->expires_at) < time()) {
            return ['valid' => false, 'reason' => 'Gift has expired'];
        }
        
        return ['valid' => true, 'gift' => $gift];
    }

    /**
     * Get total revenue from gifts
     *
     * @return float
     */
    public function getTotalRevenue(): float
    {
        $sql = "SELECT SUM(purchase_amount) as total_revenue FROM gifts";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total_revenue'] ?? 0);
    }

    /**
     * Get gift conversion rate (redeemed vs sent)
     *
     * @return float
     */
    public function getConversionRate(): float
    {
        $sql = "SELECT 
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN status = 'redeemed' THEN 1 ELSE 0 END) as redeemed
                FROM gifts";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total_sent'] > 0) {
            return ($result['redeemed'] / $result['total_sent']) * 100;
        }
        
        return 0;
    }

    /**
     * Clean up old expired gifts (optional - for maintenance)
     *
     * @param int $daysOld
     * @return bool
     */
    public function cleanupOldExpiredGifts(int $daysOld = 90): bool
    {
        $sql = "DELETE FROM gifts 
                WHERE status = 'expired' 
                AND expires_at < :cutoff_date";
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':cutoff_date' => $cutoffDate]);
    }
}