<?php

declare(strict_types=1);

namespace Elarion\Http\Resources;

use Psr\Http\Message\ServerRequestInterface;

/**
 * JSON Resource
 *
 * Concrete implementation of Resource for JSON API responses.
 * Can be used directly or extended for custom resources.
 */
class JsonResource extends Resource
{
    /**
     * Transform resource to array
     *
     * Default implementation returns all attributes.
     * Override this method in subclasses for custom transformation.
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, mixed> Transformed data
     */
    public function toArray(ServerRequestInterface $request): array
    {
        // If resource is already an array, return it
        if (is_array($this->resource)) {
            return $this->resource;
        }

        // If resource has toArray method (like Model), use it
        if (is_object($this->resource) && method_exists($this->resource, 'toArray')) {
            return $this->resource->toArray();
        }

        // If resource is an object, convert to array
        if (is_object($this->resource)) {
            return get_object_vars($this->resource);
        }

        // Scalar or null, wrap in array
        return ['value' => $this->resource];
    }
}
