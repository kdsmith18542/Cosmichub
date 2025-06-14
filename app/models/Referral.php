<?php
/**
 * Referral Model
 * 
 * Handles all database operations for the referrals table.
 */

namespace App\Models;

use App\Core\Database\Model;

class Referral extends Model {
    // The table name 'referrals' and primary key 'id' will be inferred by the base Model.
    // Timestamps (created_at, updated_at) are handled by the base Model by default.

    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'user_id',
        'referral_code',
        'type',
        'archetype_id', // Added for archetype-specific referrals
        'successful_referrals',
        'created_at',
        'updated_at'
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'successful_referrals' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Referral type constants
     */
    const TYPE_RARITY_SCORE = 'rarity-score';
    const TYPE_COMPATIBILITY = 'compatibility';
    
    /**
     * Get the user that owns the referral
     * 
     * @return User|null
     */
    public function user(): ?User
    {
        return User::find($this->user_id);
    }
    
    /**
     * Get the referral conversions for this referral
     * 
     * @return array
     */
    public function conversions(): \App\Core\Database\Collection
    {
        return ReferralConversion::query()->where('referral_id', $this->id)->get();
    }
    
    /**
     * Generate a unique referral code
     * 
     * @return string
     */
    public static function generateUniqueCode(): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        
        // Generate a random 10-character code
        for ($i = 0; $i < 10; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Check if code already exists
        $existingReferral = static::query()->where('referral_code', $code)->first();
        
        // If code exists, generate a new one recursively
        if ($existingReferral) {
            return self::generateUniqueCode();
        }
        
        return $code;
    }
    
    /**
     * Create a new referral for a user
     * 
     * @param int $userId
     * @param string $type
     * @param int|null $archetypeId
     * @return Referral|null
     */
    public static function createForUser(int $userId, string $type = self::TYPE_RARITY_SCORE, ?int $archetypeId = null): ?static
    {
        // Check if user already has a referral of this type (and archetype if provided)
        $query = static::query()->where('user_id', $userId)
                                ->where('type', $type);
        if ($archetypeId !== null) {
            $query = $query->where('archetype_id', $archetypeId);
        }
        $existingReferral = $query->first();
        if ($existingReferral) {
            return $existingReferral;
        }
        // Create new referral
        return static::create([
            'user_id' => $userId,
            'referral_code' => self::generateUniqueCode(),
            'type' => $type,
            'archetype_id' => $archetypeId,
            'successful_referrals' => 0,
            // created_at and updated_at are handled automatically by the base Model
        ]);
    }
    
    /**
     * Find a referral by its code
     * 
     * @param string $code
     * @return Referral|null
     */
    public static function findByCode(string $code): ?static
    {
        return static::query()->where('referral_code', $code)->first();
    }
    
    /**
     * Track a successful referral
     * 
     * @param int $referredUserId
     * @return bool
     */
    public function trackReferral(int $referredUserId): bool
    {
        // Check if this user has already been referred by this referral
        $existingConversion = ReferralConversion::query()
                                              ->where('referral_id', $this->id)
                                              ->where('referred_user_id', $referredUserId)
                                              ->first();
        
        if ($existingConversion) {
            return false; // Already tracked
        }
        
        // Create new conversion record
        $conversion = ReferralConversion::create([
            'referral_id' => $this->id,
            'referred_user_id' => $referredUserId,
            // converted_at should be handled by ReferralConversion model's timestamps if applicable
            // or set explicitly if it's not a standard timestamp field.
            // Assuming ReferralConversion also uses automatic timestamps or has its own logic.
            'converted_at' => date('Y-m-d H:i:s') // Use native PHP date function
        ]);
        
        if ($conversion) {
            // Increment successful referrals count
            $this->successful_referrals += 1;
            // updated_at is handled automatically by the base Model on save
            return $this->save();
        }
        
        return false;
    }
    
    /**
     * Check if the user has enough referrals to unlock content
     * 
     * @param int $requiredReferrals
     * @return bool
     */
    public function hasEnoughReferrals(int $requiredReferrals = 3): bool
    {
        return $this->successful_referrals >= $requiredReferrals;
    }
    
    /**
     * Get the referral URL
     * 
     * @return string
     */
    public function getReferralUrl(): string
    {
        $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $baseUrl .= $_SERVER['HTTP_HOST'];
        
        return $baseUrl . '/register?ref=' . $this->referral_code;
    }
}