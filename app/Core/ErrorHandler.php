<?php

namespace App\Core;

use App\Core\Logging\Logger;
use App\Core\Http\Response;
use Exception;
use Throwable;
use ErrorException;

/**
 * Enhanced Error Handler
 * 
 * Provides centralized exception handling, structured logging, and error response formatting
 * as part of Phase 1 of the refactoring plan.
 */
class ErrorHandler
{
    /**
     * @var Logger The logger instance
     */
    protected $logger;
    
    /**
     * @var bool Whether debug mode is enabled
     */
    protected $debug;
    
    /**
     * @var array Error levels mapping
     */
    protected $errorLevels = [
        E_ERROR => 'error',
        E_WARNING => 'warning',
        E_PARSE => 'error',
        E_NOTICE => 'notice',
        E_CORE_ERROR => 'error',
        E_CORE_WARNING => 'warning',
        E_COMPILE_ERROR => 'error',
        E_COMPILE_WARNING => 'warning',
        E_USER_ERROR => 'error',
        E_USER_WARNING => 'warning',
        E_USER_NOTICE => 'notice',
        E_STRICT => 'notice',
        E_RECOVERABLE_ERROR => 'error',
        E_DEPRECATED => 'notice',
        E_USER_DEPRECATED => 'notice',
    ];
    
    /**
     * Constructor
     * 
     * @param Logger|null $logger
     * @param bool $debug
     */
    public function __construct(Logger $logger = null, bool $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }
    
    /**
     * Register the error handler
     * 
     * @return void
     */
    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     * 
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @return bool
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }
        
        $logLevel = $this->errorLevels[$level] ?? 'error';
        
        $context = [
            'level' => $level,
            'file' => $file,
            'line' => $line,
            'error_type' => $this->getErrorTypeName($level)
        ];
        
        if ($this->logger) {
            $this->logger->log($logLevel, $message, $context);
        }
        
        // Convert errors to exceptions in debug mode
        if ($this->debug && in_array($level, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     * 
     * @param Throwable $exception
     * @return void
     */
    public function handleException(Throwable $exception): void
    {
        $this->logException($exception);
        $this->renderException($exception);
    }
    
    /**
     * Handle fatal errors during shutdown
     * 
     * @return void
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            
            $this->handleException($exception);
        }
    }
    
    /**
     * Log an exception
     * 
     * @param Throwable $exception
     * @return void
     */
    protected function logException(Throwable $exception): void
    {
        if (!$this->logger) {
            return;
        }
        
        $context = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode()
        ];
        
        $this->logger->error($exception->getMessage(), $context);
    }
    
    /**
     * Render an exception response
     * 
     * @param Throwable $exception
     * @return void
     */
    protected function renderException(Throwable $exception): void
    {
        $statusCode = $this->getStatusCode($exception);
        
        if ($this->isAjaxRequest()) {
            $this->renderJsonError($exception, $statusCode);
        } else {
            $this->renderHtmlError($exception, $statusCode);
        }
    }
    
    /**
     * Render JSON error response
     * 
     * @param Throwable $exception
     * @param int $statusCode
     * @return void
     */
    protected function renderJsonError(Throwable $exception, int $statusCode): void
    {
        $data = [
            'error' => true,
            'message' => $this->debug ? $exception->getMessage() : 'An error occurred',
            'status_code' => $statusCode
        ];
        
        if ($this->debug) {
            $data['exception'] = get_class($exception);
            $data['file'] = $exception->getFile();
            $data['line'] = $exception->getLine();
            $data['trace'] = $exception->getTrace();
        }
        
        header('Content-Type: application/json', true, $statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Render HTML error response
     * 
     * @param Throwable $exception
     * @param int $statusCode
     * @return void
     */
    protected function renderHtmlError(Throwable $exception, int $statusCode): void
    {
        http_response_code($statusCode);
        
        if ($this->debug) {
            $this->renderDebugError($exception);
        } else {
            $this->renderProductionError($statusCode);
        }
    }
    
    /**
     * Render debug error page
     * 
     * @param Throwable $exception
     * @return void
     */
    protected function renderDebugError(Throwable $exception): void
    {
        echo "<!DOCTYPE html>\n";
        echo "<html>\n";
        echo "<head>\n";
        echo "<title>Error - " . htmlspecialchars(get_class($exception)) . "</title>\n";
        echo "<style>\n";
        echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n";
        echo ".error-container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }\n";
        echo ".error-title { color: #d32f2f; font-size: 24px; margin-bottom: 10px; }\n";
        echo ".error-message { font-size: 16px; margin-bottom: 20px; }\n";
        echo ".error-details { background: #f9f9f9; padding: 15px; border-radius: 3px; font-family: monospace; }\n";
        echo ".trace { background: #f0f0f0; padding: 10px; border-radius: 3px; margin-top: 10px; white-space: pre-wrap; }\n";
        echo "</style>\n";
        echo "</head>\n";
        echo "<body>\n";
        echo "<div class='error-container'>\n";
        echo "<h1 class='error-title'>" . htmlspecialchars(get_class($exception)) . "</h1>\n";
        echo "<div class='error-message'>" . htmlspecialchars($exception->getMessage()) . "</div>\n";
        echo "<div class='error-details'>\n";
        echo "<strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "<br>\n";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>\n";
        echo "<strong>Code:</strong> " . $exception->getCode() . "\n";
        echo "</div>\n";
        echo "<div class='trace'>" . htmlspecialchars($exception->getTraceAsString()) . "</div>\n";
        echo "</div>\n";
        echo "</body>\n";
        echo "</html>\n";
    }
    
    /**
     * Render production error page
     * 
     * @param int $statusCode
     * @return void
     */
    protected function renderProductionError(int $statusCode): void
    {
        $message = $this->getStatusMessage($statusCode);
        
        echo "<!DOCTYPE html>\n";
        echo "<html>\n";
        echo "<head>\n";
        echo "<title>Error {$statusCode}</title>\n";
        echo "<style>\n";
        echo "body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; background: #f5f5f5; }\n";
        echo ".error-container { display: inline-block; background: white; padding: 40px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }\n";
        echo ".error-code { font-size: 72px; color: #d32f2f; margin: 0; }\n";
        echo ".error-message { font-size: 18px; color: #666; margin: 20px 0; }\n";
        echo "</style>\n";
        echo "</head>\n";
        echo "<body>\n";
        echo "<div class='error-container'>\n";
        echo "<h1 class='error-code'>{$statusCode}</h1>\n";
        echo "<p class='error-message'>{$message}</p>\n";
        echo "</div>\n";
        echo "</body>\n";
        echo "</html>\n";
    }
    
    /**
     * Get HTTP status code for exception
     * 
     * @param Throwable $exception
     * @return int
     */
    protected function getStatusCode(Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }
        
        return 500;
    }
    
    /**
     * Get status message for HTTP code
     * 
     * @param int $statusCode
     * @return string
     */
    protected function getStatusMessage(int $statusCode): string
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Page Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];
        
        return $messages[$statusCode] ?? 'An error occurred';
    }
    
    /**
     * Get error type name
     * 
     * @param int $level
     * @return string
     */
    protected function getErrorTypeName(int $level): string
    {
        $types = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];
        
        return $types[$level] ?? 'UNKNOWN';
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Set debug mode
     * 
     * @param bool $debug
     * @return void
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }
    
    /**
     * Set logger
     * 
     * @param Logger $logger
     * @return void
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }
}