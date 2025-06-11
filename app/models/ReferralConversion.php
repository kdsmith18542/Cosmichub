<?php
/**
 * Referral Conversion Model
 * 
 * Handles all database operations for the referral_conversions table.
 */

namespace App\Models;

use App\Libraries\Database;

class ReferralConversion extends Model {
    /**
     * @var string The database table name
     */
    protected static $table = 'referral_conversions';
    
    /**
     * @var string The primary key for the table
     */
    protected static $primaryKey = 'id';
    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'referral_id',
        'referred_user_id',
        'converted_at'
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'converted_at' => 'datetime'
    ];
    
    /**
     * Get the referral that owns the conversion
     * 
     * @return Referral|null
     */
    public function referral() {
        return Referral::find($this->referral_id);
    }
    
    /**
     * Get the referred user
     * 
     * @return User|null
     */
    public function referredUser() {
        return User::find($this->referred_user_id);
    }
    
    /**
     * Check if a user has been referred by a specific referral
     * 
     * @param int $referralId
     * @param int $userId
     * @return bool
     */
    public static function hasBeenReferred($referralId, $userId) {
        $conversion = self::where('referral_id', $referralId)
                         ->where('referred_user_id', $userId)
                         ->first();
        
        return $conversion !== null;
    }
    
    /**
     * Get all conversions for a specific referral
     * 
     * @param int $referralId
     * @return array
     */
    public static function getByReferralId($referralId) {
        return self::where('referral_id', $referralId)->get();
    }
    
    /**
     * Get all referrals that have referred a specific user
     * 
     * @param int $userId
     * @return array
     */
    public static function getReferralsForUser($userId) {
        $conversions = self::where('referred_user_id', $userId)->get();
        $referrals = [];
        
        foreach ($conversions as $conversion) {
            $referral = $conversion->referral();
            if ($referral) {
                $referrals[] = $referral;
            }
        }
        
        return $referrals;
    }
}