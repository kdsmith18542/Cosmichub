<?php
namespace App\Utils;

use PDO;
use PDOException;

class TokenManager {
    private $db;
    private $tokenExpiry = 86400; // 24 hours in seconds

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Generate a secure random token
     * 
     * @param int $length Length of the token in bytes (before base64 encoding)
     * @return string URL-safe base64 encoded token
     */
    public function generateToken($length = 32) {
        try {
            $randomBytes = random_bytes($length);
            // Convert to URL-safe base64
            return rtrim(strtr(base64_encode($randomBytes), '+/', '-_'), '=');
        } catch (\Exception $e) {
            error_log("Token generation failed: " . $e->getMessage());
            throw new \Exception("Failed to generate secure token");
        }
    }

    /**
     * Create a verification token for a user
     * 
     * @param int $userId User ID to create token for
     * @param string $type Token type (e.g., 'email_verification', 'password_reset')
     * @return string The generated token
     */
    public function createToken($userId, $type = 'email_verification') {
        $token = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', time() + $this->tokenExpiry);
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_tokens (
                    user_id, 
                    token, 
                    type, 
                    expires_at, 
                    created_at
                ) VALUES (
                    :user_id, 
                    :token, 
                    :type, 
                    :expires_at, 
                    NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':token' => $token,
                ':type' => $type,
                ':expires_at' => $expiresAt
            ]);
            
            return $token;
            
        } catch (PDOException $e) {
            error_log("Failed to create token: " . $e->getMessage());
            throw new \Exception("Failed to create verification token");
        }
    }

    /**
     * Validate a token
     * 
     * @param string $token The token to validate
     * @param string $type Expected token type
     * @return array|false Token data if valid, false otherwise
     */
    public function validateToken($token, $type = 'email_verification') {
        try {
            // First, clean up any expired tokens
            $this->cleanupExpiredTokens();
            
            $stmt = $this->db->prepare("
                SELECT ut.*, u.email, u.username
                FROM user_tokens ut
                JOIN users u ON ut.user_id = u.id
                WHERE ut.token = :token 
                AND ut.type = :type
                AND ut.used_at IS NULL
                AND ut.expires_at > NOW()
                LIMIT 1
            ");
            
            $stmt->execute([
                ':token' => $token,
                ':type' => $type
            ]);
            
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $tokenData ?: false;
            
        } catch (PDOException $e) {
            error_log("Token validation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark a token as used
     * 
     * @param string $token The token to mark as used
     * @return bool True on success, false on failure
     */
    public function markTokenUsed($token) {
        try {
            $stmt = $this->db->prepare("
                UPDATE user_tokens 
                SET used_at = NOW() 
                WHERE token = :token
                AND used_at IS NULL
            ");
            
            return $stmt->execute([':token' => $token]);
            
        } catch (PDOException $e) {
            error_log("Failed to mark token as used: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up expired tokens
     * 
     * @return int Number of tokens deleted
     */
    public function cleanupExpiredTokens() {
        try {
            // Delete tokens that are either expired or used more than 7 days ago
            $stmt = $this->db->prepare("
                DELETE FROM user_tokens 
                WHERE expires_at < NOW() 
                OR (used_at IS NOT NULL AND used_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
            ");
            
            $stmt->execute();
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Failed to clean up expired tokens: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all active tokens for a user
     * 
     * @param int $userId User ID
     * @param string $type Optional token type filter
     * @return array Array of token data
     */
    public function getUserTokens($userId, $type = null) {
        try {
            $sql = "
                SELECT * 
                FROM user_tokens 
                WHERE user_id = :user_id
                AND (expires_at > NOW() OR used_at IS NOT NULL)
            ";
            
            $params = [':user_id' => $userId];
            
            if ($type) {
                $sql .= " AND type = :type";
                $params[':type'] = $type;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Failed to get user tokens: " . $e->getMessage());
            return [];
        }
    }
}
