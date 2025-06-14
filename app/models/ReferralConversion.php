<?php
/**
 * Referral Conversion Model
 * 
 * Handles all database operations for the referral_conversions table.
 */

namespace App\Models;

use App\Core\Database\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralConversion extends Model {
    /**
     * @var string The database table name
     */
    // protected static $table = 'referral_conversions'; // Conventionally handled by base Model
    
    /**
     * @var string The primary key for the table
     */
    // protected static $primaryKey = 'id'; // Conventionally handled by base Model
    
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
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // Assuming created_at/updated_at are not used or handled differently
    
    /**
     * Get the referral that owns the conversion.
     * 
     * @return BelongsTo
     */
    public function referral(): BelongsTo 
    {
        return $this->belongsTo(Referral::class);
    }
    
    /**
     * Get the referred user.
     * 
     * @return BelongsTo
     */
    public function referredUser(): BelongsTo 
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
    
    /**
     * Check if a user has been referred by a specific referral.
     * 
     * @param int $referralId
     * @param int $userId
     * @return bool
     */
    public static function hasBeenReferred(int $referralId, int $userId): bool 
    {
        return static::query()
                         ->where('referral_id', $referralId)
                         ->where('referred_user_id', $userId)
                         ->exists();
    }
    
    /**
     * Get all conversions for a specific referral.
     * 
     * @param int $referralId
     * @return Collection
     */
    public static function getByReferralId(int $referralId): Collection 
    {
        return static::query()->where('referral_id', $referralId)->get();
    }
    
    /**
     * Get all referrals that have referred a specific user.
     * 
     * @param int $userId
     * @return Collection
     */
    public static function getReferralsForUser(int $userId): Collection 
    {
        return Referral::query()
            ->whereHas('conversions', function ($query) use ($userId) {
                $query->where('referred_user_id', $userId);
            })
            ->get();
    }
}