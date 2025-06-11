<?php
namespace App\Libraries\Security;

class CSRF
{
    /**
     * Generate a CSRF token and store it in the session
     * 
     * @param string $formName The name/identifier for the form
     * @return string The generated token
     */
    public static function generateToken(string $formName = 'default'): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        // Generate a random token
        $token = bin2hex(random_bytes(32));
        
        // Store the token in the session with a timestamp
        $_SESSION['csrf_tokens'][$formName] = [
            'token' => $token,
            'created_at' => time()
        ];

        return $token;
    }

    /**
     * Validate a CSRF token
     * 
     * @param string $token The token to validate
     * @param string $formName The name/identifier for the form
     * @param int $expireSeconds Number of seconds before the token expires (default: 1 hour)
     * @return bool True if valid, false otherwise
     */
    public static function validateToken(string $token, string $formName = 'default', int $expireSeconds = 3600): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if the token exists in the session
        if (empty($_SESSION['csrf_tokens'][$formName])) {
            return false;
        }

        $storedToken = $_SESSION['csrf_tokens'][$formName];

        // Remove the token so it can't be used again
        unset($_SESSION['csrf_tokens'][$formName]);

        // Check if token matches and is not expired
        $isValid = hash_equals($storedToken['token'], $token) && 
                  (time() - $storedToken['created_at'] < $expireSeconds);

        return $isValid;
    }

    /**
     * Get a hidden input field with CSRF token
     * 
     * @param string $formName The name/identifier for the form
     * @return string HTML input field with CSRF token
     */
    public static function getTokenField(string $formName = 'default'): string
    {
        $token = self::generateToken($formName);
        return sprintf('<input type="hidden" name="csrf_token" value="%s">', htmlspecialchars($token, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Verify the CSRF token from POST/GET data
     * 
     * @param string $formName The name/identifier for the form
     * @param bool $throwException Whether to throw an exception on failure
     * @return bool True if valid
     * @throws \RuntimeException If token is invalid and $throwException is true
     */
    public static function verifyRequest(string $formName = 'default', bool $throwException = true): bool
    {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        if (empty($token) || !self::validateToken($token, $formName)) {
            if ($throwException) {
                throw new \RuntimeException('Invalid or expired CSRF token. Please refresh the page and try again.');
            }
            return false;
        }
        
        return true;
    }
}
