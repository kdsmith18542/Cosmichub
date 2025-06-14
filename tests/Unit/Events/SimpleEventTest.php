<?php

namespace Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use App\Core\Events\Event;
use App\Core\Events\EventDispatcher;
use App\Core\Application;

/**
 * Simple Event Test
 *
 * Basic tests for the enhanced event system without complex dependencies.
 */
class SimpleEventTest extends TestCase
{
    /**
     * Test basic event creation
     *
     * @return void
     */
    public function testEventCreation()
    {
        $event = new TestSimpleEvent();
        
        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('Tests\\Unit\\Events\\TestSimpleEvent', $event->getName());
        $this->assertFalse($event->isPropagationStopped());
        $this->assertIsFloat($event->getTimestamp());
    }

    /**
     * Test event propagation stopping
     *
     * @return void
     */
    public function testEventPropagationStopping()
    {
        $event = new TestSimpleEvent();
        
        $this->assertFalse($event->isPropagationStopped());
        
        $event->stopPropagation();
        
        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * Test event metadata
     *
     * @return void
     */
    public function testEventMetadata()
    {
        $event = new TestSimpleEvent();
        
        $this->assertFalse($event->hasMetadata('test'));
        
        $event->setMetadata('test', 'value');
        
        $this->assertTrue($event->hasMetadata('test'));
        $this->assertEquals('value', $event->getMetadata('test'));
        $this->assertEquals('default', $event->getMetadata('nonexistent', 'default'));
    }

    /**
     * Test event array conversion
     *
     * @return void
     */
    public function testEventToArray()
    {
        $event = new TestSimpleEvent();
        $event->setMetadata('key', 'value');
        
        $array = $event->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertArrayHasKey('propagation_stopped', $array);
        
        $this->assertEquals('Tests\\Unit\\Events\\TestSimpleEvent', $array['name']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
        $this->assertFalse($array['propagation_stopped']);
    }

    /**
     * Test event JSON conversion
     *
     * @return void
     */
    public function testEventToJson()
    {
        $event = new TestSimpleEvent();
        $event->setMetadata('test', 'data');
        
        $json = $event->toJson();
        
        $this->assertIsString($json);
        
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('Tests\\Unit\\Events\\TestSimpleEvent', $decoded['name']);
    }

    /**
     * Test basic event dispatcher functionality
     *
     * @return void
     */
    public function testBasicEventDispatcher()
    {
        // Create a minimal application mock
        $app = $this->createMock(Application::class);
        
        $dispatcher = new EventDispatcher($app);
        
        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }
}

/**
 * Simple Test Event Class
 */
class TestSimpleEvent extends Event
{
    public function getName(): string
    {
        return static::class;
    }
}