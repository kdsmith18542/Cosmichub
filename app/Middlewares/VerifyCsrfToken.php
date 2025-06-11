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
            return $next($request);
        }

        // If we get here, the CSRF token is invalid
        if ($request->expectsJson()) {
            return $this->jsonError('CSRF token mismatch.');
        }

        // For regular form submissions, redirect back with error
        $_SESSION['error'] = 'The form has expired. Please refresh the page and try again.';
        
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
