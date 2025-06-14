<?php

namespace App\Core\Database;

use ArrayAccess;
use Countable;
use Iterator;
use JsonSerializable;
use App\Core\Exceptions\DatabaseException;

/**
 * Collection Class
 * 
 * A collection class for handling groups of models with enhanced functionality
 * including filtering, mapping, sorting, and aggregation operations
 */
class Collection implements ArrayAccess, Countable, Iterator, JsonSerializable
{
    /**
     * @var array The items contained in the collection
     */
    protected $items = [];
    
    /**
     * @var int Current position for iteration
     */
    private $position = 0;
    
    /**
     * Create a new collection instance
     * 
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }
    
    /**
     * Create a new collection instance if the value isn't one already
     * 
     * @param mixed $items
     * @return static
     */
    public static function make($items = [])
    {
        return new static($items);
    }
    
    /**
     * Get all items in the collection
     * 
     * @return array
     */
    public function all()
    {
        return $this->items;
    }
    
    /**
     * Get the average value of a given key
     * 
     * @param callable|string|null $callback
     * @return mixed
     */
    public function avg($callback = null)
    {
        $callback = $this->valueRetriever($callback);
        
        $items = $this->map($callback)->filter(function ($value) {
            return !is_null($value);
        });
        
        if ($count = $items->count()) {
            return $items->sum() / $count;
        }
    }
    
    /**
     * Alias for the "avg" method
     * 
     * @param callable|string|null $callback
     * @return mixed
     */
    public function average($callback = null)
    {
        return $this->avg($callback);
    }
    
    /**
     * Get the median of a given key
     * 
     * @param string|null $key
     * @return mixed
     */
    public function median($key = null)
    {
        $values = (isset($key) ? $this->pluck($key) : $this)
            ->filter(function ($item) {
                return !is_null($item);
            })->sort()->values();
        
        $count = $values->count();
        
        if ($count === 0) {
            return;
        }
        
        $middle = (int) ($count / 2);
        
        if ($count % 2) {
            return $values->get($middle);
        }
        
        return (new static([
            $values->get($middle - 1), $values->get($middle),
        ]))->average();
    }
    
    /**
     * Get the mode of a given key
     * 
     * @param string|null $key
     * @return array|null
     */
    public function mode($key = null)
    {
        if ($this->count() === 0) {
            return;
        }
        
        $collection = isset($key) ? $this->pluck($key) : $this;
        
        $counts = new static;
        
        $collection->each(function ($value) use ($counts) {
            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
        });
        
        $sorted = $counts->sort();
        
        $highestValue = $sorted->last();
        
        return $sorted->filter(function ($value) use ($highestValue) {
            return $value == $highestValue;
        })->sort()->keys()->all();
    }
    
    /**
     * Collapse the collection of items into a single array
     * 
     * @return static
     */
    public function collapse()
    {
        return new static(array_merge([], ...array_values($this->items)));
    }
    
    /**
     * Determine if an item exists in the collection
     * 
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            if ($this->useAsCallable($key)) {
                $placeholder = new \stdClass;
                
                return $this->first($key, $placeholder) !== $placeholder;
            }
            
            return in_array($key, $this->items);
        }
        
        return $this->contains($this->operatorForWhere(...func_get_args()));
    }
    
    /**
     * Cross join with the given lists, returning all possible permutations
     * 
     * @param mixed ...$lists
     * @return static
     */
    public function crossJoin(...$lists)
    {
        return new static(array_reduce(
            $lists,
            [$this, 'crossJoinArray'],
            $this->items
        ));
    }
    
    /**
     * Cross join array
     * 
     * @param array $a
     * @param array $b
     * @return array
     */
    protected function crossJoinArray(array $a, array $b)
    {
        $results = [];
        
        foreach ($a as $aValue) {
            foreach ($b as $bValue) {
                $results[] = array_merge(
                    (array) $aValue,
                    (array) $bValue
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Get the items that are not present in the given items
     * 
     * @param mixed $items
     * @return static
     */
    public function diff($items)
    {
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }
    
    /**
     * Get the items that are not present in the given items, using the callback
     * 
     * @param mixed $items
     * @param callable $callback
     * @return static
     */
    public function diffUsing($items, callable $callback)
    {
        return new static(array_udiff($this->items, $this->getArrayableItems($items), $callback));
    }
    
    /**
     * Get the items whose keys and values are not present in the given items
     * 
     * @param mixed $items
     * @return static
     */
    public function diffAssoc($items)
    {
        return new static(array_diff_assoc($this->items, $this->getArrayableItems($items)));
    }
    
    /**
     * Get the items whose keys are not present in the given items
     * 
     * @param mixed $items
     * @return static
     */
    public function diffKeys($items)
    {
        return new static(array_diff_key($this->items, $this->getArrayableItems($items)));
    }
    
    /**
     * Execute a callback over each item
     * 
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        
        return $this;
    }
    
    /**
     * Determine if all items pass the given test
     * 
     * @param string|callable $key
     * @param mixed $operator
     * @param mixed $value
     * @return bool
     */
    public function every($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            $callback = $this->valueRetriever($key);
            
            foreach ($this->items as $k => $v) {
                if (!$callback($v, $k)) {
                    return false;
                }
            }
            
            return true;
        }
        
        return $this->every($this->operatorForWhere(...func_get_args()));
    }
    
    /**
     * Get all items except for those with the specified keys
     * 
     * @param mixed $keys
     * @return static
     */
    public function except($keys)
    {
        if ($keys instanceof self) {
            $keys = $keys->all();
        } elseif (!is_array($keys)) {
            $keys = func_get_args();
        }
        
        return new static(array_diff_key($this->items, array_flip($keys)));
    }
    
    /**
     * Run a filter over each of the items
     * 
     * @param callable|null $callback
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }
        
        return new static(array_filter($this->items));
    }
    
    /**
     * Apply the callback if the value is truthy
     * 
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function when($value, callable $callback, callable $default = null)
    {
        if ($value) {
            return $callback($this, $value);
        } elseif ($default) {
            return $default($this, $value);
        }
        
        return $this;
    }
    
    /**
     * Apply the callback if the collection is empty
     * 
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function whenEmpty(callable $callback, callable $default = null)
    {
        return $this->when($this->isEmpty(), $callback, $default);
    }
    
    /**
     * Apply the callback if the collection is not empty
     * 
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function whenNotEmpty(callable $callback, callable $default = null)
    {
        return $this->when($this->isNotEmpty(), $callback, $default);
    }
    
    /**
     * Apply the callback unless the value is truthy
     * 
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function unless($value, callable $callback, callable $default = null)
    {
        return $this->when(!$value, $callback, $default);
    }
    
    /**
     * Apply the callback unless the collection is empty
     * 
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function unlessEmpty(callable $callback, callable $default = null)
    {
        return $this->whenNotEmpty($callback, $default);
    }
    
    /**
     * Apply the callback unless the collection is not empty
     * 
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function unlessNotEmpty(callable $callback, callable $default = null)
    {
        return $this->whenEmpty($callback, $default);
    }
    
    /**
     * Filter items by the given key value pair
     * 
     * @param string $key
     * @param mixed $operator
     * @param mixed $value
     * @return static
     */
    public function where($key, $operator = null, $value = null)
    {
        return $this->filter($this->operatorForWhere(...func_get_args()));
    }
    
    /**
     * Filter items where the given key is not null
     * 
     * @param string|null $key
     * @return static
     */
    public function whereNotNull($key = null)
    {
        return $this->where($key, '!==', null);
    }
    
    /**
     * Filter items where the given key is null
     * 
     * @param string|null $key
     * @return static
     */
    public function whereNull($key = null)
    {
        return $this->where($key, '===', null);
    }
    
    /**
     * Filter items by the given key value pair using strict comparison
     * 
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function whereStrict($key, $value)
    {
        return $this->where($key, '===', $value);
    }
    
    /**
     * Filter items where the value for the given key is between the given values
     * 
     * @param string $key
     * @param array $values
     * @return static
     */
    public function whereBetween($key, $values)
    {
        return $this->where($key, '>=', reset($values))->where($key, '<=', end($values));
    }
    
    /**
     * Filter items where the value for the given key is not between the given values
     * 
     * @param string $key
     * @param array $values
     * @return static
     */
    public function whereNotBetween($key, $values)
    {
        return $this->filter(function ($item) use ($key, $values) {
            return data_get($item, $key) < reset($values) || data_get($item, $key) > end($values);
        });
    }
    
    /**
     * Filter items where the value for the given key is in the given array
     * 
     * @param string $key
     * @param mixed $values
     * @param bool $strict
     * @return static
     */
    public function whereIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);
        
        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }
    
    /**
     * Filter items where the value for the given key is not in the given array
     * 
     * @param string $key
     * @param mixed $values
     * @param bool $strict
     * @return static
     */
    public function whereNotIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);
        
        return $this->filter(function ($item) use ($key, $values, $strict) {
            return !in_array(data_get($item, $key), $values, $strict);
        });
    }
    
    /**
     * Filter items where the value for the given key matches the given pattern
     * 
     * @param string $key
     * @param string $pattern
     * @param int $flags
     * @return static
     */
    public function whereInstanceOf($class)
    {
        return $this->filter(function ($value) use ($class) {
            return $value instanceof $class;
        });
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
        if (is_null($callback)) {
            if (empty($this->items)) {
                return value($default);
            }
            
            foreach ($this->items as $item) {
                return $item;
            }
        }
        
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        
        return value($default);
    }
    
    /**
     * Get the first item by the given key value pair
     * 
     * @param string $key
     * @param mixed $operator
     * @param mixed $value
     * @return mixed
     */
    public function firstWhere($key, $operator = null, $value = null)
    {
        return $this->first($this->operatorForWhere(...func_get_args()));
    }
    
    /**
     * Get a flattened array of the items in the collection
     * 
     * @param int $depth
     * @return static
     */
    public function flatten($depth = INF)
    {
        return new static($this->flattenArray($this->items, $depth));
    }
    
    /**
     * Flip the items in the collection
     * 
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }
    
    /**
     * Remove an item from the collection by key
     * 
     * @param string|array $keys
     * @return $this
     */
    public function forget($keys)
    {
        foreach ((array) $keys as $key) {
            $this->offsetUnset($key);
        }
        
        return $this;
    }
    
    /**
     * Get an item from the collection by key
     * 
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }
        
        return value($default);
    }
    
    /**
     * Group an associative array by a field or using a callback
     * 
     * @param array|callable|string $groupBy
     * @param bool $preserveKeys
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        if (!$this->useAsCallable($groupBy) && is_array($groupBy)) {
            $nextGroups = $groupBy;
            
            $groupBy = array_shift($nextGroups);
        }
        
        $groupBy = $this->valueRetriever($groupBy);
        
        $results = [];
        
        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);
            
            if (!is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }
            
            foreach ($groupKeys as $groupKey) {
                $groupKey = is_bool($groupKey) ? (int) $groupKey : $groupKey;
                
                if (!array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static;
                }
                
                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }
        
        $result = new static($results);
        
        if (!empty($nextGroups)) {
            return $result->map->groupBy($nextGroups, $preserveKeys);
        }
        
        return $result;
    }
    
    /**
     * Key an associative array by a field or using a callback
     * 
     * @param callable|string $keyBy
     * @return static
     */
    public function keyBy($keyBy)
    {
        $keyBy = $this->valueRetriever($keyBy);
        
        $results = [];
        
        foreach ($this->items as $key => $item) {
            $resolvedKey = $keyBy($item, $key);
            
            if (is_object($resolvedKey)) {
                $resolvedKey = (string) $resolvedKey;
            }
            
            $results[$resolvedKey] = $item;
        }
        
        return new static($results);
    }
    
    /**
     * Determine if an item exists in the collection by key
     * 
     * @param mixed $key
     * @return bool
     */
    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        
        foreach ($keys as $value) {
            if (!$this->offsetExists($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Concatenate values of a given key as a string
     * 
     * @param string $value
     * @param string|null $glue
     * @return string
     */
    public function implode($value, $glue = null)
    {
        $first = $this->first();
        
        if (is_array($first) || (is_object($first) && !$first instanceof \Stringable)) {
            return implode($glue ?? '', $this->pluck($value)->all());
        }
        
        return implode($value ?? '', $this->items);
    }
    
    /**
     * Intersect the collection with the given items
     * 
     * @param mixed $items
     * @return static
     */
    public function intersect($items)
    {
        return new static(array_intersect($this->items, $this->getArrayableItems($items)));
    }
    
    /**
     * Intersect the collection with the given items by key
     * 
     * @param mixed $items
     * @return static
     */
    public function intersectByKeys($items)
    {
        return new static(array_intersect_key(
            $this->items, $this->getArrayableItems($items)
        ));
    }
    
    /**
     * Determine if the collection is empty or not
     * 
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }
    
    /**
     * Determine if the collection is not empty
     * 
     * @return bool
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }
    
    /**
     * Join all items from the collection using a string
     * 
     * @param string $glue
     * @param string $finalGlue
     * @return string
     */
    public function join($glue, $finalGlue = '')
    {
        if ($finalGlue === '') {
            return $this->implode($glue);
        }
        
        $count = $this->count();
        
        if ($count === 0) {
            return '';
        }
        
        if ($count === 1) {
            return $this->last();
        }
        
        $collection = new static($this->items);
        
        $finalItem = $collection->pop();
        
        return $collection->implode($glue).$finalGlue.$finalItem;
    }
    
    /**
     * Get the keys of the collection items
     * 
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
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
        if (is_null($callback)) {
            return empty($this->items) ? value($default) : end($this->items);
        }
        
        return $this->reverse()->first($callback, $default);
    }
    
    /**
     * Get the values of a given key
     * 
     * @param string|array $value
     * @param string|null $key
     * @return static
     */
    public function pluck($value, $key = null)
    {
        return new static(array_pluck($this->items, $value, $key));
    }
    
    /**
     * Run a map over each of the items
     * 
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);
        
        $items = array_map($callback, $this->items, $keys);
        
        return new static(array_combine($keys, $items));
    }
    
    /**
     * Run a map over each nested chunk of items
     * 
     * @param callable $callback
     * @return static
     */
    public function mapSpread(callable $callback)
    {
        return $this->map(function ($chunk, $key) use ($callback) {
            $chunk[] = $key;
            
            return $callback(...$chunk);
        });
    }
    
    /**
     * Run a grouping map over the items
     * 
     * @param callable $callback
     * @return static
     */
    public function mapToGroups(callable $callback)
    {
        $groups = $this->mapToDictionary($callback);
        
        return $groups->map([$this, 'make']);
    }
    
    /**
     * Run a dictionary map over the items
     * 
     * @param callable $callback
     * @return static
     */
    public function mapToDictionary(callable $callback)
    {
        $dictionary = [];
        
        foreach ($this->items as $key => $item) {
            $pair = $callback($item, $key);
            
            $key = key($pair);
            
            $value = reset($pair);
            
            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }
            
            $dictionary[$key][] = $value;
        }
        
        return new static($dictionary);
    }
    
    /**
     * Run an associative map over each of the items
     * 
     * @param callable $callback
     * @return static
     */
    public function mapWithKeys(callable $callback)
    {
        $result = [];
        
        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);
            
            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }
        
        return new static($result);
    }
    
    /**
     * Merge the collection with the given items
     * 
     * @param mixed $items
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }
    
    /**
     * Recursively merge the collection with the given items
     * 
     * @param mixed $items
     * @return static
     */
    public function mergeRecursive($items)
    {
        return new static(array_merge_recursive($this->items, $this->getArrayableItems($items)));
    }
    
    /**
     * Create a collection by using this collection for keys and another for its values
     * 
     * @param mixed $values
     * @return static
     */
    public function combine($values)
    {
        return new static(array_combine($this->all(), $this->getArrayableItems($values)));
    }
    
    /**
     * Union the collection with the given items
     * 
     * @param mixed $items
     * @return static
     */
    public function union($items)
    {
        return new static($this->items + $this->getArrayableItems($items));
    }
    
    /**
     * Get the min value of a given key
     * 
     * @param callable|string|null $callback
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);
        
        return $this->map($callback)->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $item) {
            return is_null($result) || $item < $result ? $item : $result;
        });
    }
    
    /**
     * Get the max value of a given key
     * 
     * @param callable|string|null $callback
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);
        
        return $this->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);
            
            return is_null($result) || $value > $result ? $value : $result;
        });
    }
    
    /**
     * Create a new collection consisting of every n-th element
     * 
     * @param int $step
     * @param int $offset
     * @return static
     */
    public function nth($step, $offset = 0)
    {
        $new = [];
        
        $position = 0;
        
        foreach ($this->items as $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }
            
            $position++;
        }
        
        return new static($new);
    }
    
    /**
     * Get the items with the specified keys
     * 
     * @param mixed $keys
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items);
        }
        
        if ($keys instanceof self) {
            $keys = $keys->all();
        }
        
        $keys = is_array($keys) ? $keys : func_get_args();
        
        return new static(array_intersect_key($this->items, array_flip($keys)));
    }
    
    /**
     * Get and remove the last item from the collection
     * 
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }
    
    /**
     * Push an item onto the beginning of the collection
     * 
     * @param mixed $value
     * @param mixed $key
     * @return $this
     */
    public function prepend($value, $key = null)
    {
        $this->items = array_prepend($this->items, $value, $key);
        
        return $this;
    }
    
    /**
     * Push one or more items onto the end of the collection
     * 
     * @param mixed ...$values
     * @return $this
     */
    public function push(...$values)
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }
        
        return $this;
    }
    
    /**
     * Push all of the given items onto the collection
     * 
     * @param iterable $source
     * @return static
     */
    public function concat($source)
    {
        $result = new static($this);
        
        foreach ($source as $item) {
            $result->push($item);
        }
        
        return $result;
    }
    
    /**
     * Get one or a specified number of items randomly from the collection
     * 
     * @param int|null $number
     * @return static|mixed
     * 
     * @throws \InvalidArgumentException
     */
    public function random($number = null)
    {
        if (is_null($number)) {
            return $this->items[array_rand($this->items)];
        }
        
        return new static(array_intersect_key($this->items, array_flip(array_rand($this->items, $number))));
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
     * Replace the collection items with the given items
     * 
     * @param mixed $items
     * @return static
     */
    public function replace($items)
    {
        return new static(array_replace($this->items, $this->getArrayableItems($items)));
    }
    
    /**
     * Recursively replace the collection items with the given items
     * 
     * @param mixed $items
     * @return static
     */
    public function replaceRecursive($items)
    {
        return new static(array_replace_recursive($this->items, $this->getArrayableItems($items)));
    }
    
    /**
     * Reverse items order
     * 
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }
    
    /**
     * Search the collection for a given value and return the corresponding key if successful
     * 
     * @param mixed $value
     * @param bool $strict
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        if (!$this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }
        
        foreach ($this->items as $key => $item) {
            if ($value($item, $key)) {
                return $key;
            }
        }
        
        return false;
    }
    
    /**
     * Get and remove the first item from the collection
     * 
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }
    
    /**
     * Shuffle the items in the collection
     * 
     * @param int|null $seed
     * @return static
     */
    public function shuffle($seed = null)
    {
        return new static(array_shuffle_assoc($this->items, $seed));
    }
    
    /**
     * Create chunks representing a "sliding window" view of the items in the collection
     * 
     * @param int $size
     * @param int $step
     * @return static
     */
    public function sliding($size = 2, $step = 1)
    {
        $chunks = floor(($this->count() - $size) / $step) + 1;
        
        return static::times($chunks, function ($number) use ($size, $step) {
            return $this->slice(($number - 1) * $step, $size);
        });
    }
    
    /**
     * Skip the first {$count} items
     * 
     * @param int $count
     * @return static
     */
    public function skip($count)
    {
        return $this->slice($count);
    }
    
    /**
     * Skip items in the collection until the given condition is met
     * 
     * @param mixed $value
     * @return static
     */
    public function skipUntil($value)
    {
        return new static($this->lazy()->skipUntil($value)->all());
    }
    
    /**
     * Skip items in the collection while the given condition is met
     * 
     * @param mixed $value
     * @return static
     */
    public function skipWhile($value)
    {
        return new static($this->lazy()->skipWhile($value)->all());
    }
    
    /**
     * Slice the underlying collection array
     * 
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }
    
    /**
     * Split a collection into a certain number of groups
     * 
     * @param int $numberOfGroups
     * @return static
     */
    public function split($numberOfGroups)
    {
        if ($this->isEmpty()) {
            return new static;
        }
        
        $groups = new static;
        
        $groupSize = floor($this->count() / $numberOfGroups);
        
        $remain = $this->count() % $numberOfGroups;
        
        $start = 0;
        
        for ($i = 0; $i < $numberOfGroups; $i++) {
            $size = $groupSize;
            
            if ($i < $remain) {
                $size++;
            }
            
            if ($size) {
                $groups->push(new static(array_slice($this->items, $start, $size, true)));
                
                $start += $size;
            }
        }
        
        return $groups;
    }
    
    /**
     * Get the sum of the given values
     * 
     * @param callable|string|null $callback
     * @return mixed
     */
    public function sum($callback = null)
    {
        $callback = is_null($callback) ? $this->identity() : $this->valueRetriever($callback);
        
        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }
    
    /**
     * Take the first or last {$limit} items
     * 
     * @param int $limit
     * @return static
     */
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }
        
        return $this->slice(0, $limit);
    }
    
    /**
     * Take items in the collection until the given condition is met
     * 
     * @param mixed $value
     * @return static
     */
    public function takeUntil($value)
    {
        return new static($this->lazy()->takeUntil($value)->all());
    }
    
    /**
     * Take items in the collection while the given condition is met
     * 
     * @param mixed $value
     * @return static
     */
    public function takeWhile($value)
    {
        return new static($this->lazy()->takeWhile($value)->all());
    }
    
    /**
     * Transform each item in the collection using a callback
     * 
     * @param callable $callback
     * @return $this
     */
    public function transform(callable $callback)
    {
        $this->items = $this->map($callback)->all();
        
        return $this;
    }
    
    /**
     * Reset the keys on the underlying array
     * 
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }
    
    /**
     * Zip the collection together with one or more arrays
     * 
     * @param mixed ...$items
     * @return static
     */
    public function zip($items)
    {
        $arrayableItems = array_map(function ($items) {
            return $this->getArrayableItems($items);
        }, func_get_args());
        
        $params = array_merge([function () {
            return new static(func_get_args());
        }, $this->items], $arrayableItems);
        
        return new static(array_map(...$params));
    }
    
    /**
     * Pad collection to the specified length with a value
     * 
     * @param int $size
     * @param mixed $value
     * @return static
     */
    public function pad($size, $value)
    {
        return new static(array_pad($this->items, $size, $value));
    }
    
    /**
     * Get the collection of items as a plain array
     * 
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof \ArrayAccess ? $value->toArray() : $value;
        }, $this->items);
    }
    
    /**
     * Convert the object into something JSON serializable
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_map(function ($value) {
            if ($value instanceof \JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof \ArrayAccess) {
                return $value->toArray();
            }
            
            return $value;
        }, $this->items);
    }
    
    /**
     * Get the collection of items as JSON
     * 
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
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
     * Get an iterator for the items
     * 
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
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
    public function offsetGet($key): mixed
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
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }
    
    /**
     * Unset the item at a given offset
     * 
     * @param string $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        unset($this->items[$key]);
    }
    
    /**
     * Convert the collection to its string representation
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
    
    /**
     * Results array of items from Collection or Arrayable
     * 
     * @param mixed $items
     * @return array
     */
    protected function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();
        } elseif ($items instanceof \ArrayAccess) {
            return $items->toArray();
        } elseif ($items instanceof \Traversable) {
            return iterator_to_array($items);
        }
        
        return (array) $items;
    }
    
    /**
     * Get an operator checker callback
     * 
     * @param string $key
     * @param string|null $operator
     * @param mixed $value
     * @return \Closure
     */
    protected function operatorForWhere($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            $value = true;
            
            $operator = '=';
        }
        
        if (func_num_args() === 2) {
            $value = $operator;
            
            $operator = '=';
        }
        
        return function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);
            
            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });
            
            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }
            
            switch ($operator) {
                default:
                case '=':
                case '==':
                    return $retrieved == $value;
                case '!=':
                case '<>':
                    return $retrieved != $value;
                case '<':
                    return $retrieved < $value;
                case '>':
                    return $retrieved > $value;
                case '<=':
                    return $retrieved <= $value;
                case '>=':
                    return $retrieved >= $value;
                case '===':
                    return $retrieved === $value;
                case '!==':
                    return $retrieved !== $value;
            }
        };
    }
    
    /**
     * Determine if the given value is callable, but not a string
     * 
     * @param mixed $value
     * @return bool
     */
    protected function useAsCallable($value)
    {
        return !is_string($value) && is_callable($value);
    }
    
    /**
     * Get a value retrieving callback
     * 
     * @param callable|string|null $value
     * @return callable
     */
    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }
        
        return function ($item) use ($value) {
            return data_get($item, $value);
        };
    }
    
    /**
     * Make a function to check an item's equality
     * 
     * @param mixed $value
     * @return \Closure
     */
    protected function equality($value)
    {
        return function ($item) use ($value) {
            return $item === $value;
        };
    }
    
    /**
     * Make a function using another function, by negating its result
     * 
     * @param \Closure $callback
     * @return \Closure
     */
    protected function negate(\Closure $callback)
    {
        return function (...$params) use ($callback) {
            return !$callback(...$params);
        };
    }
    
    /**
     * Make an identity function
     * 
     * @return \Closure
     */
    protected function identity()
    {
        return function ($value) {
            return $value;
        };
    }
    
    /**
     * Flatten a multi-dimensional array into a single level
     * 
     * @param array $array
     * @param int $depth
     * @return array
     */
    protected function flattenArray(array $array, $depth)
    {
        $result = [];
        
        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1
                    ? array_values($item)
                    : $this->flattenArray($item, $depth - 1);
                
                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Create a collection with the given number of items
     * 
     * @param int $number
     * @param callable|null $callback
     * @return static
     */
    public static function times($number, callable $callback = null)
    {
        if ($number < 1) {
            return new static;
        }
        
        if (is_null($callback)) {
            return new static(range(1, $number));
        }
        
        return (new static(range(1, $number)))->map($callback);
    }
    
    /**
     * Create a collection by invoking the callback a given amount of times
     * 
     * @param int $amount
     * @param callable $callback
     * @return static
     */
    public static function range($from, $to)
    {
        return new static(range($from, $to));
    }
    
    // Iterator methods
    public function current(): mixed
    {
        return current($this->items);
    }
    
    public function key(): mixed
    {
        return key($this->items);
    }
    
    public function next(): void
    {
        next($this->items);
    }
    
    public function rewind(): void
    {
        reset($this->items);
    }
    
    public function valid(): bool
    {
        return key($this->items) !== null;
    }
}

// Helper functions that would typically be in a separate file
if (!function_exists('data_get')) {
    function data_get($target, $key, $default = null) {
        if (is_null($key)) {
            return $target;
        }
        
        $key = is_array($key) ? $key : explode('.', $key);
        
        foreach ($key as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }
        
        return $target;
    }
}

if (!function_exists('value')) {
    function value($value) {
        return $value instanceof \Closure ? $value() : $value;
    }
}

if (!function_exists('array_pluck')) {
    function array_pluck($array, $value, $key = null) {
        $results = [];
        
        foreach ($array as $item) {
            $itemValue = data_get($item, $value);
            
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);
                
                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string) $itemKey;
                }
                
                $results[$itemKey] = $itemValue;
            }
        }
        
        return $results;
    }
}

if (!function_exists('array_prepend')) {
    function array_prepend($array, $value, $key = null) {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        
        return $array;
    }
}

if (!function_exists('array_shuffle_assoc')) {
    function array_shuffle_assoc($array, $seed = null) {
        if (!is_null($seed)) {
            mt_srand($seed);
        }
        
        $keys = array_keys($array);
        
        shuffle($keys);
        
        $random = [];
        
        foreach ($keys as $key) {
            $random[$key] = $array[$key];
        }
        
        return $random;
    }
}