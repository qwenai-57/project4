<?php

declare(strict_types=1);

/**
 * Collection Class - A lightweight, efficient collection implementation
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Support;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class Collection implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var array<int, mixed>
     */
    protected array $items = [];

    /**
     * Create a new collection instance
     *
     * @param array<int, mixed> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Create a new collection from static method
     *
     * @param array<int, mixed> $items
     * @return static
     */
    public static function make(array $items = []): static
    {
        return new static($items);
    }

    /**
     * Get all items in the collection
     *
     * @return array<int, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get an item by key
     *
     * @param int|string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(int|string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * Set an item by key
     *
     * @param int|string $key
     * @param mixed $value
     * @return static
     */
    public function set(int|string $key, mixed $value): static
    {
        $this->items[$key] = $value;
        return $this;
    }

    /**
     * Check if key exists
     *
     * @param int|string $key
     * @return bool
     */
    public function has(int|string $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * Remove an item by key
     *
     * @param int|string $key
     * @return static
     */
    public function forget(int|string $key): static
    {
        unset($this->items[$key]);
        return $this;
    }

    /**
     * Get the first item
     *
     * @return mixed
     */
    public function first(): mixed
    {
        return reset($this->items);
    }

    /**
     * Get the last item
     *
     * @return mixed
     */
    public function last(): mixed
    {
        return end($this->items);
    }

    /**
     * Filter items by callback
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Map items using callback
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items, array_keys($this->items)));
    }

    /**
     * Reduce items to single value
     *
     * @template TReduceInitial
     * @template TReduceReturnType
     * @param callable(TReduceInitial|TReduceReturnType, mixed, int|string): TReduceReturnType $callback
     * @param TReduceInitial $initial
     * @return TReduceReturnType
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Sort items
     *
     * @param callable|null $callback
     * @return static
     */
    public function sort(?callable $callback = null): static
    {
        $items = $this->items;
        if ($callback) {
            uasort($items, $callback);
        } else {
            sort($items);
        }
        return new static($items);
    }

    /**
     * Get count of items
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Check if collection is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if collection is not empty
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get iterator
     *
     * @return ArrayIterator<int, mixed>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Convert to JSON
     *
     * @return array<int, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    /**
     * Convert to array
     *
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Merge with another collection or array
     *
     * @param array<int, mixed>|Collection $items
     * @return static
     */
    public function merge(array|Collection $items): static
    {
        $arrayItems = $items instanceof Collection ? $items->toArray() : $items;
        return new static(array_merge($this->items, $arrayItems));
    }

    /**
     * Get only specified keys
     *
     * @param array<int, int|string> $keys
     * @return static
     */
    public function only(array $keys): static
    {
        $result = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->get($key);
            }
        }
        return new static($result);
    }

    /**
     * Exclude specified keys
     *
     * @param array<int, int|string> $keys
     * @return static
     */
    public function except(array $keys): static
    {
        $result = $this->items;
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        return new static($result);
    }

    /**
     * Transform each item
     *
     * @param callable(mixed, int|string): mixed $callback
     * @return static
     */
    public function transform(callable $callback): static
    {
        foreach ($this->items as $key => $value) {
            $this->items[$key] = $callback($value, $key);
        }
        return $this;
    }

    /**
     * Chunk into smaller collections
     *
     * @param int $size
     * @return static<static>
     */
    public function chunk(int $size): static
    {
        $chunks = array_chunk($this->items, $size, true);
        return new static(array_map(fn($chunk) => new static($chunk), $chunks));
    }
}
