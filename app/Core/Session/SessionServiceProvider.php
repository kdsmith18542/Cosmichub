<?php

namespace App\Core\Session;

use App\Core\Application;
use App\Core\ServiceProvider;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Enhanced SessionServiceProvider for session management
 * 
 * This provider has been enhanced to support improved session configuration,
 * security settings, and better session lifecycle management.
 */
class SessionServiceProvider extends ServiceProvider
{
    /**
     * Services provided by this provider
     *
     * @var array
     */
    protected $provides = [
        'session',
        'session.manager',
        Session::class,
    ];
    
    /**
     * Singletons to register
     *
     * @var array
     */
    protected $singletons = [
        'session' => Session::class,
        'session.manager' => Session::class,
        Session::class => Session::class,
    ];
    
    /**
     * Aliases for services
     *
     * @var array
     */
    protected $aliases = [
        'session' => Session::class,
        'session.manager' => Session::class,
    ];

    /**
     * @var LoggerInterface
     */
    protected ?LoggerInterface $logger = null;


    
    /**
     * Register session services
     *
     * @return void
     */
    protected function registerServices()
    {
        var_dump('Inside SessionServiceProvider::registerServices()');
        // Register the Session class as a singleton
        $this->singleton('session', function ($app) {
            var_dump('Registering session singleton');
            $session = new Session();
            
            // Configure session with enhanced settings
            $this->configureSession($session, $app);
            
            return $session;
        });
        
        // Register session manager alias
        $this->singleton('session.manager', function ($app) {

            return $app->make('session');
        });

    }
    
    /**
     * Boot session services
     *
     * @return void
     */
    protected function bootServices()
    {
        // Configure PHP session settings
        $this->configurePhpSession();
        
        // Start the session
        $this->startSession();
        
        // Register session lifecycle handlers
        $this->registerSessionHandlers();
        
        // Set up session security
        $this->setupSessionSecurity();
    }
    
    /**
     * Configure the session instance
     *
     * @param Session $session
     * @param mixed $app
     * @return void
     */
    protected function configureSession(Session $session, $app)
    {
        // Set session configuration from config and environment
        $config = [
            'name' => $this->getSessionName(),
            'lifetime' => $this->getSessionLifetime(),
            'path' => $this->getSessionPath(),
            'domain' => $this->getSessionDomain(),
            'secure' => $this->isSessionSecure(),
            'httponly' => $this->isSessionHttpOnly(),
            'samesite' => $this->getSessionSameSite(),
        ];
        
        // Apply configuration if session supports it
        if (method_exists($session, 'configure')) {
            $session->configure($config);
        }
    }
    
    /**
     * Configure PHP session settings
     *
     * @return void
     */
    protected function configurePhpSession()
    {
        // Only configure if session hasn't started yet and headers haven't been sent
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                // Session name
                ini_set('session.name', $this->getSessionName());
            
            // Session lifetime
            ini_set('session.gc_maxlifetime', $this->getSessionLifetime());
            ini_set('session.cookie_lifetime', $this->getSessionLifetime());
            
            // Session path and domain
            ini_set('session.cookie_path', $this->getSessionPath());
            if ($domain = $this->getSessionDomain()) {
                ini_set('session.cookie_domain', $domain);
            }
            
            // Security settings
            ini_set('session.cookie_secure', $this->isSessionSecure() ? '1' : '0');
            ini_set('session.cookie_httponly', $this->isSessionHttpOnly() ? '1' : '0');
            ini_set('session.cookie_samesite', $this->getSessionSameSite());
            
            // Additional security settings
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            
            // Session save path
            $savePath = $this->getSessionSavePath();
            if ($savePath && is_dir($savePath) && is_writable($savePath)) {
                ini_set('session.save_path', $savePath);
            }
            
                // Garbage collection
                ini_set('session.gc_probability', '1');
                ini_set('session.gc_divisor', '100');
            } else {
                var_dump('Session already started or headers sent. Skipping configuration.');
            }
    }
    
    /**
     * Start the session
     *
     * @return void
     */
    protected function startSession()
    {
        var_dump('Inside startSession()');
        // Start the session if it hasn't been started yet
        if (session_status() === PHP_SESSION_NONE) {
            var_dump('Session not started, calling session_start()');
            session_start();
            var_dump('session_start() called');
        } else {
            var_dump('Session already started. Status: ' . session_status());
        }
    }
    
    /**
     * Register session lifecycle handlers
     *
     * @return void
     */
    protected function registerSessionHandlers()
    {
        // Register custom session handlers if needed
    }
    
    /**
     * Set up session security measures
     *
     * @return void
     */
    protected function setupSessionSecurity()
    {
        // Implement session security measures (e.g., CSRF protection, session fixation prevention)
    }
    
    /**
     * Set up CSRF protection
     *
     * @return void
     */
    protected function setupCsrfProtection()
    {
        $session = $this->make('session');
        
        if (method_exists($session, 'generateCsrfToken')) {
            $session->generateCsrfToken();
        } elseif (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Set up session timeout
     *
     * @param int $timeout
     * @return void
     */
    protected function setupSessionTimeout(int $timeout)
    {
        $session = $this->make('session');
        
        if (method_exists($session, 'setTimeout')) {
            $session->setTimeout($timeout);
        } else {
            $_SESSION['_timeout'] = time() + $timeout;
        }
    }
    
    /**
     * Get session name
     *
     * @return string
     */
    protected function getSessionName(): string
    {
        return $this->config('session.name', $this->env('SESSION_NAME', 'cosmichub_session'));
    }
    
    /**
     * Get session lifetime
     *
     * @return int
     */
    protected function getSessionLifetime(): int
    {
        return (int) $this->config('session.lifetime', $this->env('SESSION_LIFETIME', 7200));
    }
    
    /**
     * Get session path
     *
     * @return string
     */
    protected function getSessionPath(): string
    {
        return $this->config('session.path', $this->env('SESSION_PATH', '/'));
    }
    
    /**
     * Get session domain
     *
     * @return string|null
     */
    protected function getSessionDomain(): ?string
    {
        return $this->config('session.domain', $this->env('SESSION_DOMAIN'));
    }
    
    /**
     * Check if session should be secure
     *
     * @return bool
     */
    protected function isSessionSecure(): bool
    {
        return (bool) $this->config('session.secure', $this->env('SESSION_SECURE', false));
    }
    
    /**
     * Check if session should be HTTP only
     *
     * @return bool
     */
    protected function isSessionHttpOnly(): bool
    {
        return (bool) $this->config('session.httponly', $this->env('SESSION_HTTP_ONLY', true));
    }
    
    /**
     * Get session SameSite setting
     *
     * @return string
     */
    protected function getSessionSameSite(): string
    {
        return $this->config('session.samesite', $this->env('SESSION_SAME_SITE', 'Lax'));
    }
    
    /**
     * Get session save path
     *
     * @return string|null
     */
    protected function getSessionSavePath(): ?string
    {
        $path = $this->config('session.save_path', $this->env('SESSION_SAVE_PATH'));
        
        if (!$path) {
            $path = $this->app->getBasePath() . '/storage/sessions';
            
            // Create directory if it doesn't exist
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
        }
        
        return $path;
    }
    
    /**
     * Check if session ID should be regenerated
     *
     * @return bool
     */
    protected function shouldRegenerateSessionId(): bool
    {
        $regenerateInterval = $this->config('session.regenerate_interval', 300); // 5 minutes
        $lastRegeneration = $_SESSION['_last_regeneration'] ?? 0;
        
        return (time() - $lastRegeneration) > $regenerateInterval;
    }
    
    /**
     * Regenerate session ID
     *
     * @return void
     */
    protected function regenerateSessionId()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['_last_regeneration'] = time();
        }
    }
}