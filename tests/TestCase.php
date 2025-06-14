<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Core\Application;
use App\Core\Container\Container;
use Mockery;

/**
 * Base test case for all tests
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Container
     */
    protected $container;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->createApplication();
        $this->container = $this->app->getContainer();
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create the application instance
     *
     * @return Application
     */
    protected function createApplication(): Application
    {
        if (!$this->app) {
            // Set testing environment
            $_ENV['APP_ENV'] = 'testing';
            $_ENV['APP_DEBUG'] = 'true';
            
            $this->app = Application::getInstance();
            
            // Bootstrap the application if not already done
            if (!$this->app->isBooted()) {
                $this->app->bootstrap();
            }
        }
        return $this->app;
    }

    /**
     * Get a mock object for the given class
     */
    protected function mock(string $class)
    {
        return Mockery::mock($class);
    }

    /**
     * Get a partial mock object for the given class
     */
    protected function partialMock(string $class, array $methods = [])
    {
        return Mockery::mock($class)->makePartial();
    }

    /**
     * Assert that a string contains a substring (case-insensitive)
     * Note: PHPUnit provides this method natively, so we use it directly
     */
    protected function assertStringContainsIgnoringCase(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertStringContainsString(
            strtolower($needle),
            strtolower($haystack),
            $message
        );
    }

    /**
     * Create a temporary database for testing
     */
    protected function createTestDatabase(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        
        return $pdo;
    }

    /**
     * Get the application instance
     */
    protected function app(): Application
    {
        return $this->app;
    }

    /**
     * Get the container instance
     */
    protected function container(): Container
    {
        return $this->container;
    }
}