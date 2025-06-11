<?php
namespace App\Middlewares;

use App\Libraries\Security\CSRF;

class VerifyCsrfToken
{
    /**
     * List of URIs that should be excluded from CSRF verification
     * 
     * @var array
     */
    protected $except = [
        // Add URIs that should be excluded from CSRF protection
        // Example: 'api/*', 'webhook/*'
    ];

    /**
     * The number of seconds a CSRF token is valid.
     *
     * @var int
     */
    protected $tokenLifetime = 7200; // 2 hours

    /**
     * The maximum number of tokens to keep per session.
     *
     * @var int
     */
    protected $maxTokens = 25;

    /**
     * Handle an incoming request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        if ($this->isReading($request) || 
            $this->inExceptArray($request) || 
            $this->tokensMatch($request)) {
            // Regenerate token on successful validation for better security
            if (!$this->isReading($request) && !$this->inExceptArray($request)) {
                $this->regenerateToken($request);
            }
            return $next($request);
            return $next($request);
        }

        // If we get here, the CSRF token is invalid
        if ($request->expectsJson()) {
            return $this->jsonError('CSRF token mismatch.');
        }

        // For regular form submissions, redirect back with error
        // Use a more generic error message to avoid revealing too much information
        $_SESSION['error'] = 'Unable to process your request. Please try again.';
        
        // Get the previous URL or fallback to home
        $previousUrl = $_SERVER['HTTP_REFERER'] ?? '/';
        
        // Clear any output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Redirect back
        header('Location: ' . $previousUrl);
        exit;
    }

    /**
     * Regenerate the CSRF token for the current request's form.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return void
     */
    protected function regenerateToken($request)
    {
        $formName = $this->getFormNameFromRequest($request) ?? 'default';
        CSRF::generateToken($formName, $this->tokenLifetime, $this->maxTokens);
    }

    /**
     * Get the form name from the request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return string|null
     */
    protected function getFormNameFromRequest($request)
    {
        $body = $request->getParsedBody();
        return $body['_form_name'] ?? null; // Assuming form name is passed as _form_name
    }

    /**
     * Return a JSON error response.
     *
     * @param string $message
     * @param int $status
     * @return \App\Libraries\JsonResponse
     */
    protected function jsonError(string $message, int $status = 419) // 419 Authentication Timeout (CSRF)
    {
        return new \App\Libraries\JsonResponse(['error' => $message], $status);
    }

    /**
     * Get the CSRF token from the request.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        $body = $request->getParsedBody();
        $token = $body['csrf_token'] ?? $request->getHeaderLine('X-CSRF-TOKEN');
        
        if (empty($token) && $request->hasHeader('X-XSRF-TOKEN')) {
            $token = $request->getHeaderLine('X-XSRF-TOKEN');
            // If using X-XSRF-TOKEN, it's often URL-encoded and needs decoding.
            // However, PHPs standard $_COOKIE handling already decodes it.
            // If it's passed in a custom header, it might need urldecode().
            // For simplicity, assuming it's already decoded if passed this way.
        }
        return $token;
    }

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);
        $formName = $this->getFormNameFromRequest($request) ?? 'default';

        if (empty($token)) {
            return false;
        }

        return CSRF::validateToken($token, $formName, $this->tokenLifetime);
    }

    /**
     * Add the CSRF token to the response cookies.
     * This is useful for JavaScript-driven applications.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @param  \Psr\Http\Message\ResponseInterface  $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function addCookieToResponse($request, $response)
    {
        $config = config('session'); // Assuming a global config helper

        $token = CSRF::generateToken('default', $this->tokenLifetime, $this->maxTokens);

        // This part requires a proper Response object that can handle cookies.
        // For now, this is a placeholder for how it might be done.
        // setcookie(
        //     'XSRF-TOKEN',
        //     $token,
        //     time() + $this->tokenLifetime,
        //     $config['cookie']['path'],
        //     $config['cookie']['domain'],
        //     $config['cookie']['secure'],
        //     false, // HttpOnly should be false for XSRF-TOKEN
        //     $config['cookie']['samesite']
        // );

        return $response;
    }
}

// Helper function to get CSRF token (can be used in views)
if (!function_exists('csrf_token')) {
    function csrf_token(string $formName = 'default'): string
    {
        return App\Libraries\Security\CSRF::generateToken($formName);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(string $formName = 'default'): string
    {
        return App\Libraries\Security\CSRF::getTokenField($formName);
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(string $formName = 'default', bool $throwException = true): bool
    {
        return App\Libraries\Security\CSRF::verifyRequest($formName, $throwException);
    }
}
    }

    /**
     * Determine if the HTTP request uses a 'read' verb
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool
     */
    protected function isReading($request)
    {
        return in_array($request->getMethod(), ['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        $uri = $request->getUri()->getPath();
        
        foreach ($this->except as $except) {
            // Convert wildcard to regex
            $pattern = str_replace('*', '.*', $except);
            if (preg_match('#^' . $pattern . '$#', $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the session and input CSRF tokens match
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);
        
        if (empty($token)) {
            return false;
        }
        
        // Use the form name if provided, otherwise use default
        $formName = $request->getParsedBody()['_form_name'] ?? 'default';
        
        return CSRF::validateToken($token, $formName);
    }

    /**
     * Get the CSRF token from the request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        $token = $request->getParsedBody()['_token'] ?? 
                $request->getHeaderLine('X-CSRF-TOKEN') ??
                $request->getHeaderLine('X-XSRF-TOKEN');

        if (empty($token) && !empty($_COOKIE['XSRF-TOKEN'])) {
            $token = $this->decryptXSRFToken($_COOKIE['XSRF-TOKEN']);
        }

        return $token;
    }

    /**
     * Decrypt the XSRF token from the cookie
     * 
     * @param string $token
     * @return string|null
     */
    protected function decryptXSRFToken($token)
    {
        // Implement your decryption logic here
        // This is a simplified example - in production, use proper encryption
        return base64_decode($token);
    }

    /**
     * Return a JSON error response
     * 
     * @param string $message
     * @param int $statusCode
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function jsonError($message, $statusCode = 419)
    {
        $response = new \Laminas\Diactoros\Response\JsonResponse([
            'error' => $message,
            'status' => $statusCode
        ], $statusCode);
        
        return $response;
    }
}
