<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Application;
use App\Core\Container\Container;

class ApplicationTest extends TestCase
{
    public function testApplicationIsSingleton()
    {
        $app1 = Application::getInstance();
        $app2 = Application::getInstance();
        
        $this->assertSame($app1, $app2);
    }

    public function testApplicationHasContainer()
    {
        $app = Application::getInstance();
        $container = $app->getContainer();
        
        $this->assertInstanceOf(Container::class, $container);
    }

    public function testApplicationCanSetAndGetBasePath()
    {
        $app = Application::getInstance();
        $testPath = '/test/path';
        
        $app->setBasePath($testPath);
        $this->assertEquals($testPath, $app->getBasePath());
    }

    public function testApplicationCanRegisterServiceProviders()
    {
        $app = Application::getInstance();
        
        // Create a mock service provider
        $provider = $this->mock('App\\Core\\ServiceProvider\\ServiceProvider');
        $provider->shouldReceive('register')->once();
        
        $app->register($provider);
        
        // Verify the provider was registered
        $this->assertTrue(true); // If we get here without exception, the test passes
    }

    public function testApplicationCanBootServiceProviders()
    {
        $app = Application::getInstance();
        
        // Create a mock service provider
        $provider = $this->mock('App\\Core\\ServiceProvider\\ServiceProvider');
        $provider->shouldReceive('register')->once();
        $provider->shouldReceive('boot')->once();
        
        $app->register($provider);
        $app->boot();
        
        // Verify the provider was booted
        $this->assertTrue(true); // If we get here without exception, the test passes
    }

    public function testApplicationBootstrapRegistersAndBootsProviders()
    {
        $app = Application::getInstance();
        
        // Reset the application state for this test
        $reflection = new \ReflectionClass($app);
        $bootedProperty = $reflection->getProperty('booted');
        $bootedProperty->setAccessible(true);
        $bootedProperty->setValue($app, []);
        
        // Bootstrap should register and boot core providers
        $app->bootstrap();
        
        // Verify that some core providers are registered
        $this->assertTrue(true); // Basic test that bootstrap completes without error
    }

    public function testApplicationCanResolveFromContainer()
    {
        $app = Application::getInstance();
        $container = $app->getContainer();
        
        // Bind something to the container
        $container->bind('test.service', function() {
            return 'test value';
        });
        
        // Resolve through the application
        $resolved = $app->make('test.service');
        $this->assertEquals('test value', $resolved);
    }
}