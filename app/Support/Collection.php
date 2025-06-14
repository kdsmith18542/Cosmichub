<?php

namespace App\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Collection class for working with arrays of data
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array The items contained in the collection
     */
    protected $items = [];

    /**
     * Create a new collection
     *
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Create a new collection instance
     *
     * @param array $items
     * @return static
     */
    public static function make(array $items = []): self
    {
        return new static($items);
    }

    /**
     * Get all items in the collection
     *
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get the first item from the collection
     *
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        if ($callback === null) {
            return empty($this->items) ? $default : reset($this->items);
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get the last item from the collection
     *
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        if ($callback === null) {
            return empty($this->items) ? $default : end($this->items);
        }

        return $this->filter($callback)->last(null, $default);
    }

    /**
     * Filter the collection using a callback
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): self
    {
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Map over each item in the collection
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }

    /**
     * Reduce the collection to a single value
     *
     * @param callable $callback
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Get a specific item by key
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * Set an item in the collection
     *
     * @param mixed $key
     * @param mixed $value
     * @return $this
     */
    public function put($key, $value): self
    {
        $this->items[$key] = $value;
        return $this;
    }

    /**
     * Push an item onto the end of the collection
     *
     * @param mixed $value
     * @return $this
     */
    public function push($value): self
    {
        $this->items[] = $value;
        return $this;
    }

    /**
     * Check if the collection is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if the collection is not empty
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Check if an item exists in the collection
     *
     * @param mixed $key
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Remove an item from the collection
     *
     * @param mixed $key
     * @return $this
     */
    public function forget($key): self
    {
        unset($this->items[$key]);
        return $this;
    }

    /**
     * Get the keys of the collection items
     *
     * @return static
     */
    public function keys(): self
    {
        return new static(array_keys($this->items));
    }

    /**
     * Get the values of the collection items
     *
     * @return static
     */
    public function values(): self
    {
        return new static(array_values($this->items));
    }

    /**
     * Sort the collection
     *
     * @param callable|null $callback
     * @return static
     */
    public function sort(callable $callback = null): self
    {
        $items = $this->items;
        
        if ($callback) {
            uasort($items, $callback);
        } else {
            asort($items);
        }
        
        return new static($items);
    }

    /**
     * Reverse the collection
     *
     * @return static
     */
    public function reverse(): self
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Take the first or last {$limit} items
     *
     * @param int $limit
     * @return static
     */
    public function take(int $limit): self
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }
        
        return $this->slice(0, $limit);
    }

    /**
     * Slice the collection
     *
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice(int $offset, int $length = null): self
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Convert the collection to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Convert the collection to JSON
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get the collection as JSON serializable array
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    /**
     * Get an iterator for the items
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Count the number of items in the collection
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Determine if an item exists at an offset
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        if ($key === null) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset
     *
     * @param mixed $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Convert the collection to a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}