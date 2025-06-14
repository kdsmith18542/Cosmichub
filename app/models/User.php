<?php
/**
 * User Model
 * 
 * Handles user data and authentication
 */

namespace App\Models;

use App\Core\Database\Model;
use App\Models\UserToken;
use App\Services\EmailService;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'email', 'username', 'password', 'role', 'status', 
        'credits', 'subscription_status', 'subscription_expires_at',
        'email_verified_at', 'last_login_at', 'login_count', 'avatar'
    ];
    protected $hidden = ['password', 'remember_token'];
    protected $timestamps = true;
    
    protected $casts = [
        'credits' => 'integer',
        'login_count' => 'integer',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'subscription_expires_at' => 'datetime'
    ];

    /**
     * Create a new User model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public static function findByEmail($email) {
        return static::query()->where('email', $email)->first();
    }
    
    /**
     * Find a user by username.
     *
     * @param string $username
     * @return User|null
     */
    public static function findByUsername($username) {
        return static::query()->where('username', $username)->first();
    }
    
    /**
     * Get all email verification tokens for this user
     * 
     * @return array
     */
    public function emailVerificationTokens()
    {
        return UserToken::where('user_id', $this->getKey())
            ->where('type', 'email_verification')
            ->get();
    }
    
    /**
     * Get the latest email verification token for this user
     * 
     * @return UserToken|null
     */
    public function getLatestEmailVerificationToken()
    {
        return UserToken::where('user_id', $this->getKey())
            ->where('type', 'email_verification')
            ->orderBy('created_at', 'DESC')
            ->first();
    }
    
    /**
     * Create a new email verification token for the user
     * 
     * @return UserToken
     */
    public function createEmailVerificationToken()
    {
        // Invalidate any existing tokens
        $tokens = $this->emailVerificationTokens();
        if (is_array($tokens) || $tokens instanceof \Traversable) {
            foreach ($tokens as $token) {
                if (method_exists($token, 'markAsUsed')) {
                    $token->markAsUsed();
                }
            }
        }
        
        // Create new token
        $token = new UserToken([
            'user_id' => $this->getKey(),
            'token' => bin2hex(random_bytes(32)),
            'type' => 'email_verification',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'used_at' => null
        ]);
        
        $token->save();
        
        return $token;
    }
    
    /**
     * Check if the user has verified their email
     * 
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return !empty($this->email_verified_at);
    }

    /**
     * Deduct credits from the user's account.
     *
     * @param int $amount The number of credits to deduct.
     * @return bool True if credits were successfully deducted, false otherwise.
     */
    public function deductCredits($amount)
    {
        if ($this->credits >= $amount) {
            $this->credits -= $amount;
            return $this->save();
        }
        return false;
    }
    
    /**
     * Check if the user's email is verified.
     *
     * @return bool
     */
    public function isEmailVerified() {
        return !is_null($this->email_verified_at);
    }
    
    /**
     * Get the user's email address for verification
     * 
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }
    
    /**
     * Send the email verification notification
     * 
     * @return bool
     */
    public function sendEmailVerificationNotification()
    {
        try {
            $emailService = new \App\Services\EmailService();
            return $emailService->sendVerificationEmail($this);
        } catch (\Exception $e) {
            \App\Support\Log::error('Failed to send verification email: ' . $e->getMessage());
            return false;
        }
    }
    
    
    /**
     * Mark the user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified() {
        $this->email_verified_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Check if the user has an active subscription
     * 
     * @return bool
     */
    public function hasActiveSubscription() {
        return $this->subscription_status === 'active' && 
               ($this->subscription_expires_at === null || 
                strtotime($this->subscription_expires_at) > time());
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
     * Check if email has been verified (alias for isEmailVerified)
     * 
     * @return bool
     */
    public function hasVerifiedEmail() {
        return $this->isEmailVerified();
    }
    
    /**
     * Generate a new email verification token
     * 
     * @return string
     */
    public function generateEmailVerificationToken() {
        return bin2hex(random_bytes(32));
    }
    

    
    /**
     * Verify the email using the token
     * 
     * @param string $token
     * @return array ['success' => bool, 'message' => string]
     */
    public function verifyEmail($rawToken) {
        // Check if email is already verified
        if ($this->hasVerifiedEmail()) {
            return [
                'success' => false,
                'message' => 'Email is already verified.'
            ];
        }

        // Get the latest valid token
        $userToken = UserToken::query()
            ->where('user_id', $this->id)
            ->where('type', 'email_verification')
            ->where('used_at', null)
            ->where('invalidated_at', null)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$userToken) {
            return [
                'success' => false,
                'message' => 'Verification token has expired or is invalid. Please request a new one.',
                'expired' => true
            ];
        }

        // Increment token attempts
        $userToken->attempts = ($userToken->attempts ?? 0) + 1;
        $userToken->save();

        // Check for too many attempts on this token
        if ($userToken->attempts > 5) {
            $userToken->invalidated_at = date('Y-m-d H:i:s');
            $userToken->save();
            return [
                'success' => false,
                'message' => 'Too many verification attempts. Please request a new verification email.',
                'expired' => true
            ];
        }

        try {
            // Verify the token
            if (!password_verify($rawToken, $userToken->token)) {
                return [
                    'success' => false,
                    'message' => 'Invalid verification token.'
                ];
            }

            // Update user record
            $this->email_verified_at = date('Y-m-d H:i:s');
            $this->email_verification_sent_at = null;
            $this->email_verification_attempts = 0; // Reset attempts on success
            $this->status = 'active';

            // Mark token as used
            $userToken->used_at = date('Y-m-d H:i:s');
            $userToken->save();

            if ($this->save()) {
                return [
                    'success' => true,
                    'message' => 'Email verified successfully! You can now log in.'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to update user record.'
            ];

        } catch (\Exception $e) {
            \App\Support\Log::error('Email verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while verifying your email. Please try again.'
            ];
        }
    }
    
    /**
     * Check if the verification token is expired
     * 
     * @param int $expiryHours Number of hours until expiry (default: 24)
     * @return bool
     */
    public function isVerificationTokenExpired($expiryHours = 24) {
        $sentAt = $this->email_verification_sent_at;
        if (empty($sentAt)) {
            return true;
        }
        
        if ($sentAt instanceof \DateTime) {
            $sentAt = $sentAt->getTimestamp();
        } else {
            $sentAt = is_string($sentAt) ? strtotime($sentAt) : $sentAt;
        }
        
        $expiryTime = $sentAt + ($expiryHours * 3600);
        return time() > $expiryTime;
    }
    
    /**
     * Delete all remember tokens for this user
     * 
     * @return bool
     */
    public function deleteRememberTokens() {
        $this->remember_token = null;
        return $this->save();
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
