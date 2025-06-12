<?php
/**
 * Gift Model
 * 
 * Handles database operations for gift reports
 */

namespace App\Models;

use App\Libraries\Database;
use PDO;

class Gift extends Model
{
    protected static $table = 'gifts';
    
    /**
     * Create a new gift record
     */
    public static function create($data)
    {
        $db = Database::getInstance();
        
        $sql = "INSERT INTO gifts (
            gift_code, sender_user_id, sender_name, recipient_email, recipient_name,
            gift_message, credits_amount, plan_id, purchase_amount, stripe_payment_intent_id,
            status, expires_at, created_at
        ) VALUES (
            :gift_code, :sender_user_id, :sender_name, :recipient_email, :recipient_name,
            :gift_message, :credits_amount, :plan_id, :purchase_amount, :stripe_payment_intent_id,
            :status, :expires_at, :created_at
        )";
        
        $stmt = $db->prepare($sql);
        
        $params = [
            ':gift_code' => $data['gift_code'],
            ':sender_user_id' => $data['sender_user_id'],
            ':sender_name' => $data['sender_name'],
            ':recipient_email' => $data['recipient_email'],
            ':recipient_name' => $data['recipient_name'],
            ':gift_message' => $data['gift_message'] ?? null,
            ':credits_amount' => $data['credits_amount'],
            ':plan_id' => $data['plan_id'],
            ':purchase_amount' => $data['purchase_amount'],
            ':stripe_payment_intent_id' => $data['stripe_payment_intent_id'],
            ':status' => $data['status'],
            ':expires_at' => $data['expires_at'],
            ':created_at' => date('Y-m-d H:i:s')
        ];
        
        $stmt->execute($params);
        
        return $db->lastInsertId();
    }
    
    /**
     * Find gift by ID
     */
    public static function find($id)
    {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM gifts WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find gift by gift code
     */
    public static function findByCode($giftCode)
    {
        return static::where('gift_code', $giftCode)->first();
    }
    
    /**
     * Update gift record
     */
    public static function update($id, $data)
    {
        $db = Database::getInstance();
        
        $setParts = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        $sql = "UPDATE gifts SET " . implode(', ', $setParts) . ", updated_at = :updated_at WHERE id = :id";
        $params[':updated_at'] = date('Y-m-d H:i:s');
        
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get gifts by sender user ID
     */
    public static function getBySender($userId)
    {
        return static::where('sender_user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->get();
    }
    
    /**
     * Get pending gifts (not yet redeemed)
     */
    public static function getPendingGifts()
    {
        return static::where('status', 'pending')
                    ->where('expires_at', '>', date('Y-m-d H:i:s'))
                    ->get();
    }
    
    /**
     * Get redeemed gifts
     */
    public static function getRedeemedGifts()
    {
        return static::where('status', 'redeemed')->get();
    }
    
    /**
     * Get expired gifts
     */
    public static function getExpiredGifts()
    {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM gifts 
                WHERE status = 'pending' 
                AND expires_at <= :now 
                ORDER BY expires_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':now' => date('Y-m-d H:i:s')]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mark expired gifts as expired
     */
    public static function markExpiredGifts()
    {
        $db = Database::getInstance();
        
        $sql = "UPDATE gifts 
                SET status = 'expired', updated_at = :updated_at 
                WHERE status = 'pending' 
                AND expires_at <= :now";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':updated_at' => date('Y-m-d H:i:s'),
            ':now' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get gift statistics for a user
     */
    public static function getUserGiftStats($userId)
    {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN status = 'redeemed' THEN 1 ELSE 0 END) as redeemed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                    SUM(credits_amount) as total_credits_gifted,
                    SUM(purchase_amount) as total_amount_spent
                FROM gifts 
                WHERE sender_user_id = :user_id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recent gift activity
     */
    public static function getRecentActivity($limit = 10)
    {
        $db = Database::getInstance();
        
        $sql = "SELECT g.*, u.name as sender_full_name 
                FROM gifts g 
                LEFT JOIN users u ON g.sender_user_id = u.id 
                ORDER BY g.created_at DESC 
                LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if gift code is valid and available
     */
    public static function isValidGiftCode($giftCode)
    {
        $gift = static::findByCode($giftCode);
        
        if (!$gift) {
            return ['valid' => false, 'reason' => 'Gift code not found'];
        }
        
        if ($gift['status'] === 'redeemed') {
            return ['valid' => false, 'reason' => 'Gift already redeemed'];
        }
        
        if ($gift['status'] === 'expired' || strtotime($gift['expires_at']) < time()) {
            return ['valid' => false, 'reason' => 'Gift has expired'];
        }
        
        return ['valid' => true, 'gift' => $gift];
    }
    
    /**
     * Get total revenue from gifts
     */
    public static function getTotalRevenue()
    {
        $db = Database::getInstance();
        
        $sql = "SELECT SUM(purchase_amount) as total_revenue FROM gifts";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_revenue'] ?? 0;
    }
    
    /**
     * Get gift conversion rate (redeemed vs sent)
     */
    public static function getConversionRate()
    {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN status = 'redeemed' THEN 1 ELSE 0 END) as redeemed
                FROM gifts";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total_sent'] > 0) {
            return ($result['redeemed'] / $result['total_sent']) * 100;
        }
        
        return 0;
    }
    
    /**
     * Clean up old expired gifts (optional - for maintenance)
     */
    public static function cleanupOldExpiredGifts($daysOld = 90)
    {
        $db = Database::getInstance();
        
        $sql = "DELETE FROM gifts 
                WHERE status = 'expired' 
                AND expires_at < :cutoff_date";
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([':cutoff_date' => $cutoffDate]);
    }
}