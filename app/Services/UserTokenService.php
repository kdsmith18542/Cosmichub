<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\UserTokenRepository;
use App\Models\UserToken;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * UserToken Service for handling user token business logic
 */
class UserTokenService extends Service
{
    /**
     * @var UserTokenRepository
     */
    protected $userTokenRepository;
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * Initialize repositories
     * 
     * @return void
     */
    protected function initializeRepositories()
    {
        $this->userTokenRepository = $this->container->resolve(UserTokenRepository::class);
    }
    
    /**
     * Create a new email verification token
     *
     * @param int $userId
     * @param string $token
     * @param DateTime $expiresAt
     * @return UserToken
     * @throws Exception
     */
    public function createEmailVerificationToken(int $userId, string $token, DateTime $expiresAt): UserToken
    {
        // Clean up old tokens first
        $this->cleanupOldTokens($userId);
        
        $tokenData = [
            'user_id' => $userId,
            'token' => password_hash($token, PASSWORD_DEFAULT), // Store hashed token
            'type' => 'email_verification',
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'used_at' => null,
            'attempts' => 0
        ];
        
        $userToken = $this->userTokenRepository->create($tokenData);
        
        if (!$userToken) {
            throw new Exception('Failed to save verification token');
        }
        
        return $userToken;
    }
    
    /**
     * Create a password reset token
     *
     * @param int $userId
     * @param string $token
     * @param DateTime $expiresAt
     * @return UserToken
     * @throws Exception
     */
    public function createPasswordResetToken(int $userId, string $token, DateTime $expiresAt): UserToken
    {
        $tokenData = [
            'user_id' => $userId,
            'token' => password_hash($token, PASSWORD_DEFAULT),
            'type' => 'password_reset',
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'used_at' => null,
            'attempts' => 0
        ];
        
        $userToken = $this->userTokenRepository->create($tokenData);
        
        if (!$userToken) {
            throw new Exception('Failed to save password reset token');
        }
        
        return $userToken;
    }
    
    /**
     * Find a valid token
     *
     * @param string $token
     * @param string $type
     * @return UserToken|null
     */
    public function findValidToken(string $token, string $type): ?UserToken
    {
        return $this->userTokenRepository->findValidToken($token, $type);
    }
    
    /**
     * Mark a token as used
     *
     * @param UserToken $token
     * @return bool
     */
    public function markTokenAsUsed(UserToken $token): bool
    {
        $used_at = date('Y-m-d H:i:s');
        return $this->userTokenRepository->update($token->id, ['used_at' => $used_at]);
    }
    
    /**
     * Clean up old tokens for a user
     *
     * @param int $userId
     * @return void
     */
    protected function cleanupOldTokens(int $userId): void
    {
        try {
            // Delete tokens older than 48 hours
            $this->userTokenRepository->deleteOldTokens(
                $userId, 
                'email_verification', 
                date('Y-m-d H:i:s', strtotime('-48 hours'))
            );

            // Keep only the last 5 tokens for this user
            $this->userTokenRepository->keepLatestTokens($userId, 'email_verification', 5);
        } catch (Exception $e) {
            // Log error but don't throw - cleanup is not critical
            $this->logger->error('Failed to cleanup old tokens: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify a token against the stored hash
     *
     * @param string $plainToken
     * @param string $hashedToken
     * @return bool
     */
    public function verifyToken(string $plainToken, string $hashedToken): bool
    {
        return password_verify($plainToken, $hashedToken);
    }
}