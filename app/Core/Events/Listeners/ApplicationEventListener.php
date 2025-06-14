<?php

namespace App\Core\Events\Listeners;

use App\Core\Events\Event;
use App\Core\Application;
use Psr\Log\LoggerInterface;

/**
 * Application Event Listener
 *
 * This listener handles application lifecycle events and demonstrates
 * the enhanced event system capabilities following the refactoring plan.
 */
class ApplicationEventListener
{
    /**
     * The application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * The logger instance
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Create a new application event listener
     *
     * @param Application $app
     * @param LoggerInterface $logger
     */
    public function __construct(Application $app, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    /**
     * Handle application booting event
     *
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    public function onApplicationBooting($event, $payload = null)
    {
        $this->log('Application is booting', [
            'event' => $event,
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
        ]);

        // Perform any pre-boot initialization
        $this->initializeApplicationServices();
    }

    /**
     * Handle application booted event
     *
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    public function onApplicationBooted($event, $payload = null)
    {
        $this->log('Application has booted', [
            'event' => $event,
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'services_registered' => $this->getRegisteredServicesCount(),
        ]);

        // Perform any post-boot tasks
        $this->finalizeApplicationSetup();
    }

    /**
     * Handle request received event
     *
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    public function onRequestReceived($event, $payload = null)
    {
        $this->log('Request received', [
            'event' => $event,
            'timestamp' => microtime(true),
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
        ]);

        // Track request metrics
        $this->trackRequestMetrics();
    }

    /**
     * Handle response sending event
     *
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    public function onResponseSending($event, $payload = null)
    {
        $this->log('Response sending', [
            'event' => $event,
            'timestamp' => microtime(true),
            'memory_peak' => memory_get_peak_usage(true),
        ]);

        // Perform cleanup tasks
        $this->performCleanup();
    }

    /**
     * Handle exception occurred event
     *
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    public function onExceptionOccurred($event, $payload = null)
    {
        $exception = $payload instanceof \Exception ? $payload : null;
        
        $this->log('Exception occurred', [
            'event' => $event,
            'timestamp' => microtime(true),
            'exception_class' => $exception ? get_class($exception) : 'Unknown',
            'exception_message' => $exception ? $exception->getMessage() : 'Unknown error',
            'exception_file' => $exception ? $exception->getFile() : 'Unknown',
            'exception_line' => $exception ? $exception->getLine() : 'Unknown',
        ], 'error');

        // Handle exception reporting
        $this->reportException($exception);
    }

    /**
     * Handle PSR-14 compliant events
     *
     * @param Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $eventName = $event->getName();
        $eventData = $event->toArray();

        $this->log('PSR-14 Event handled', [
            'event_name' => $eventName,
            'event_data' => $eventData,
            'timestamp' => microtime(true),
        ]);

        // Handle specific event types
        $this->handleSpecificEvent($event);
    }

    /**
     * Initialize application services
     *
     * @return void
     */
    protected function initializeApplicationServices()
    {
        // Initialize any required services during boot
        // This could include setting up error handlers, etc.
    }

    /**
     * Finalize application setup
     *
     * @return void
     */
    protected function finalizeApplicationSetup()
    {
        // Perform any final setup tasks
        // This could include warming caches, etc.
    }

    /**
     * Track request metrics
     *
     * @return void
     */
    protected function trackRequestMetrics()
    {
        // Track request metrics for monitoring
        // This could include response times, memory usage, etc.
    }

    /**
     * Perform cleanup tasks
     *
     * @return void
     */
    protected function performCleanup()
    {
        // Perform any cleanup tasks
        // This could include closing connections, clearing temporary data, etc.
    }

    /**
     * Report exception
     *
     * @param \Exception|null $exception
     * @return void
     */
    protected function reportException(?\Exception $exception)
    {
        if (!$exception) {
            return;
        }

        // Report exception to monitoring systems
        // This could include error tracking services, etc.
    }

    /**
     * Handle specific event types
     *
     * @param Event $event
     * @return void
     */
    protected function handleSpecificEvent(Event $event)
    {
        // Handle specific event types based on their class
        $eventClass = get_class($event);
        
        // Add specific handling logic here
    }

    /**
     * Get count of registered services
     *
     * @return int
     */
    protected function getRegisteredServicesCount(): int
    {
        try {
            $container = $this->app->getContainer();
            return method_exists($container, 'getRegisteredServices') 
                ? count($container->getRegisteredServices()) 
                : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Log a message
     *
     * @param string $message
     * @param array $context
     * @param string $level
     * @return void
     */
    protected function log(string $message, array $context = [], string $level = 'info')
    {
        $this->logger->log($level, $message, $context);
    }
}