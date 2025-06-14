<?php

namespace App\Models;

use App\Core\Database\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserToken extends Model
{
    /**
     * @var string The database table name
     */
    // protected static $table = 'user_tokens'; // Conventionally handled by base Model
    
    /**
     * @var string The primary key for the table
     */
    // protected static $primaryKey = 'id'; // Conventionally handled by base Model
    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'user_id',
        'token',
        'type',
        'expires_at',
        'used_at',
        // 'created_at', // Handled by timestamps
        // 'updated_at'  // Handled by timestamps
    ];
    
    /**
     * @var array The attributes that should be cast to native types
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        // 'created_at' and 'updated_at' are handled by Eloquent's $timestamps property (true by default)
    ];
    
    /**
     * The attributes that should be hidden for arrays
     *
     * @var array
     */
    protected $hidden = [
        'token'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    
    /**
     * Get a token by its value and type.
     *
     * @param string $token
     * @param string $type
     * @return static|null
     */
    public static function findByToken(string $token, string $type): ?static
    {
        return static::query()
            ->where('token', $token)
            ->where('type', $type)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
            })
            ->whereNull('used_at')
            ->first();
    }
    
    /**
     * Mark token as used.
     * 
     * @return bool
     */
    public function markAsUsed(): bool
    {
        $this->used_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Check if token is expired.
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return strtotime($this->expires_at) < time();
    }
    
    /**
     * Get the user that owns the token.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
