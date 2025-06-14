<?php

namespace App\Core\Events;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Base Event Class
 *
 * This class provides a foundation for all events in the application,
 * implementing PSR-14 StoppableEventInterface for enhanced event control.
 * Enhanced as part of the refactoring plan for better event management.
 */
abstract class Event implements StoppableEventInterface
{
    /**
     * Whether event propagation has been stopped
     *
     * @var bool
     */
    protected $propagationStopped = false;

    /**
     * Event timestamp
     *
     * @var float
     */
    protected $timestamp;

    /**
     * Event metadata
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * Create a new event instance
     */
    public function __construct()
    {
        $this->timestamp = microtime(true);
    }

    /**
     * Check if event propagation has been stopped
     *
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stop event propagation
     *
     * @return $this
     */
    public function stopPropagation(): self
    {
        $this->propagationStopped = true;
        return $this;
    }

    /**
     * Get event timestamp
     *
     * @return float
     */
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getName(): string
    {
        return static::class;
    }

    /**
     * Set event metadata
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get event metadata
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadata(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if metadata exists
     *
     * @param string $key
     * @return bool
     */
    public function hasMetadata(string $key): bool
    {
        return isset($this->metadata[$key]);
    }

    /**
     * Convert event to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'timestamp' => $this->getTimestamp(),
            'metadata' => $this->getMetadata(),
            'propagation_stopped' => $this->isPropagationStopped(),
        ];
    }

    /**
     * Convert event to JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Get event priority (override in subclasses)
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * Check if event should be queued (override in subclasses)
     *
     * @return bool
     */
    public function shouldQueue(): bool
    {
        return false;
    }

    /**
     * Check if event should be broadcasted (override in subclasses)
     *
     * @return bool
     */
    public function shouldBroadcast(): bool
    {
        return false;
    }

    /**
     * Get broadcast channels (override in subclasses)
     *
     * @return array
     */
    public function getBroadcastChannels(): array
    {
        return [];
    }

    /**
     * Get broadcast data (override in subclasses)
     *
     * @return array
     */
    public function getBroadcastData(): array
    {
        return $this->toArray();
    }
}