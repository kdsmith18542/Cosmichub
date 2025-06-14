<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Container\Container;

class ContainerTest extends TestCase
{
    public function testContainerCanBindAndResolveValues()
    {
        $container = Container::getInstance();
        
        // Test binding a simple value
        $container->bind('test.value', 'hello world');
        $this->assertEquals('hello world', $container->make('test.value'));
    }

    public function testContainerCanBindAndResolveCallables()
    {
        $container = Container::getInstance();
        
        // Test binding a callable
        $container->bind('test.callable', function() {
            return 'from callable';
        });
        
        $this->assertEquals('from callable', $container->make('test.callable'));
    }

    public function testContainerSingletonBinding()
    {
        $container = Container::getInstance();
        
        // Test singleton binding
        $container->singleton('test.singleton', function() {
            return new \stdClass();
        });
        
        $instance1 = $container->make('test.singleton');
        $instance2 = $container->make('test.singleton');
        
        $this->assertSame($instance1, $instance2);
    }

    public function testContainerCanRegisterInstances()
    {
        $container = Container::getInstance();
        $object = new \stdClass();
        $object->property = 'test';
        
        // Test instance binding
        $container->instance('test.instance', $object);
        
        $resolved = $container->make('test.instance');
        $this->assertSame($object, $resolved);
        $this->assertEquals('test', $resolved->property);
    }

    public function testContainerHasMethod()
    {
        $container = Container::getInstance();
        
        $container->bind('test.exists', 'value');
        
        $this->assertTrue($container->has('test.exists'));
        $this->assertFalse($container->has('test.not.exists'));
    }

    public function testContainerThrowsExceptionForUnresolvableBinding()
    {
        $container = Container::getInstance();
        
        $this->expectException(\Exception::class);
        $container->make('non.existent.binding');
    }
}