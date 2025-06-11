<?php
namespace App\Libraries\Security;

class CSRF
{
    /**
     * Generate a CSRF token and store it in the session
     * 
     * @param string $formName The name/identifier for the form
     * @param int $lifetime The lifetime of the token in seconds
     * @param int $maxTokens The maximum number of tokens to store for this form
     * @return string The generated token
     */
    public static function generateToken(string $formName = 'default', int $lifetime = 3600, int $maxTokens = 25): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        // Generate a random token
        $token = bin2hex(random_bytes(32));
        
        // Store the token in the session with a timestamp and expiry
        // If a specific form name is used, manage its tokens
        if (!isset($_SESSION['csrf_tokens'][$formName]) || !is_array($_SESSION['csrf_tokens'][$formName])) {
            $_SESSION['csrf_tokens'][$formName] = [];
        }

        // Add new token
        $_SESSION['csrf_tokens'][$formName][] = [
            'token' => $token,
            'expires_at' => time() + $lifetime
        ];

        // Remove oldest tokens if exceeding maxTokens
        if (count($_SESSION['csrf_tokens'][$formName]) > $maxTokens) {
            $_SESSION['csrf_tokens'][$formName] = array_slice($_SESSION['csrf_tokens'][$formName], -$maxTokens);
        }

        // Clean up expired tokens for this specific form
        self::cleanupExpiredTokens($formName);

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

        self::cleanupExpiredTokens($formName); // Clean up before validation

        if (empty($_SESSION['csrf_tokens'][$formName]) || !is_array($_SESSION['csrf_tokens'][$formName])) {
            return false;
        }

        $tokenFound = false;
        $validTokenIndex = -1;

        foreach ($_SESSION['csrf_tokens'][$formName] as $index => $storedTokenData) {
            if (hash_equals($storedTokenData['token'], $token)) {
                if (time() < $storedTokenData['expires_at']) {
                    $tokenFound = true;
                    $validTokenIndex = $index;
                    break;
                }
            }
        }

        if ($tokenFound && $validTokenIndex !== -1) {
            // Remove the used token to prevent reuse (one-time use)
            array_splice($_SESSION['csrf_tokens'][$formName], $validTokenIndex, 1);
            return true;
        }

        return false;
    }

    /**
     * Get a hidden input field with CSRF token
     * 
     * @param string $formName The name/identifier for the form
     * @return string HTML input field with CSRF token
     */
    public static function getTokenField(string $formName = 'default'): string
    {
        // Use configured lifetime and maxTokens from VerifyCsrfToken middleware if possible,
        // otherwise use defaults.
        // This requires a way to access middleware config or pass it down.
        // For simplicity, using defaults here.
        $token = self::generateToken($formName, 3600, 25);
        $output = sprintf('<input type="hidden" name="csrf_token" value="%s">', htmlspecialchars($token, ENT_QUOTES, 'UTF-8'));
        // Optionally add form name if VerifyCsrfToken is adapted to use it
        // $output .= sprintf('<input type="hidden" name="_form_name" value="%s">', htmlspecialchars($formName, ENT_QUOTES, 'UTF-8'));
        return $output;
    }

    /**
     * Clean up expired CSRF tokens from the session for a specific form.
     *
     * @param string $formName The name/identifier for the form
     * @return void
     */
    protected static function cleanupExpiredTokens(string $formName = 'default'): void
    {
        if (empty($_SESSION['csrf_tokens'][$formName]) || !is_array($_SESSION['csrf_tokens'][$formName])) {
            return;
        }

        $currentTime = time();
        $_SESSION['csrf_tokens'][$formName] = array_filter(
            $_SESSION['csrf_tokens'][$formName],
            function ($tokenData) use ($currentTime) {
                return isset($tokenData['expires_at']) && $tokenData['expires_at'] > $currentTime;
            }
        );

        // Re-index array after filtering
        $_SESSION['csrf_tokens'][$formName] = array_values($_SESSION['csrf_tokens'][$formName]);
    }

    /**
     * Clean up all expired CSRF tokens from the session.
     *
     * @return void
     */
    public static function cleanupAllExpiredTokens(): void
    {
        if (empty($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            return;
        }
        foreach (array_keys($_SESSION['csrf_tokens']) as $formName) {
            self::cleanupExpiredTokens($formName);
            if (empty($_SESSION['csrf_tokens'][$formName])) {
                unset($_SESSION['csrf_tokens'][$formName]);
            }
        }
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
