<?php

namespace Tests\Unit\Core;

use Tests\TestCase;
use App\Core\Application;
use App\Core\Container\Container;
use App\Core\ServiceProvider;

/**
 * Test cases for the Application class
 */
class ApplicationTest extends TestCase
{
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application();
    }
    
    public function testGetInstance()
    {
        $instance1 = Application::getInstance();
        $instance2 = Application::getInstance();
        
        $this->assertInstanceOf(Application::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }
    
    public function testSetBasePath()
    {
        $basePath = '/test/path';
        $this->app->setBasePath($basePath);
        
        $this->assertEquals($basePath, $this->app->basePath());
    }
    
    public function testPath()
    {
        $this->app->setBasePath('/test');
        
        $this->assertEquals('/test/app', $this->app->path());
        $this->assertEquals('/test/app/Models', $this->app->path('Models'));
    }
    
    public function testConfigPath()
    {
        $this->app->setBasePath('/test');
        
        $this->assertEquals('/test/config', $this->app->configPath());
        $this->assertEquals('/test/config/app.php', $this->app->configPath('app.php'));
    }
    
    public function testStoragePath()
    {
        $this->app->setBasePath('/test');
        
        $this->assertEquals('/test/storage', $this->app->storagePath());
        $this->assertEquals('/test/storage/logs', $this->app->storagePath('logs'));
    }
    
    public function testPublicPath()
    {
        $this->app->setBasePath('/test');
        
        $this->assertEquals('/test/public', $this->app->publicPath());
        $this->assertEquals('/test/public/assets', $this->app->publicPath('assets'));
    }
    
    public function testGetContainer()
    {
        $container = $this->app->getContainer();
        
        $this->assertInstanceOf(Container::class, $container);
        $this->assertSame($container, $this->app->getContainer());
    }
    
    public function testRegisterServiceProvider()
    {
        $provider = new TestServiceProvider($this->app);
        
        $this->app->register($provider);
        
        $this->assertTrue($provider->registered);
    }
    
    public function testRegisterServiceProviderByClass()
    {
        $this->app->register(TestServiceProvider::class);
        
        $container = $this->app->getContainer();
        $this->assertTrue($container->bound('test_service'));
    }
    
    public function testPreventDoubleRegistration()
    {
        $provider = new TestServiceProvider($this->app);
        
        $this->app->register($provider);
        $this->app->register($provider); // Should not register twice
        
        $this->assertEquals(1, $provider->registerCount);
    }
    
    public function testBootServiceProviders()
    {
        $provider = new TestServiceProvider($this->app);
        $this->app->register($provider);
        
        $this->app->boot();
        
        $this->assertTrue($provider->booted);
    }
    
    public function testPreventDoubleBoot()
    {
        $provider = new TestServiceProvider($this->app);
        $this->app->register($provider);
        
        $this->app->boot();
        $this->app->boot(); // Should not boot twice
        
        $this->assertEquals(1, $provider->bootCount);
    }
    
    public function testBootstrap()
    {
        $this->app->bootstrap();
        
        // Should register core service providers
        $container = $this->app->getContainer();
        $this->assertTrue($container->bound('app'));
        $this->assertTrue($container->bound('container'));
    }
    
    public function testRun()
    {
        // Mock request and router
        $container = $this->app->getContainer();
        
        $container->bind('request', function() {
            return new MockRequest();
        });
        
        $container->bind('router', function() {
            return new MockRouter();
        });
        
        $response = $this->app->run();
        
        $this->assertInstanceOf(MockResponse::class, $response);
    }
    
    public function testGetConfig()
    {
        $container = $this->app->getContainer();
        $container->bind('config', function() {
            return new MockConfig(['app.name' => 'Test App']);
        });
        
        $config = $this->app->config();
        $this->assertInstanceOf(MockConfig::class, $config);
    }
    
    public function testGetRouter()
    {
        $container = $this->app->getContainer();
        $container->bind('router', function() {
            return new MockRouter();
        });
        
        $router = $this->app->router();
        $this->assertInstanceOf(MockRouter::class, $router);
    }
    
    public function testEnvironmentDetection()
    {
        // Test default environment
        $this->assertEquals('production', $this->app->environment());
        
        // Test specific environment check
        $this->assertTrue($this->app->environment('production'));
        $this->assertFalse($this->app->environment('development'));
        
        // Test multiple environment check
        $this->assertTrue($this->app->environment(['production', 'staging']));
        $this->assertFalse($this->app->environment(['development', 'testing']));
    }
    
    public function testIsLocal()
    {
        $this->assertFalse($this->app->isLocal());
    }
    
    public function testIsProduction()
    {
        $this->assertTrue($this->app->isProduction());
    }
    
    public function testIsTesting()
    {
        $this->assertFalse($this->app->isTesting());
    }
    
    public function testVersion()
    {
        $version = $this->app->version();
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }
    
    public function testTerminate()
    {
        $provider = new TestServiceProvider($this->app);
        $this->app->register($provider);
        $this->app->boot();
        
        $request = new MockRequest();
        $response = new MockResponse();
        
        $this->app->terminate($request, $response);
        
        $this->assertTrue($provider->terminated);
    }
    
    public function testMakeMethod()
    {
        $this->app->getContainer()->bind('test', function() {
            return 'test_value';
        });
        
        $result = $this->app->make('test');
        $this->assertEquals('test_value', $result);
    }
    
    public function testCallMethod()
    {
        $result = $this->app->call(function($value = 'default') {
            return $value;
        }, ['value' => 'custom']);
        
        $this->assertEquals('custom', $result);
    }
    
    public function testResolvedMethod()
    {
        $called = false;
        
        $this->app->resolved('test', function() use (&$called) {
            $called = true;
        });
        
        $this->app->getContainer()->bind('test', 'value');
        $this->app->make('test');
        
        $this->assertTrue($called);
    }
    
    public function testBindMethod()
    {
        $this->app->bind('test', 'value');
        
        $this->assertTrue($this->app->bound('test'));
        $this->assertEquals('value', $this->app->make('test'));
    }
    
    public function testSingletonMethod()
    {
        $this->app->singleton('test', function() {
            return new \stdClass();
        });
        
        $instance1 = $this->app->make('test');
        $instance2 = $this->app->make('test');
        
        $this->assertSame($instance1, $instance2);
    }
    
    public function testInstanceMethod()
    {
        $instance = new \stdClass();
        $this->app->instance('test', $instance);
        
        $retrieved = $this->app->make('test');
        $this->assertSame($instance, $retrieved);
    }
}

// Test service provider
class TestServiceProvider extends ServiceProvider
{
    public $registered = false;
    public $booted = false;
    public $terminated = false;
    public $registerCount = 0;
    public $bootCount = 0;
    
    public function register()
    {
        $this->registered = true;
        $this->registerCount++;
        
        $this->app->bind('test_service', function() {
            return 'test_value';
        });
    }
    
    public function boot()
    {
        $this->booted = true;
        $this->bootCount++;
    }
    
    public function terminate($request, $response)
    {
        $this->terminated = true;
    }
}

// Mock classes for testing
class MockRequest
{
    public function getMethod()
    {
        return 'GET';
    }
    
    public function getUri()
    {
        return '/';
    }
}

class MockRouter
{
    public function dispatch($request)
    {
        return new MockResponse();
    }
}

class MockResponse
{
    public function send()
    {
        return $this;
    }
}

class MockConfig
{
    private $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    public function get($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}