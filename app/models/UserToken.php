<?php

namespace App\Models;

use PDO;
use PDOException;
use App\Libraries\Model;
use App\Libraries\Database;

class UserToken extends Model
{
    /**
     * @var string The database table name
     */
    protected static $table = 'user_tokens';
    
    /**
     * @var string The primary key for the table
     */
    protected static $primaryKey = 'id';
    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'user_id',
        'token',
        'type',
        'expires_at',
        'used_at',
        'created_at',
        'updated_at'
    ];
    
    /**
     * @var array The attributes that should be cast to native types
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * The attributes that should be mutated to dates
     *
     * @var array
     */
    protected $dates = [
        'expires_at',
        'used_at',
        'created_at',
        'updated_at'
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
     * Get a token by its value and type
     *
     * @param string $token
     * @param string $type
     * @return UserToken|null
     */
    public static function findByToken(string $token, string $type)
    {
        $db = static::getDb();
        $stmt = $db->prepare("
            SELECT * FROM " . static::$table . " 
            WHERE token = :token 
            AND type = :type 
            AND (expires_at IS NULL OR expires_at > :now)
            AND used_at IS NULL
            LIMIT 1
        ");
        
        $stmt->execute([
            ':token' => $token,
            ':type' => $type,
            ':now' => date('Y-m-d H:i:s')
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $model = new static();
            $model->fill($result);
            return $model;
        }
        
        return null;
    }
    
    /**
     * Mark token as used
     * 
     * @return bool
     */
    public function markAsUsed(): bool
    {
        $this->used_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Check if token is expired
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
     * Get the user that owns the token
     * 
     * @return User|null
     */
    public function user()
    {
        if (!$this->user_id) {
            return null;
        }
        
        return User::find($this->user_id);
    }
}
