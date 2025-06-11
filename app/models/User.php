<?php
/**
 * User Model
 * 
 * Handles user data and authentication
 */

namespace App\Models;

use PDO;
use PDOException;
use App\Libraries\Model;
use App\Libraries\Database;
use App\Models\UserToken;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int $credits
 * @property string $subscription_status
 * @property string $subscription_ends_at
 * @property string $email_verified_at
 * @property string $email_verification_token
 * @property string $email_verification_sent_at
 * @property string $remember_token
 * @property string $created_at
 * @property string $updated_at
 * @property string $birthdate
 * @property string $zodiac_sign
 */

class User extends Model {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected static $table = 'users';
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected static $primaryKey = 'id';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'credits',
        'subscription_status',
        'subscription_ends_at',
        'email_verified_at',
        'email_verification_sent_at',
        'email_verification_attempts',
        'remember_token',
        'birthdate',
        'zodiac_sign',
        'stripe_customer_id' // Added for Stripe integration
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'remember_token'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_sent_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'credits' => 'integer',
        'email_verification_attempts' => 'integer'
    ];
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

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
     * Find a user by ID
     * 
     * @param int $id
     * @return User|null
     */
    public static function findById($id) {
        return static::find($id);
    }
    
    /**
     * Find a user by email
     * 
     * @param string $email
     * @return User|null
     */
    public static function findByEmail($email) {
        return static::where('email', $email)->first();
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
     * Mark the user's email as verified
     * 
     * @return bool
     */
    public function markEmailAsVerified()
    {
        $this->email_verified_at = date('Y-m-d H:i:s');
        return $this->save();
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
            error_log('Failed to send verification email: ' . $e->getMessage());
            return false;
        }
    }
    
    
    /**
     * Check if the user has an active subscription
     * 
     * @return bool
     */
    public function hasActiveSubscription() {
        return in_array($this->subscription_status, ['active', 'trialing']) && 
               (empty($this->subscription_ends_at) || 
                strtotime($this->subscription_ends_at) > time());
    }
    
    /**
     * Verify the user's password
     * 
     * @param string $password
     * @return bool
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->getAttribute('password'));
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
        $userToken = UserToken::where('user_id', $this->getKey())
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
            error_log('Email verification error: ' . $e->getMessage());
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
        $sentAt = $this->getAttribute('email_verification_sent_at');
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
        $this->setAttribute('remember_token', null);
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
