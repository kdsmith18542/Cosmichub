<?php

namespace App\Services;

use PDO;
use Exception;
use App\Models\User;
use App\Models\UserToken;
use App\Utils\Mailer;
use App\Libraries\Config;

class EmailService
{
    /**
     * @var Mailer
     */
    protected $mailer;

    public function __construct()
    {
        $this->mailer = new Mailer();
    }
    /**
     * Send email verification notification
     *
     * @param User $user
     * @return bool
     */
    /**
     * Send verification email to user
     *
     * @param User|object $user User model instance
     * @return bool
     */
    public function sendVerificationEmail($user): bool
    {
        try {
            if (!$user || !$user->email) {
                throw new Exception('Invalid user or email address');
            }

            // Check rate limiting
            if (!$this->canSendVerificationEmail($user)) {
                throw new Exception('Please wait before requesting another verification email.');
            }
            
            // Generate verification token with increased entropy
            $token = bin2hex(random_bytes(48)); // Increased from 32 to 48 bytes
            $expiresAt = new \DateTime('+2 hours'); // Reduced from 24 to 2 hours for security
            
            // Invalidate all previous unused tokens for this user
            UserToken::where('user_id', $user->id)
                ->where('type', 'email_verification')
                ->where('used_at', null)
                ->update(['used_at' => date('Y-m-d H:i:s'), 'invalidated_at' => date('Y-m-d H:i:s')]);

            // Clean up old tokens
            $this->cleanupOldTokens($user->id);

            // Create new verification token
            $userToken = new UserToken([
                'user_id' => $user->id,
                'token' => password_hash($token, PASSWORD_DEFAULT), // Store hashed token
                'type' => 'email_verification',
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'used_at' => null,
                'attempts' => 0
            ]);

            if (!$userToken->save()) {
                throw new Exception('Failed to save verification token');
            }
            
            if (!$userToken) {
                $userToken = new UserToken([
                    'user_id' => $user->id,
                    'token' => $token,
                    'type' => 'email_verification',
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                    'used_at' => null
                ]);
                
                if (!$userToken->save()) {
                    throw new Exception('Failed to save verification token');
                }
            } else {
                $token = $userToken->token; // Use existing valid token
            }
            
            // Update user's verification attempt timestamp
            $user->email_verification_sent_at = date('Y-m-d H:i:s');
            $user->email_verification_attempts = ($user->email_verification_attempts ?? 0) + 1;
            $user->save();
            
            // Build verification URL using site URL from config
            $siteUrl = rtrim(Config::get('app.url'), '/');
            $verificationUrl = "{$siteUrl}/verify-email/" . urlencode($user->id) . "/" . urlencode($token);
            
            // Prepare email content
            $subject = 'Verify Your Email Address';
            $recipientName = $user->first_name ?? $user->username ?? 'User';
            
            // Load email template
            $emailContent = $this->getVerificationEmailTemplate([
                'name' => $recipientName,
                'verification_url' => $verificationUrl,
                'expiration_hours' => 24
            ]);
            
            // Send verification email using the Mailer with additional security headers
            $headers = [
                'X-Entity-Ref-ID' => $this->generateEmailReferenceId($user->id, $token),
                'X-Auto-Response-Suppress' => 'OOF, DR, RN, NRN, AutoReply',
                'Precedence' => 'bulk'
            ];

            return $this->mailer->send(
                $user->email,
                $recipientName,
                $subject,
                $emailContent,
                $headers
            );
            
        } catch (Exception $e) {
            error_log('Failed to send verification email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a verification email can be sent to the user based on rate limiting rules
     *
     * @param User $user
     * @return bool
     */
    protected function canSendVerificationEmail($user): bool
    {
        if (!$user->email_verification_sent_at) {
            return true;
        }

        $lastSentAt = new \DateTime($user->email_verification_sent_at);
        $now = new \DateTime();
        $interval = $now->diff($lastSentAt);

        // Get the total minutes since last email
        $minutesSinceLastEmail = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

        // Define rate limiting rules based on number of attempts
        $attempts = $user->email_verification_attempts ?? 0;
        
        if ($attempts <= 2) {
            // First 3 attempts: Allow 1 email every 2 minutes
            return $minutesSinceLastEmail >= 2;
        } elseif ($attempts <= 5) {
            // Next 3 attempts: Allow 1 email every 10 minutes
            return $minutesSinceLastEmail >= 10;
        } elseif ($attempts <= 10) {
            // Next 5 attempts: Allow 1 email every 30 minutes
            return $minutesSinceLastEmail >= 30;
        } else {
            // After 10 attempts: Allow 1 email every 60 minutes
            return $minutesSinceLastEmail >= 60;
        }
    }

    /**
     * Clean up old verification tokens for a user
     *
     * @param int $userId
     * @return void
     */
    protected function cleanupOldTokens(int $userId): void
    {
        try {
            // Delete tokens older than 48 hours
            UserToken::where('user_id', $userId)
                ->where('type', 'email_verification')
                ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-48 hours')))
                ->delete();

            // Delete all but the last 5 tokens for this user
            $tokens = UserToken::where('user_id', $userId)
                ->where('type', 'email_verification')
                ->orderBy('created_at', 'desc')
                ->get();

            if ($tokens->count() > 5) {
                $tokensToKeep = $tokens->take(5)->pluck('id')->toArray();
                UserToken::where('user_id', $userId)
                    ->where('type', 'email_verification')
                    ->whereNotIn('id', $tokensToKeep)
                    ->delete();
            }
        } catch (\Exception $e) {
            // Log error but don't throw - this is a cleanup operation
            error_log('Failed to cleanup old tokens: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique reference ID for the verification email
     *
     * @param int $userId
     * @param string $token
     * @return string
     */
    protected function generateEmailReferenceId(int $userId, string $token): string
    {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return sprintf('VER-%d-%d-%s', $userId, $timestamp, $random);
    }
    
    /**
     * Send password reset email
     *
     * @param User $user
     * @param string $token
     * @return bool
     */
    /**
     * Send password reset email
     *
     * @param User $user
     * @param string $token
     * @return bool
     */
    public function sendPasswordResetEmail(User $user, string $token): bool
    {
        try {
            if (!$user || !$user->email) {
                throw new Exception('Invalid user or email address');
            }
            
            // Build reset URL using site URL from config
            $siteUrl = rtrim(Config::get('app.url'), '/');
            $resetUrl = "{$siteUrl}/reset-password/" . urlencode($token) . 
                      "?email=" . urlencode($user->email);
            
            // Prepare email content
            $subject = 'Password Reset Request';
            $recipientName = $user->first_name ?? $user->username ?? 'User';
            
            // Load email template
            $emailContent = $this->getPasswordResetEmailTemplate([
                'name' => $recipientName,
                'reset_url' => $resetUrl,
                'expiration_minutes' => 60
            ]);
            
            // Send password reset email using the Mailer
            return $this->mailer->send(
                $user->email,
                $recipientName,
                $subject,
                $emailContent
            );
            
        } catch (Exception $e) {
            error_log('Failed to send password reset email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get verification email template
     * 
     * @param array $data
     * @return string
     */
    protected function getVerificationEmailTemplate(array $data): string
    {
        extract($data);
        ob_start();
        include __DIR__ . '/../../resources/views/emails/verify-email.php';
        return ob_get_clean();
    }
    
    /**
     * Get password reset email template
     * 
     * @param array $data
     * @return string
     */
    protected function getPasswordResetEmailTemplate(array $data): string
    {
        extract($data);
        ob_start();
        include __DIR__ . '/../../resources/views/emails/reset-password.php';
        return ob_get_clean();
    }
}
