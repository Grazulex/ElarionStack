<?php

declare(strict_types=1);

namespace Elarion\Http\Resources\JsonApi;

use Elarion\Http\Message\Response;
use Elarion\Http\Resources\ResourceCollection;
use Psr\Http\Message\ServerRequestInterface;

/**
 * JSON:API Resource Collection
 *
 * Handles collections of resources with JSON:API formatting and pagination.
 */
class JsonApiCollection extends ResourceCollection
{
    /**
     * JSON:API version
     *
     * @var string
     */
    protected string $jsonApiVersion = '1.1';

    /**
     * Pagination links
     *
     * @var array<string, string>
     */
    protected array $paginationLinks = [];

    /**
     * Included resources
     *
     * @var array<JsonApiResource>
     */
    protected array $included = [];

    /**
     * {@inheritdoc}
     */
    public function toArray(ServerRequestInterface $request): array
    {
        $data = [];

        foreach ($this->resources as $resource) {
            /** @var JsonApiResource $resourceInstance */
            $resourceInstance = new $this->resourceClass($resource);

            $data[] = $resourceInstance->toJsonApi($request);

            // Collect included resources
            $includedResources = $resourceInstance->getIncluded();
            if (! empty($includedResources)) {
                foreach ($includedResources as $included) {
                    $this->included[] = $included;
                }
            }
        }

        return ['data' => $data];
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ServerRequestInterface $request): array
    {
        $document = $this->toArray($request);

        // Add JSON:API version
        $document['jsonapi'] = [
            'version' => $this->jsonApiVersion,
        ];

        // Add included resources
        if (! empty($this->included)) {
            $document['included'] = $this->resolveIncluded($request);
        }

        // Add pagination links
        if (! empty($this->paginationLinks)) {
            $document['links'] = $this->paginationLinks;
        }

        // Add additional data (meta, etc.)
        if (! empty($this->additional)) {
            foreach ($this->additional as $key => $value) {
                $document[$key] = $value;
            }
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function toResponse(ServerRequestInterface $request, int $status = 200): Response
    {
        $data = $this->resolve($request);

        return Response::json($data, $status, [
            'Content-Type' => 'application/vnd.api+json',
        ]);
    }

    /**
     * Add JSON:API pagination with links
     *
     * @param int $total Total number of items
     * @param int $perPage Items per page
     * @param int $currentPage Current page number
     * @param string $baseUrl Base URL for pagination links
     * @return $this
     */
    public function withJsonApiPagination(
        int $total,
        int $perPage,
        int $currentPage,
        string $baseUrl
    ): self {
        $lastPage = (int) ceil($total / $perPage);

        // Add pagination links
        $this->paginationLinks = $this->buildPaginationLinks(
            $baseUrl,
            $currentPage,
            $lastPage,
            $perPage
        );

        // Add pagination meta
        $count = is_array($this->resources) || $this->resources instanceof \Countable
            ? count($this->resources)
            : iterator_count($this->resources);

        return $this->additional([
            'meta' => [
                'pagination' => [
                    'total' => $total,
                    'count' => $count,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'total_pages' => $lastPage,
                ],
            ],
        ]);
    }

    /**
     * Build pagination links
     *
     * @param string $baseUrl Base URL
     * @param int $currentPage Current page
     * @param int $lastPage Last page
     * @param int $perPage Items per page
     * @return array<string, string> Pagination links
     */
    protected function buildPaginationLinks(
        string $baseUrl,
        int $currentPage,
        int $lastPage,
        int $perPage
    ): array {
        $links = [
            'first' => $this->buildPageUrl($baseUrl, 1, $perPage),
            'last' => $this->buildPageUrl($baseUrl, $lastPage, $perPage),
        ];

        if ($currentPage > 1) {
            $links['prev'] = $this->buildPageUrl($baseUrl, $currentPage - 1, $perPage);
        }

        if ($currentPage < $lastPage) {
            $links['next'] = $this->buildPageUrl($baseUrl, $currentPage + 1, $perPage);
        }

        return $links;
    }

    /**
     * Build a page URL
     *
     * @param string $baseUrl Base URL
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return string Page URL
     */
    protected function buildPageUrl(string $baseUrl, int $page, int $perPage): string
    {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl . $separator . http_build_query([
            'page' => ['number' => $page, 'size' => $perPage],
        ]);
    }

    /**
     * Resolve included resources (deduplication)
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<array<string, mixed>> Included resources
     */
    protected function resolveIncluded(ServerRequestInterface $request): array
    {
        $included = [];
        $seen = [];

        foreach ($this->included as $resource) {
            $key = $resource->type() . ':' . $resource->id();

            if (! isset($seen[$key])) {
                $included[] = $resource->toJsonApi($request);
                $seen[$key] = true;
            }
        }

        return $included;
    }
}
