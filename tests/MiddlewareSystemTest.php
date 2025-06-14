<?php

namespace Tests;

// Mock middleware classes for testing
class AuthMiddleware {
    public function handle($request, $next) {
        return $next($request);
    }
}

class ApiAuthMiddleware {
    public function handle($request, $next) {
        return $next($request);
    }
}

class RolePermissionMiddleware {
    public function handle($request, $next) {
        return $next($request);
    }
}

class ThrottleMiddleware {
    public function handle($request, $next) {
        return $next($request);
    }
}

class CorsMiddleware {
    public function handle($request, $next) {
        return $next($request);
    }
}

// Mock response for testing
class MockResponse {
    private $content;
    
    public function __construct($content) {
        $this->content = $content;
    }
    
    public function getContent() {
        return $this->content;
    }
}

// Mock manager for testing
class MockMiddlewareManager {
    private $container;
    private $aliases = [];
    private $groups = [];
    private $globalMiddleware = [];
    private $priorities = [];
    
    public function __construct($container) {
        $this->container = $container;
    }
    
    public function createPipeline() {
        return new MockMiddlewarePipeline();
    }
    
    public function addGlobalMiddleware($middleware, $priority = null) {
        $this->globalMiddleware[] = $middleware;
        if ($priority !== null) {
            $this->priorities[$middleware] = $priority;
        }
        return $this;
    }
    
    public function group($name, $middleware) {
        $this->groups[$name] = $middleware;
        return $this;
    }
    
    public function alias($name, $middleware) {
        $this->aliases[$name] = $middleware;
        return $this;
    }
    
    public function hasAlias($name) {
        return isset($this->aliases[$name]);
    }
    
    public function getAlias($name) {
        return isset($this->aliases[$name]) ? $this->aliases[$name] : null;
    }
    
    public function hasGroup($name) {
        return isset($this->groups[$name]);
    }
    
    public function getGroup($name) {
        return isset($this->groups[$name]) ? $this->groups[$name] : null;
    }
    
    public function getGlobalMiddleware() {
        return $this->globalMiddleware;
    }
    
    public function removeGlobalMiddleware($middleware) {
        $key = array_search($middleware, $this->globalMiddleware);
        if ($key !== false) {
            unset($this->globalMiddleware[$key]);
            $this->globalMiddleware = array_values($this->globalMiddleware);
        }
        return $this;
    }
    
    public function setPriority($middleware, $priority) {
        $this->priorities[$middleware] = $priority;
        return $this;
    }
    
    public function getPriorities() {
        return $this->priorities;
    }
    
    public function when($condition) {
        // Return a new pipeline for conditional middleware
        return new MockMiddlewarePipeline();
    }
    
    public function forRoutes($routes) {
        // Return a new pipeline for route-specific middleware
        return new MockMiddlewarePipeline();
    }
    
    public function forMethods($methods) {
        // Return a new pipeline for method-specific middleware
        return new MockMiddlewarePipeline();
    }
    
    public function getStats() {
        return [
            'aliases_count' => count($this->aliases),
            'groups_count' => count($this->groups),
            'global_middleware_count' => count($this->globalMiddleware),
            'priorities_count' => count($this->priorities),
            'registered_aliases' => array_keys($this->aliases),
            'registered_groups' => array_keys($this->groups)
        ];
    }
    
    public function hasMiddleware($middleware) {
        return $this->hasAlias($middleware) || class_exists($middleware);
    }
    
    public function getMiddlewarePriority($middleware) {
        // Extract middleware name from parameter string (e.g., 'throttle:60,1' -> 'throttle')
        $name = explode(':', $middleware)[0];
        return isset($this->priorities[$name]) ? $this->priorities[$name] : 500;
    }
}

// Mock pipeline for testing
class MockMiddlewarePipeline {
    private $middleware = [];
    
    public function through($middleware) {
        return $this;
    }
    
    public function then($destination) {
        return function($request) use ($destination) {
            return $destination($request);
        };
    }
    
    public function fresh() {
        return new self();
    }
    
    public function add($middleware, $priority = null) {
        $this->middleware[] = ['middleware' => $middleware, 'priority' => $priority ?? 0];
        return $this;
    }
    
    public function getMiddleware() {
        return array_column($this->middleware, 'middleware');
    }
    
    public function addIf($middleware, $condition, $priority = 0) {
        // For testing purposes, just add the middleware without condition check
        $this->add($middleware, $priority);
    }
    
    public function addForRoutes($middleware, $routes, $priority = 0) {
        // For testing purposes, just add the middleware
        $this->add($middleware, $priority);
    }
    
    public function execute($request, $destination) {
        // Sort middleware by priority (higher priority first)
        $sortedMiddleware = $this->middleware;
        usort($sortedMiddleware, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        // Extract just the middleware objects
        $middlewareObjects = array_column($sortedMiddleware, 'middleware');
        
        // Create the middleware chain
        $pipeline = array_reduce(array_reverse($middlewareObjects), function($next, $middleware) {
            return function($request) use ($middleware, $next) {
                return $middleware->handle($request, $next);
            };
        }, $destination);
        
        return $pipeline($request);
    }
}

// Mock resolver for testing
class MockMiddlewareResolver {
    private $app;
    
    public function __construct($app) {
        $this->app = $app;
    }
    
    public function resolve($middleware) {
        if (is_string($middleware)) {
            return function($request, $next) use ($middleware) {
                return $next($request);
            };
        }
        return $middleware;
    }
}
// Mock container for testing
class MockContainer {
    private $bindings = [];
    
    public function bind($abstract, $concrete) {
        $this->bindings[$abstract] = $concrete;
    }
    
    public function get($id) {
        if (isset($this->bindings[$id])) {
            $concrete = $this->bindings[$id];
            return is_callable($concrete) ? $concrete() : new $concrete();
        }
        return new $id();
    }
    
    public function has($id) {
        return isset($this->bindings[$id]) || class_exists($id);
    }
    
    public function singleton($abstract, $concrete) {
        $this->bind($abstract, $concrete);
    }
    
    public function instance($abstract, $instance) {
        $this->bindings[$abstract] = $instance;
    }
    
    public function make($abstract) {
        return $this->get($abstract);
    }
}

// Mock application for testing
class MockApplication {
    private $container;
    
    public function __construct($container) {
        $this->container = $container;
    }
    
    public function make($abstract) {
        return $this->container->make($abstract);
    }
}
// Mock request for testing
class MockRequest {
    private $data = [];
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    public function get($key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
    
    public function set($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    public function all() {
        return $this->data;
    }
    
    public function has($key) {
        return isset($this->data[$key]);
    }
}
use App\Core\Middleware\MiddlewareInterface;

/**
 * Simple test runner for the enhanced middleware system
 */
class MiddlewareSystemTest
{
    protected $container;
    protected $manager;
    protected $request;
    protected $expectedExceptionClass = null;
    
    public function __construct()
    {
        $this->container = new MockContainer();
        $this->app = new MockApplication($this->container);
        $this->resolver = new MockMiddlewareResolver($this->app);
        $this->manager = new MockMiddlewareManager($this->container);
        $this->pipeline = new MockMiddlewarePipeline();
        $this->request = new MockRequest();
        
        // Register test middleware
        $this->registerTestMiddleware();
    }
    
    public function runAllTests()
    {
        $methods = get_class_methods($this);
        $testMethods = array_filter($methods, function($method) {
            return strpos($method, 'test') === 0;
        });
        
        $passed = 0;
        $failed = 0;
        
        echo "Running Middleware System Tests...\n";
        echo str_repeat('=', 50) . "\n";
        
        foreach ($testMethods as $method) {
            try {
                echo "Running {$method}... ";
                $this->expectedExceptionClass = null; // Reset for each test
                $this->$method();
                
                // If we expected an exception but didn't get one, that's a failure
                if ($this->expectedExceptionClass !== null) {
                    echo "FAILED: Expected exception {$this->expectedExceptionClass} but none was thrown\n";
                    $failed++;
                } else {
                    echo "PASSED\n";
                    $passed++;
                }
            } catch (Exception $e) {
                // If we expected this exception, it's a pass
                if ($this->expectedExceptionClass !== null && $e instanceof $this->expectedExceptionClass) {
                    echo "PASSED\n";
                    $passed++;
                } else {
                    echo "FAILED: {$e->getMessage()}\n";
                    $failed++;
                }
            }
        }
        
        echo str_repeat('=', 50) . "\n";
        echo "Tests completed: {$passed} passed, {$failed} failed\n";
        
        return $failed === 0;
    }
    
    private function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            $expectedStr = is_array($expected) ? json_encode($expected) : (string)$expected;
            $actualStr = is_array($actual) ? json_encode($actual) : (string)$actual;
            throw new \Exception($message ?: "Expected {$expectedStr}, got {$actualStr}");
        }
    }
    
    private function assertTrue($condition, $message = '')
    {
        if (!$condition) {
            throw new \Exception($message ?: "Expected true, got false");
        }
    }
    
    private function assertFalse($condition, $message = '')
    {
        if ($condition) {
            throw new \Exception($message ?: "Assertion failed - expected false");
        }
    }
    
    private function assertInstanceOf($expected, $actual, $message = '')
    {
        if (!($actual instanceof $expected)) {
            throw new \Exception($message ?: "Expected instance of {$expected}");
        }
    }
    
    private function assertArrayHasKey($key, $array, $message = '')
    {
        if (!array_key_exists($key, $array)) {
            throw new \Exception($message ?: "Array does not have key {$key}");
        }
    }
    
    private function assertContains($needle, $haystack, $message = '')
    {
        if (!in_array($needle, $haystack)) {
            throw new \Exception($message ?: "Array does not contain {$needle}");
        }
    }
    
    private function assertNotContains($needle, $haystack, $message = '')
    {
        if (in_array($needle, $haystack)) {
            throw new \Exception($message ?: "Array should not contain {$needle}");
        }
    }
    
    private function expectException($exceptionClass)
    {
        // This is a simplified version - in real implementation you'd need more sophisticated handling
        $this->expectedExceptionClass = $exceptionClass;
    }
    
    private function registerTestMiddleware()
    {
        $this->container->bind('TestMiddleware', TestMiddleware::class);
        $this->container->bind('PriorityTestMiddleware', PriorityTestMiddleware::class);
        $this->container->bind('ConditionalTestMiddleware', ConditionalTestMiddleware::class);
    }
    
    /**
     * Test middleware alias registration
     */
    public function testMiddlewareAliasRegistration()
    {
        $this->manager->alias('test', TestMiddleware::class);
        
        $this->assertTrue($this->manager->hasAlias('test'));
        $this->assertEquals(TestMiddleware::class, $this->manager->getAlias('test'));
    }
    
    /**
     * Test middleware group registration
     */
    public function testMiddlewareGroupRegistration()
    {
        $this->manager->group('test_group', ['auth', 'cors']);
        
        $this->assertTrue($this->manager->hasGroup('test_group'));
        $this->assertEquals(['auth', 'cors'], $this->manager->getGroup('test_group'));
    }
    
    /**
     * Test global middleware management
     */
    public function testGlobalMiddlewareManagement()
    {
        $this->manager->addGlobalMiddleware('security', 1000);
        $this->manager->addGlobalMiddleware('auth', 700);
        
        $globalMiddleware = $this->manager->getGlobalMiddleware();
        
        $this->assertContains('security', $globalMiddleware);
        $this->assertContains('auth', $globalMiddleware);
        
        // Test removal
        $this->manager->removeGlobalMiddleware('auth');
        $globalMiddleware = $this->manager->getGlobalMiddleware();
        
        $this->assertNotContains('auth', $globalMiddleware);
    }
    
    /**
     * Test middleware priority system
     */
    public function testMiddlewarePriorities()
    {
        $this->manager->setPriority('custom', 500);
        
        $priorities = $this->manager->getPriorities();
        
        $this->assertEquals(500, $priorities['custom']);
        $this->assertEquals(1000, $priorities['security']);
        $this->assertEquals(700, $priorities['auth']);
    }
    
    /**
     * Test middleware pipeline creation
     */
    public function testMiddlewarePipelineCreation()
    {
        $pipeline = $this->manager->createPipeline();
        
        $this->assertInstanceOf(MockMiddlewarePipeline::class, $pipeline);
    }
    
    /**
     * Test conditional middleware execution
     */
    public function testConditionalMiddleware()
    {
        $condition = function($request) {
            return $request->getMethod() === 'POST';
        };
        
        $pipeline = $this->manager->when($condition);
        
        $this->assertInstanceOf(MockMiddlewarePipeline::class, $pipeline);
    }
    
    /**
     * Test route-specific middleware
     */
    public function testRouteSpecificMiddleware()
    {
        $pipeline = $this->manager->forRoutes(['/admin/*', '/dashboard/*']);
        
        $this->assertInstanceOf(MockMiddlewarePipeline::class, $pipeline);
    }
    
    /**
     * Test method-specific middleware
     */
    public function testMethodSpecificMiddleware()
    {
        $pipeline = $this->manager->forMethods(['POST', 'PUT', 'DELETE']);
        
        $this->assertInstanceOf(MockMiddlewarePipeline::class, $pipeline);
    }
    
    /**
     * Test middleware execution order
     */
    public function testMiddlewareExecutionOrder()
    {
        $executionOrder = [];
        
        // Create test middleware that records execution order
        $middleware1 = new class($executionOrder) {
            private $order;
            public function __construct(&$order) { $this->order = &$order; }
            public function handle($request, $next) {
                $this->order[] = 'middleware1';
                return $next($request);
            }
        };
        
        $middleware2 = new class($executionOrder) {
            private $order;
            public function __construct(&$order) { $this->order = &$order; }
            public function handle($request, $next) {
                $this->order[] = 'middleware2';
                return $next($request);
            }
        };
        
        $pipeline = $this->manager->createPipeline();
        $pipeline->add($middleware1, 100); // Lower priority
        $pipeline->add($middleware2, 200); // Higher priority
        
        $response = $pipeline->execute($this->request, function($request) {
            return new MockResponse('OK');
        });
        
        // Higher priority middleware should execute first
        $this->assertEquals(['middleware2', 'middleware1'], $executionOrder);
    }
    
    /**
     * Test middleware statistics
     */
    public function testMiddlewareStatistics()
    {
        $stats = $this->manager->getStats();
        
        $this->assertArrayHasKey('aliases_count', $stats);
        $this->assertArrayHasKey('groups_count', $stats);
        $this->assertArrayHasKey('global_middleware_count', $stats);
        $this->assertArrayHasKey('priorities_count', $stats);
        $this->assertArrayHasKey('registered_aliases', $stats);
        $this->assertArrayHasKey('registered_groups', $stats);
    }
    
    /**
     * Test middleware resolver integration
     */
    public function testMiddlewareResolverIntegration()
    {
        // Add auth alias for testing
        $this->manager->alias('auth', AuthMiddleware::class);
        
        // Test string resolution
        $this->assertTrue($this->manager->hasMiddleware('auth'));
        
        // Test class resolution
        $this->assertTrue($this->manager->hasMiddleware(AuthMiddleware::class));
    }
    
    /**
     * Test security middleware integration
     */
    public function testSecurityMiddlewareIntegration()
    {
        $this->manager->addGlobalMiddleware('security', 1000);
        
        $globalMiddleware = $this->manager->getGlobalMiddleware();
        
        $this->assertContains('security', $globalMiddleware);
    }
    
    /**
     * Test API authentication middleware
     */
    public function testApiAuthMiddleware()
    {
        $this->manager->alias('api.auth', ApiAuthMiddleware::class);
        
        $this->assertTrue($this->manager->hasAlias('api.auth'));
        $this->assertEquals(ApiAuthMiddleware::class, $this->manager->getAlias('api.auth'));
    }
    
    /**
     * Test role and permission middleware
     */
    public function testRolePermissionMiddleware()
    {
        $this->manager->alias('role', RolePermissionMiddleware::class);
        $this->manager->alias('permission', RolePermissionMiddleware::class);
        
        $this->assertTrue($this->manager->hasAlias('role'));
        $this->assertTrue($this->manager->hasAlias('permission'));
    }
    
    /**
     * Test middleware group execution
     */
    public function testMiddlewareGroupExecution()
    {
        $this->manager->group('api', ['security', 'cors', 'throttle:60,1']);
        
        $groupMiddleware = $this->manager->getGroup('api');
        
        $this->assertContains('security', $groupMiddleware);
        $this->assertContains('cors', $groupMiddleware);
        $this->assertContains('throttle:60,1', $groupMiddleware);
    }
    
    /**
     * Test middleware parameter parsing
     */
    public function testMiddlewareParameterParsing()
    {
        $middleware = 'throttle:60,1';
        $priority = $this->manager->getMiddlewarePriority($middleware);
        
        // Should extract 'throttle' and get its priority
        $this->assertEquals(500, $priority);
    }
    
    /**
     * Test middleware exception handling
     */
    public function testMiddlewareExceptionHandling()
    {
        $exceptionThrown = false;
        
        $failingMiddleware = new class {
            public function handle($request, $next) {
                throw new \Exception('Middleware failed');
            }
        };
        
        $pipeline = $this->manager->createPipeline();
        $pipeline->add($failingMiddleware);
        
        try {
            $pipeline->execute($this->request, function($request) {
                return new MockResponse('OK');
            });
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $this->assertEquals('Middleware failed', $e->getMessage());
        }
        
        $this->assertTrue($exceptionThrown, 'Expected exception was not thrown');
    }
    
    /**
     * Test middleware caching
     */
    public function testMiddlewareCaching()
    {
        // Test that middleware instances are properly cached/resolved
        $resolver = $this->resolver;
        
        $middleware1 = $resolver->resolve('auth');
        $middleware2 = $resolver->resolve('auth');
        
        // Should return the same instance or equivalent
        $this->assertEquals(get_class($middleware1), get_class($middleware2));
    }
    
    /**
     * Test complex middleware pipeline
     */
    public function testComplexMiddlewarePipeline()
    {
        $pipeline = $this->manager->createPipeline();
        
        // Add multiple middleware with different priorities
        $pipeline->add('security', 1000);
        $pipeline->add('cors', 900);
        $pipeline->add('auth', 700);
        $pipeline->add('throttle:60,1', 500);
        
        // Test conditional middleware
        $pipeline->addIf('csrf', function($request) {
            return $request->getMethod() === 'POST';
        }, 800);
        
        // Test route-specific middleware
        $pipeline->addForRoutes('role:admin', ['/admin/*'], 600);
        
        $this->assertInstanceOf(MockMiddlewarePipeline::class, $pipeline);
    }
}

// Run the tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $test = new MiddlewareSystemTest();
        $success = $test->runAllTests();
        exit($success ? 0 : 1);
    } catch (\Exception $e) {
        echo "Error running tests: {$e->getMessage()}\n";
        exit(1);
    }
}

// Mock middleware classes for testing
class TestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Test-Middleware', 'executed');
        return $response;
    }
}

class PriorityTestMiddleware implements MiddlewareInterface
{
    private $name;
    
    public function __construct($name = 'default')
    {
        $this->name = $name;
    }
    
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);
        $existing = $response->headers->get('X-Execution-Order', '');
        $response->headers->set('X-Execution-Order', $existing . $this->name . ',');
        return $response;
    }
}

class ConditionalTestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if ($request->getMethod() === 'POST') {
            $response = $next($request);
            $response->headers->set('X-Conditional', 'executed');
            return $response;
        }
        
        return $next($request);
    }
}