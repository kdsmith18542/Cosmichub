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
            
            // Generate verification token
            $token = bin2hex(random_bytes(32));
            $expiresAt = new \DateTime('+24 hours');
            
            // Create or update verification token
            $userToken = UserToken::where('user_id', $user->id)
                ->where('type', 'email_verification')
                ->where('used_at', null)
                ->where('expires_at', '>', date('Y-m-d H:i:s'))
                ->first();
            
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
            
            // Update user's verification token and sent timestamp
            $user->email_verification_token = $token;
            $user->email_verification_sent_at = date('Y-m-d H:i:s');
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
            
            // Send verification email using the Mailer
            return $this->mailer->send(
                $user->email,
                $recipientName,
                $subject,
                $emailContent
            );
            
        } catch (Exception $e) {
            error_log('Failed to send verification email: ' . $e->getMessage());
            return false;
        }
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
