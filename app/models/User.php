<?php
/**
 * User Model
 * 
 * Handles all database operations for the users table.
 */

class User extends Model {
    /**
     * @var string The database table name
     */
    protected static $table = 'users';
    
    /**
     * @var string The primary key for the table
     */
    protected static $primaryKey = 'id';
    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'credits',
        'subscription_status',
        'subscription_ends_at',
        'email_verified_at',
        'remember_token',
        'created_at',
        'updated_at'
    ];
    
    /**
     * The attributes that should be hidden for serialization
     * 
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'credits' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Find a user by email
     * 
     * @param string $email
     * @return User|null
     */
    public static function findByEmail($email) {
        return self::where('email', '=', $email)->first();
    }
    
    /**
     * Check if the user has a verified email
     * 
     * @return bool
     */
    public function hasVerifiedEmail() {
        return $this->email_verified_at !== null;
    }
    
    /**
     * Mark the user's email as verified
     * 
     * @return bool
     */
    public function markEmailAsVerified() {
        $this->email_verified_at = now();
        return $this->save();
    }
    
    /**
     * Check if the user has an active subscription
     * 
     * @return bool
     */
    public function hasActiveSubscription() {
        return $this->subscription_status === 'active' && 
               ($this->subscription_ends_at === null || $this->subscription_ends_at > now());
    }
    
    /**
     * Check if the user has enough credits
     * 
     * @param int $requiredCredits
     * @return bool
     */
    public function hasEnoughCredits($requiredCredits = 1) {
        return $this->credits >= $requiredCredits;
    }
    
    /**
     * Deduct credits from the user's account
     * 
     * @param int $amount
     * @return bool
     */
    public function deductCredits($amount = 1) {
        if ($this->hasEnoughCredits($amount)) {
            $this->credits -= $amount;
            return $this->save();
        }
        return false;
    }
    
    /**
     * Add credits to the user's account
     * 
     * @param int $amount
     * @return bool
     */
    public function addCredits($amount) {
        $this->credits += $amount;
        return $this->save();
    }
    
    /**
     * Get the user's reports
     * 
     * @return array
     */
    public function reports() {
        return Report::where('user_id', '=', $this->id)->get();
    }
    
    /**
     * Get the user's subscription
     * 
     * @return Subscription|null
     */
    public function subscription() {
        return Subscription::where('user_id', '=', $this->id)
                         ->where('status', '=', 'active')
                         ->first();
    }
    
    /**
     * Get the user's credit transactions
     * 
     * @return array
     */
    public function creditTransactions() {
        return CreditTransaction::where('user_id', '=', $this->id)
                              ->orderBy('created_at', 'desc')
                              ->get();
    }
    
    /**
     * Set the user's password (automatically hashes it)
     * 
     * @param string $password
     * @return void
     */
    public function setPasswordAttribute($password) {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify the user's password
     * 
     * @param string $password
     * @return bool
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
    
    /**
     * Create a new password reset token
     * 
     * @return string
     */
    public function createPasswordResetToken() {
        $token = bin2hex(random_bytes(32));
        
        // Store the token in the database
        Database::insert('password_resets', [
            'email' => $this->email,
            'token' => $token,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $token;
    }
    
    /**
     * Reset the user's password
     * 
     * @param string $token
     * @param string $newPassword
     * @return bool
     */
    public static function resetPassword($token, $newPassword) {
        // Find the password reset record
        $reset = Database::first(
            'SELECT * FROM password_resets WHERE token = ? AND created_at > datetime("now", "-1 hour")',
            [$token]
        );
        
        if (!$reset) {
            return false;
        }
        
        // Find the user by email
        $user = self::findByEmail($reset->email);
        
        if (!$user) {
            return false;
        }
        
        // Update the password
        $user->password = $newPassword;
        $saved = $user->save();
        
        // Delete the used token
        if ($saved) {
            Database::delete('password_resets', 'token = ?', [$token]);
        }
        
        return $saved;
    }
    
    /**
     * Create a new remember token
     * 
     * @return string
     */
    public function createRememberToken() {
        $token = bin2hex(random_bytes(32));
        
        // Store the token in the database
        Database::insert('user_tokens', [
            'user_id' => $this->id,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $token;
    }
    
    /**
     * Find a user by remember token
     * 
     * @param string $token
     * @return User|null
     */
    public static function findByRememberToken($token) {
        $record = Database::first(
            'SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > ?',
            [$token, date('Y-m-d H:i:s')]
        );
        
        if (!$record) {
            return null;
        }
        
        return self::find($record->user_id);
    }
    
    /**
     * Delete all remember tokens for this user
     * 
     * @return bool
     */
    public function deleteRememberTokens() {
        return Database::delete('user_tokens', 'user_id = ?', [$this->id]);
    }
    
    /**
     * Log the user out (delete remember tokens)
     * 
     * @return bool
     */
    public function logout() {
        return $this->deleteRememberTokens();
    }
}
