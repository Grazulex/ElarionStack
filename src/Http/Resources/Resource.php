<?php

declare(strict_types=1);

namespace Elarion\Http\Resources;

use Elarion\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base Resource Class
 *
 * Provides transformation layer for converting models/data to API responses.
 * Following Transformer/Presenter pattern for clean API output.
 */
abstract class Resource
{
    /**
     * Additional data to merge with resource
     *
     * @var array<string, mixed>
     */
    protected array $with = [];

    /**
     * Additional top-level data
     *
     * @var array<string, mixed>
     */
    protected array $additional = [];

    /**
     * Create new resource instance
     *
     * @param mixed $resource Underlying resource data
     */
    public function __construct(protected mixed $resource)
    {
    }

    /**
     * Transform resource to array
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, mixed> Transformed data
     */
    abstract public function toArray(ServerRequestInterface $request): array;

    /**
     * Create new resource instance (factory)
     *
     * @param mixed $resource Resource data
     * @return static Resource instance
     */
    public static function make(mixed $resource): static
    {
        // @phpstan-ignore-next-line new.static (Required for factory pattern)
        return new static($resource);
    }

    /**
     * Create resource collection
     *
     * @param iterable<mixed> $resources Resources to transform
     * @return ResourceCollection Collection instance
     */
    public static function collection(iterable $resources): ResourceCollection
    {
        return new ResourceCollection($resources, static::class);
    }

    /**
     * Convert resource to HTTP Response
     *
     * @param ServerRequestInterface $request Request instance
     * @param int $status HTTP status code
     * @return Response HTTP Response
     */
    public function toResponse(ServerRequestInterface $request, int $status = 200): Response
    {
        $data = $this->resolve($request);

        return Response::json($data, $status);
    }

    /**
     * Resolve resource data
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, mixed> Resolved data
     */
    public function resolve(ServerRequestInterface $request): array
    {
        $data = $this->toArray($request);

        // Filter out MissingValue instances
        $data = $this->filter($data);

        // Merge with() data
        $withData = $this->with($request);
        if (! empty($withData)) {
            $data = array_merge($data, $this->filter($withData));
        }

        // Add additional top-level data
        if (! empty($this->additional)) {
            $data = array_merge($this->additional, ['data' => $data]);
        }

        return $data;
    }

    /**
     * Filter MissingValue instances from array
     *
     * @param array<string, mixed> $data Data to filter
     * @return array<string, mixed> Filtered data
     */
    protected function filter(array $data): array
    {
        return array_filter($data, fn ($value) => ! ($value instanceof MissingValue));
    }

    /**
     * Get additional data to merge with resource
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, mixed> Additional data
     */
    protected function with(ServerRequestInterface $request): array
    {
        return $this->with;
    }

    /**
     * Add additional top-level data
     *
     * @param array<string, mixed> $data Additional data
     * @return $this Fluent interface
     */
    public function additional(array $data): self
    {
        $this->additional = $data;

        return $this;
    }

    /**
     * Merge value when condition is true
     *
     * @param bool $condition Condition to check
     * @param mixed $value Value to include if true
     * @param mixed $default Value to include if false (default: MissingValue)
     * @return mixed Value or default or MissingValue
     */
    protected function when(bool $condition, mixed $value, mixed $default = null): mixed
    {
        $default = $default ?? new MissingValue();

        if ($condition) {
            return $value instanceof \Closure ? $value() : $value;
        }

        return $default instanceof \Closure ? $default() : $default;
    }

    /**
     * Merge data when condition is true
     *
     * @param bool $condition Condition to check
     * @param array<string, mixed> $data Data to merge
     * @return array<string, mixed> Merged data or empty array
     */
    protected function mergeWhen(bool $condition, array $data): array
    {
        return $condition ? $data : [];
    }

    /**
     * Get value from underlying resource
     *
     * @param string $key Property name
     * @return mixed Property value
     */
    public function __get(string $key): mixed
    {
        // Support both array and object access
        if (is_array($this->resource)) {
            return $this->resource[$key] ?? null;
        }

        if (is_object($this->resource)) {
            return $this->resource->$key ?? null;
        }

        return null;
    }

    /**
     * Check if property exists on underlying resource
     *
     * @param string $key Property name
     * @return bool True if exists
     */
    public function __isset(string $key): bool
    {
        if (is_array($this->resource)) {
            return isset($this->resource[$key]);
        }

        if (is_object($this->resource)) {
            return isset($this->resource->$key);
        }

        return false;
    }

    /**
     * Call method on underlying resource
     *
     * @param string $method Method name
     * @param array<mixed> $parameters Method parameters
     * @return mixed Method result
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (is_object($this->resource) && method_exists($this->resource, $method)) {
            return $this->resource->$method(...$parameters);
        }

        throw new \BadMethodCallException("Method {$method} does not exist on resource.");
    }
}
