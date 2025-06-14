<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Core\Events\EventDispatcher;
use App\Core\Events\Event;
use App\Core\Application;
use App\Core\Container\Container;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * EventDispatcher Test
 *
 * Tests for the enhanced event dispatcher system following the refactoring plan.
 * Verifies PSR-14 compliance and enhanced functionality.
 */
class EventDispatcherTest extends TestCase
{
    /**
     * Test that EventDispatcher implements PSR-14 interface
     *
     * @return void
     */
    public function testImplementsPsr14Interface()
    {
        $app = $this->createApplication();
        $dispatcher = new EventDispatcher($app);
        
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    }

    /**
     * Test basic event listening and firing
     *
     * @return void
     */
    public function testBasicEventListening()
    {
        $app = $this->createApplication();
        $dispatcher = new EventDispatcher($app);
        
        $called = false;
        $dispatcher->listen('test.event', function() use (&$called) {
            $called = true;
        });
        
        $dispatcher->fire('test.event');
        
        $this->assertTrue($called);
    }

    /**
     * Test PSR-14 dispatch method
     *
     * @return void
     */
    public function testPsr14Dispatch()
    {
        $app = $this->createApplication();
        $dispatcher = new EventDispatcher($app);
        
        $event = new TestEvent();
        $result = $dispatcher->dispatch($event);
        
        $this->assertSame($event, $result);
    }

    /**
     * Test stoppable event propagation
     *
     * @return void
     */
    public function testStoppableEventPropagation()
    {
        $app = $this->createApplication();
        $dispatcher = new EventDispatcher($app);
        
        $callCount = 0;
        
        // First listener stops propagation
        $dispatcher->listen('test.stoppable', function($event) use (&$callCount) {
            $callCount++;
            if ($event instanceof StoppableEventInterface) {
                $event->stopPropagation();
            }
        });
        
        // Second listener should not be called
        $dispatcher->listen('test.stoppable', function() use (&$callCount) {
            $callCount++;
        });
        
        $event = new StoppableTestEvent();
        $dispatcher->dispatch($event);
        
        $this->assertEquals(1, $callCount);
        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * Test event listener priorities
     *
     * @return void
     */
    public function testEventListenerPriorities()
    {
        $app = $this->createApplication();
        $dispatcher = new EventDispatcher($app);
        
        $order = [];
        
        // Add listeners with different priorities
        $dispatcher->listen('test.priority', function() use (&$order) {
            $order[] = 'low';
        }, 10);
        
        $dispatcher->listen('test.priority', function() use (&$order) {
            $order[] = 'high';
        }, 100);
        
        $dispatcher->listen('test.priority', function() use (&$order) {
            $order[] = 'medium';
        }, 50);
        
        $dispatcher->fire('test.priority');
        
        $this->assertEquals(['high', 'medium', 'low'], $order);
    }

    /**
     * Test wildcard event listening
     *
     * @return void
     */
    public function testWildcardEventListening()
    {
        $app = $this->createApplication();
        $dispatcher = new EventDispatcher($app);
        
        $called = false;
        $dispatcher->listen('test.*', function() use (&$called) {
            $called = true;
        });
        
        $dispatcher->fire('test.wildcard');
        
        $this->assertTrue($called);
    }

    /**
     * Test container integration
     *
     * @return void
     */
    public function testContainerIntegration()
    {
        $app = $this->createApplication();
        $container = new Container();
        
        // Register a listener in the container
        $container->bind('test.listener', function() {
            return new TestListener();
        });
        
        $dispatcher = new EventDispatcher($app, $container);
        
        $dispatcher->listen('test.container', 'test.listener');
        $dispatcher->fire('test.container');
        
        // If we get here without errors, container integration works
        $this->assertTrue(true);
    }

    /**
     * Test event payload handling
     *
     * @return void
     */
    public function testEventPayloadHandling()
    {
        $app = $this->createApplication();
        $dispatcher = new EventDispatcher($app);
        
        $receivedPayload = null;
        $dispatcher->listen('test.payload', function($event, $payload) use (&$receivedPayload) {
            $receivedPayload = $payload;
        });
        
        $testPayload = ['key' => 'value'];
        $dispatcher->fire('test.payload', $testPayload);
        
        $this->assertEquals($testPayload, $receivedPayload);
    }
}

/**
 * Test Event Class
 */
class TestEvent extends Event
{
    public function getName(): string
    {
        return 'test.event';
    }
}

/**
 * Stoppable Test Event Class
 */
class StoppableTestEvent extends Event implements StoppableEventInterface
{
    public function getName(): string
    {
        return 'test.stoppable';
    }
}

/**
 * Test Listener Class
 */
class TestListener
{
    public function handle($event, $payload = null)
    {
        // Test listener implementation
    }
}