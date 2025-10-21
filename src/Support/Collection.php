<?php

declare(strict_types=1);

namespace Elarion\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;

/**
 * Collection Class
 *
 * Provides a fluent, expressive API for working with arrays of data.
 * Inspired by Laravel Collections.
 *
 * @template TKey of array-key
 * @template TValue
 * @implements ArrayAccess<TKey, TValue>
 * @implements IteratorAggregate<TKey, TValue>
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * The items contained in the collection
     *
     * @var array<TKey, TValue>
     */
    protected array $items = [];

    /**
     * Create a new collection
     *
     * @param iterable<TKey, TValue> $items Initial items
     */
    public function __construct(iterable $items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * Create a new collection instance
     *
     * @template TMakeKey of array-key
     * @template TMakeValue
     * @param iterable<TMakeKey, TMakeValue> $items Items to collect
     * @return static<TMakeKey, TMakeValue>
     */
    public static function make(iterable $items = []): static
    {
        // @phpstan-ignore-next-line new.static (Required for factory pattern)
        return new static($items);
    }

    // ========================================
    // TRANSFORMATION METHODS
    // ========================================

    /**
     * Run a map over each item
     *
     * @template TMapValue
     * @param callable(TValue, TKey): TMapValue $callback Map callback
     * @return static<TKey, TMapValue>
     */
    public function map(callable $callback): static
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);

        // @phpstan-ignore-next-line new.static (Required for fluent interface)
        return new static(array_combine($keys, $items));
    }

    /**
     * Run a filter over each item
     *
     * @param callable(TValue, TKey): bool|null $callback Filter callback
     * @return static<TKey, TValue>
     */
    public function filter(?callable $callback = null): static
    {
        if ($callback) {
            // @phpstan-ignore-next-line new.static, return.type (Required for fluent interface)
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }

        // @phpstan-ignore-next-line new.static (Required for fluent interface)
        return new static(array_filter($this->items));
    }

    /**
     * Reduce the collection to a single value
     *
     * @template TReduceInitial
     * @template TReduceReturnType
     * @param callable(TReduceInitial, TValue, TKey): TReduceReturnType $callback Reducer callback
     * @param TReduceInitial $initial Initial value
     * @return TReduceReturnType
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $result = $initial;

        foreach ($this->items as $key => $value) {
            $result = $callback($result, $value, $key);
        }

        return $result;
    }

    /**
     * Execute a callback over each item
     *
     * @param callable(TValue, TKey): mixed $callback Callback to execute
     * @return $this
     */
    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }

        return $this;
    }

    // ========================================
    // ACCESS METHODS
    // ========================================

    /**
     * Get the first item
     *
     * @param callable(TValue, TKey): bool|null $callback Optional filter callback
     * @param TValue|null $default Default value if not found
     * @return TValue|null
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if (empty($this->items)) {
                return $default;
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

        return $default;
    }

    /**
     * Get the last item
     *
     * @param callable(TValue, TKey): bool|null $callback Optional filter callback
     * @param TValue|null $default Default value if not found
     * @return TValue|null
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if (empty($this->items)) {
                return $default;
            }

            return end($this->items);
        }

        return $this->filter($callback)->last(null, $default);
    }

    /**
     * Get an item by key
     *
     * @param TKey $key Key to get
     * @param TValue|null $default Default value
     * @return TValue|null
     */
    public function get(mixed $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * Get the nth item
     *
     * @param int $n Item index (0-based)
     * @param TValue|null $default Default value
     * @return TValue|null
     */
    public function nth(int $n, mixed $default = null): mixed
    {
        $values = array_values($this->items);

        return $values[$n] ?? $default;
    }

    // ========================================
    // SORTING AND GROUPING
    // ========================================

    /**
     * Sort items
     *
     * @param callable(TValue, TValue): int|null $callback Optional comparison callback
     * @return static<TKey, TValue>
     */
    public function sort(?callable $callback = null): static
    {
        $items = $this->items;

        if ($callback) {
            uasort($items, $callback);
        } else {
            asort($items);
        }

        // @phpstan-ignore-next-line new.static (Required for fluent interface)
        return new static($items);
    }

    /**
     * Sort items by a key or callback
     *
     * @param callable(TValue, TKey): mixed|string $callback Key or callback
     * @param int $options Sort options
     * @param bool $descending Sort descending
     * @return static<TKey, TValue>
     */
    public function sortBy(callable|string $callback, int $options = SORT_REGULAR, bool $descending = false): static
    {
        $results = [];

        foreach ($this->items as $key => $value) {
            $results[$key] = is_callable($callback)
                ? $callback($value, $key)
                : $this->dataGet($value, $callback);
        }

        $descending ? arsort($results, $options) : asort($results, $options);

        $items = [];
        foreach (array_keys($results) as $key) {
            $items[$key] = $this->items[$key];
        }

        // @phpstan-ignore-next-line new.static (Required for fluent interface)
        return new static($items);
    }

    /**
     * Group items by a key or callback
     *
     * @param callable(TValue, TKey): array-key|string $groupBy Key or callback
     * @return static<array-key, static<TKey, TValue>>
     */
    public function groupBy(callable|string $groupBy): static
    {
        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKey = is_callable($groupBy)
                ? $groupBy($value, $key)
                : $this->dataGet($value, $groupBy);

            if (! isset($results[$groupKey])) {
                // @phpstan-ignore-next-line new.static (Required for fluent interface)
                $results[$groupKey] = new static();
            }

            $results[$groupKey]->items[$key] = $value;
        }

        // @phpstan-ignore-next-line new.static, return.type (Required for fluent interface)
        return new static($results);
    }

    /**
     * Get the values of a given key
     *
     * @param string $value Key to pluck
     * @param string|null $key Optional key for result
     * @return static<array-key, mixed>
     */
    public function pluck(string $value, ?string $key = null): static
    {
        $results = [];

        foreach ($this->items as $item) {
            $itemValue = $this->dataGet($item, $value);

            if ($key === null) {
                $results[] = $itemValue;
            } else {
                $itemKey = $this->dataGet($item, $key);
                $results[$itemKey] = $itemValue;
            }
        }

        // @phpstan-ignore-next-line new.static (Required for fluent interface)
        return new static($results);
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    /**
     * Count the items
     *
     * @return int Item count
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Check if collection is empty
     *
     * @return bool True if empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if collection is not empty
     *
     * @return bool True if not empty
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Get all items as array
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Get all items as JSON
     *
     * @param int $options JSON encode options
     * @return string JSON string
     */
    public function toJson(int $options = 0): string
    {
        $json = json_encode($this->jsonSerialize(), $options);

        return $json !== false ? $json : '[]';
    }

    /**
     * Get the values (reset keys)
     *
     * @return static<int, TValue>
     */
    public function values(): static
    {
        // @phpstan-ignore-next-line new.static (Required for fluent interface)
        return new static(array_values($this->items));
    }

    /**
     * Get the keys
     *
     * @return static<int, TKey>
     */
    public function keys(): static
    {
        // @phpstan-ignore-next-line new.static (Required for fluent interface)
        return new static(array_keys($this->items));
    }

    /**
     * Take the first n items
     *
     * @param int $limit Number of items to take
     * @return static<TKey, TValue>
     */
    public function take(int $limit): static
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Slice the collection
     *
     * @param int $offset Start offset
     * @param int|null $length Length to slice
     * @return static<TKey, TValue>
     */
    public function slice(int $offset, ?int $length = null): static
    {
        // @phpstan-ignore-next-line new.static (Required for fluent interface)
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Concatenate values
     *
     * @param string $glue Glue string
     * @return string Concatenated string
     */
    public function implode(string $glue = ''): string
    {
        return implode($glue, $this->items);
    }

    /**
     * Check if an item exists by key
     *
     * @param TKey $key Key to check
     * @return bool True if exists
     */
    public function has(mixed $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Sum of values
     *
     * @param callable(TValue): (int|float)|string|null $callback Key or callback
     * @return int|float Sum
     */
    public function sum(callable|string|null $callback = null): int|float
    {
        if ($callback === null) {
            return array_sum($this->items);
        }

        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + (is_callable($callback) ? $callback($item) : $this->dataGet($item, $callback));
        }, 0);
    }

    /**
     * Average of values
     *
     * @param callable(TValue): (int|float)|string|null $callback Key or callback
     * @return int|float|null Average or null if empty
     */
    public function avg(callable|string|null $callback = null): int|float|null
    {
        if ($count = $this->count()) {
            return (float) ($this->sum($callback) / $count);
        }

        return null;
    }

    /**
     * Merge with another collection or array
     *
     * @param iterable<TKey, TValue> $items Items to merge
     * @return static<TKey, TValue>
     */
    public function merge(iterable $items): static
    {
        // @phpstan-ignore-next-line new.static (Required for fluent interface)
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    // ========================================
    // INTERFACE IMPLEMENTATIONS
    // ========================================

    /**
     * Get an iterator for items
     *
     * @return Iterator<TKey, TValue>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * {@inheritdoc}
     *
     * @return array<TKey, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Results array of items from collection or array
     *
     * @param iterable<TKey, TValue> $items Items to convert
     * @return array<TKey, TValue>
     */
    protected function getArrayableItems(iterable $items): array
    {
        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof self) {
            return $items->toArray();
        }

        return iterator_to_array($items);
    }

    /**
     * Get an item from an array or object using "dot" notation
     *
     * @param mixed $target Target array or object
     * @param string $key Key to retrieve
     * @return mixed Retrieved value
     */
    protected function dataGet(mixed $target, string $key): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (is_array($target)) {
                if (! array_key_exists($segment, $target)) {
                    return null;
                }

                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (! isset($target->{$segment})) {
                    return null;
                }

                $target = $target->{$segment};
            } else {
                return null;
            }
        }

        return $target;
    }
}
