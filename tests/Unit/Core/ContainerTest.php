<?php

namespace Tests\Unit\Core;

use Tests\TestCase;
use App\Core\Container\Container;
use App\Core\Container\ContainerException;
use App\Core\Container\ContainerNotFoundException;

/**
 * Test cases for the Container class
 */
class ContainerTest extends TestCase
{
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }
    
    public function testGetInstance()
    {
        $instance1 = Container::getInstance();
        $instance2 = Container::getInstance();
        
        $this->assertInstanceOf(Container::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }
    
    public function testBind()
    {
        $this->container->bind('test', 'value');
        $this->assertTrue($this->container->bound('test'));
        $this->assertEquals('value', $this->container->make('test'));
    }
    
    public function testBindClosure()
    {
        $this->container->bind('test', function() {
            return 'closure_value';
        });
        
        $this->assertEquals('closure_value', $this->container->make('test'));
    }
    
    public function testBindClass()
    {
        $this->container->bind('test', TestClass::class);
        $instance = $this->container->make('test');
        
        $this->assertInstanceOf(TestClass::class, $instance);
    }
    
    public function testSingleton()
    {
        $this->container->singleton('test', function() {
            return new TestClass();
        });
        
        $instance1 = $this->container->make('test');
        $instance2 = $this->container->make('test');
        
        $this->assertSame($instance1, $instance2);
    }
    
    public function testInstance()
    {
        $testInstance = new TestClass();
        $this->container->instance('test', $testInstance);
        
        $retrieved = $this->container->make('test');
        $this->assertSame($testInstance, $retrieved);
    }
    
    public function testMake()
    {
        // Test making a class without binding
        $instance = $this->container->make(TestClass::class);
        $this->assertInstanceOf(TestClass::class, $instance);
        
        // Test making with parameters
        $instance = $this->container->make(TestClassWithDependency::class);
        $this->assertInstanceOf(TestClassWithDependency::class, $instance);
        $this->assertInstanceOf(TestClass::class, $instance->dependency);
    }
    
    public function testMakeWithParameters()
    {
        $this->container->bind('test', function($container, $parameters) {
            return new TestClassWithConstructor($parameters['name'] ?? 'default');
        });
        
        $instance = $this->container->make('test', ['name' => 'John']);
        $this->assertEquals('John', $instance->name);
    }
    
    public function testBound()
    {
        $this->assertFalse($this->container->bound('test'));
        
        $this->container->bind('test', 'value');
        $this->assertTrue($this->container->bound('test'));
    }
    
    public function testHas()
    {
        $this->assertFalse($this->container->has('test'));
        
        $this->container->bind('test', 'value');
        $this->assertTrue($this->container->has('test'));
    }
    
    public function testGet()
    {
        $this->container->bind('test', 'value');
        $this->assertEquals('value', $this->container->get('test'));
    }
    
    public function testGetThrowsNotFoundException()
    {
        $this->expectException(ContainerNotFoundException::class);
        $this->container->get('nonexistent');
    }
    
    public function testAlias()
    {
        $this->container->bind('original', 'value');
        $this->container->alias('original', 'alias');
        
        $this->assertEquals('value', $this->container->make('alias'));
    }
    
    public function testTag()
    {
        $this->container->bind('service1', TestClass::class);
        $this->container->bind('service2', TestClassWithDependency::class);
        
        $this->container->tag(['service1', 'service2'], 'services');
        
        $tagged = $this->container->tagged('services');
        $this->assertCount(2, $tagged);
    }
    
    public function testExtend()
    {
        $this->container->bind('test', function() {
            return new TestClass();
        });
        
        $this->container->extend('test', function($service, $container) {
            $service->extended = true;
            return $service;
        });
        
        $instance = $this->container->make('test');
        $this->assertTrue($instance->extended);
    }
    
    public function testWhen()
    {
        $this->container->when(TestClassWithDependency::class)
            ->needs(TestInterface::class)
            ->give(TestImplementation::class);
        
        $instance = $this->container->make(TestClassWithInterface::class);
        $this->assertInstanceOf(TestImplementation::class, $instance->implementation);
    }
    
    public function testCall()
    {
        $result = $this->container->call(function(TestClass $test) {
            return $test;
        });
        
        $this->assertInstanceOf(TestClass::class, $result);
    }
    
    public function testCallWithParameters()
    {
        $result = $this->container->call(function($name, TestClass $test) {
            return [$name, $test];
        }, ['name' => 'John']);
        
        $this->assertEquals('John', $result[0]);
        $this->assertInstanceOf(TestClass::class, $result[1]);
    }
    
    public function testMethodInjection()
    {
        $testObject = new TestClassWithMethods();
        
        $result = $this->container->call([$testObject, 'methodWithDependency']);
        $this->assertInstanceOf(TestClass::class, $result);
    }
    
    public function testResolvingCallback()
    {
        $called = false;
        
        $this->container->resolving('test', function($object, $container) use (&$called) {
            $called = true;
            $object->resolved = true;
        });
        
        $this->container->bind('test', function() {
            return new TestClass();
        });
        
        $instance = $this->container->make('test');
        
        $this->assertTrue($called);
        $this->assertTrue($instance->resolved);
    }
    
    public function testAfterResolvingCallback()
    {
        $called = false;
        
        $this->container->afterResolving('test', function($object, $container) use (&$called) {
            $called = true;
            $object->afterResolved = true;
        });
        
        $this->container->bind('test', function() {
            return new TestClass();
        });
        
        $instance = $this->container->make('test');
        
        $this->assertTrue($called);
        $this->assertTrue($instance->afterResolved);
    }
    
    public function testCircularDependencyDetection()
    {
        $this->container->bind(CircularA::class, CircularA::class);
        $this->container->bind(CircularB::class, CircularB::class);
        
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');
        
        $this->container->make(CircularA::class);
    }
    
    public function testForget()
    {
        $this->container->bind('test', 'value');
        $this->assertTrue($this->container->bound('test'));
        
        $this->container->forget('test');
        $this->assertFalse($this->container->bound('test'));
    }
    
    public function testFlush()
    {
        $this->container->bind('test1', 'value1');
        $this->container->bind('test2', 'value2');
        $this->container->singleton('test3', 'value3');
        
        $this->container->flush();
        
        $this->assertFalse($this->container->bound('test1'));
        $this->assertFalse($this->container->bound('test2'));
        $this->assertFalse($this->container->bound('test3'));
    }
    
    public function testArrayAccess()
    {
        // Test offsetSet
        $this->container['test'] = 'value';
        $this->assertTrue($this->container->bound('test'));
        
        // Test offsetGet
        $this->assertEquals('value', $this->container['test']);
        
        // Test offsetExists
        $this->assertTrue(isset($this->container['test']));
        
        // Test offsetUnset
        unset($this->container['test']);
        $this->assertFalse($this->container->bound('test'));
    }
}

// Test classes for dependency injection testing
class TestClass
{
    public $extended = false;
    public $resolved = false;
    public $afterResolved = false;
}

class TestClassWithDependency
{
    public $dependency;
    
    public function __construct(TestClass $dependency)
    {
        $this->dependency = $dependency;
    }
}

class TestClassWithConstructor
{
    public $name;
    
    public function __construct($name)
    {
        $this->name = $name;
    }
}

interface TestInterface
{
    public function test();
}

class TestImplementation implements TestInterface
{
    public function test()
    {
        return 'implemented';
    }
}

class TestClassWithInterface
{
    public $implementation;
    
    public function __construct(TestInterface $implementation)
    {
        $this->implementation = $implementation;
    }
}

class TestClassWithMethods
{
    public function methodWithDependency(TestClass $dependency)
    {
        return $dependency;
    }
}

// Circular dependency test classes
class CircularA
{
    public function __construct(CircularB $b)
    {
        // Circular dependency
    }
}

class CircularB
{
    public function __construct(CircularA $a)
    {
        // Circular dependency
    }
}