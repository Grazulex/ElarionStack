<?php

declare(strict_types=1);

namespace Elarion\Http\Resources;

use Elarion\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Resource Collection
 *
 * Handles transformation of multiple resources.
 * Wraps items in "data" envelope and supports pagination metadata.
 */
class ResourceCollection
{
    /**
     * Additional top-level data
     *
     * @var array<string, mixed>
     */
    protected array $additional = [];

    /**
     * Create new collection instance
     *
     * @param iterable<mixed> $resources Resources to transform
     * @param class-string $resourceClass Resource class to use
     */
    public function __construct(
        protected iterable $resources,
        protected string $resourceClass
    ) {
    }

    /**
     * Transform collection to array
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, mixed> Transformed data
     */
    public function toArray(ServerRequestInterface $request): array
    {
        $data = [];

        foreach ($this->resources as $resource) {
            /** @var Resource $resourceInstance */
            $resourceInstance = new $this->resourceClass($resource);
            $data[] = $resourceInstance->toArray($request);
        }

        return ['data' => $data];
    }

    /**
     * Convert collection to HTTP Response
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
     * Resolve collection data
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, mixed> Resolved data
     */
    public function resolve(ServerRequestInterface $request): array
    {
        $data = $this->toArray($request);

        // Add additional top-level data
        if (! empty($this->additional)) {
            $data = array_merge($data, $this->additional);
        }

        return $data;
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
     * Add pagination metadata
     *
     * @param int $total Total items
     * @param int $perPage Items per page
     * @param int $currentPage Current page
     * @param int|null $lastPage Last page (optional)
     * @return $this Fluent interface
     */
    public function withPagination(
        int $total,
        int $perPage,
        int $currentPage,
        ?int $lastPage = null
    ): self {
        $lastPage = $lastPage ?? (int) ceil($total / $perPage);

        return $this->additional([
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'from' => ($currentPage - 1) * $perPage + 1,
                'to' => min($currentPage * $perPage, $total),
            ],
        ]);
    }

    /**
     * Add simple metadata
     *
     * @param array<string, mixed> $meta Metadata
     * @return $this Fluent interface
     */
    public function withMeta(array $meta): self
    {
        return $this->additional(['meta' => $meta]);
    }
}
