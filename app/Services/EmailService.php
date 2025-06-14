<?php

namespace App\Services;

use PDO;
use Exception;
use App\Models\User;
use App\Utils\Mailer;
use App\Libraries\Config;
use App\Services\UserTokenService;
use App\Repositories\UserRepository;
use App\Repositories\UserTokenRepository;
use DateTime;
use Psr\Log\LoggerInterface;

class EmailService
{
    /**
     * @var Mailer
     */
    private $mailer;
    
    /**
     * @var UserTokenService
     */
    private $userTokenService;
    
    /**
     * @var UserRepository
     */
    private $userRepository;
    
    /**
     * @var UserTokenRepository
     */
    private $userTokenRepository;
    private $logger;

    public function __construct(
        Mailer $mailer = null,
        UserTokenService $userTokenService = null,
        UserRepository $userRepository = null,
        UserTokenRepository $userTokenRepository = null,
        LoggerInterface $logger = null
    ) {
        $this->mailer = $mailer ?? new Mailer();
        $this->userTokenService = $userTokenService ?? new UserTokenService();
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->userTokenRepository = $userTokenRepository ?? new UserTokenRepository();
        $this->logger = $logger;
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
            $this->userTokenRepository->invalidateUnusedTokensByUserAndType(
                $user->id,
                'email_verification'
            );

            // Create new verification token using service
            $userToken = $this->userTokenService->createEmailVerificationToken(
                $user->id,
                $token,
                $expiresAt
            );
            
            // Update user's verification attempt timestamp
            $new_email_verification_sent_at = date('Y-m-d H:i:s');
            $new_email_verification_attempts = ($user->email_verification_attempts ?? 0) + 1;
            $this->userRepository->update($user->id, [
                'email_verification_sent_at' => $new_email_verification_sent_at,
                'email_verification_attempts' => $new_email_verification_attempts
            ]);

            // Refresh user object to get updated values if needed later in this method
            // However, in this specific case, the updated user object is not used further in this method after this block.
            // If it were, we would call: $user = $this->userRepository->findById($user->id);
            
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
            $this->logger->error('Failed to send verification email: ' . $e->getMessage());
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

    // Token cleanup is now handled by UserTokenService

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
            $this->logger->error('Failed to send password reset email: ' . $e->getMessage());
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
