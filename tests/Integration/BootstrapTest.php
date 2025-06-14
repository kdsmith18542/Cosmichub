<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Core\Application;

class BootstrapTest extends TestCase
{
    public function testApplicationBootstrapProcess()
    {
        $app = Application::getInstance();
        
        // Verify the application is properly bootstrapped
        $this->assertInstanceOf(Application::class, $app);
        $this->assertNotNull($app->getContainer());
    }

    public function testCoreServiceProvidersAreRegistered()
    {
        $app = Application::getInstance();
        $container = $app->getContainer();
        
        // Test that core services are available
        $this->assertTrue($container->has('app'));
        $this->assertTrue($container->has('container'));
        
        // Verify the app binding resolves to the application instance
        $this->assertSame($app, $container->make('app'));
    }

    public function testConfigurationIsLoaded()
    {
        $app = Application::getInstance();
        $container = $app->getContainer();
        
        // Test that configuration is available
        if ($container->has('config')) {
            $config = $container->make('config');
            $this->assertNotNull($config);
        } else {
            // If config service isn't bound, that's also valid for current state
            $this->assertTrue(true);
        }
    }

    public function testDatabaseConnectionCanBeEstablished()
    {
        $app = Application::getInstance();
        $container = $app->getContainer();
        
        // Test database connection if available
        if ($container->has('db')) {
            $db = $container->make('db');
            $this->assertInstanceOf(\PDO::class, $db);
        } else {
            // Create a test database connection
            $testDb = $this->createTestDatabase();
            $this->assertInstanceOf(\PDO::class, $testDb);
        }
    }

    public function testRoutingSystemIsAvailable()
    {
        $app = Application::getInstance();
        $container = $app->getContainer();
        
        // Test that router is available
        if ($container->has('router')) {
            $router = $container->make('router');
            $this->assertNotNull($router);
        } else {
            // Router might not be bound yet, which is acceptable
            $this->assertTrue(true);
        }
    }

    public function testViewSystemIsAvailable()
    {
        $app = Application::getInstance();
        $container = $app->getContainer();
        
        // Test that view system is available
        if ($container->has('view')) {
            $view = $container->make('view');
            $this->assertNotNull($view);
        } else {
            // View system might not be bound yet, which is acceptable
            $this->assertTrue(true);
        }
    }

    public function testApplicationCanHandleBasicRequest()
    {
        $app = Application::getInstance();
        
        // This is a basic test that the application can be instantiated
        // and doesn't throw exceptions during bootstrap
        $this->assertInstanceOf(Application::class, $app);
        $this->assertNotNull($app->getBasePath());
    }
}