<?php

declare(strict_types=1);

namespace Elarion\Http\Resources\JsonApi;

use Elarion\Http\Message\Response;

/**
 * JSON:API Error Response
 *
 * Formats errors according to JSON:API specification.
 * @see https://jsonapi.org/format/#errors
 */
class JsonApiErrorResponse
{
    /**
     * JSON:API version
     *
     * @var string
     */
    protected string $jsonApiVersion = '1.1';

    /**
     * Error objects
     *
     * @var array<array<string, mixed>>
     */
    protected array $errors = [];

    /**
     * Additional meta data
     *
     * @var array<string, mixed>
     */
    protected array $meta = [];

    /**
     * Create a new error response
     *
     * @param array<array<string, mixed>> $errors Error objects
     * @param array<string, mixed> $meta Additional meta data
     */
    public function __construct(array $errors = [], array $meta = [])
    {
        $this->errors = $errors;
        $this->meta = $meta;
    }

    /**
     * Add an error
     *
     * @param string $status HTTP status code as string
     * @param string $title Human-readable error title
     * @param string|null $detail Human-readable error detail
     * @param string|null $code Application-specific error code
     * @param array<string, mixed>|null $source Error source (pointer or parameter)
     * @param array<string, mixed> $meta Additional meta data
     * @return $this
     */
    public function addError(
        string $status,
        string $title,
        ?string $detail = null,
        ?string $code = null,
        ?array $source = null,
        array $meta = []
    ): self {
        $error = [
            'status' => $status,
            'title' => $title,
        ];

        if ($code !== null) {
            $error['code'] = $code;
        }

        if ($detail !== null) {
            $error['detail'] = $detail;
        }

        if ($source !== null) {
            $error['source'] = $source;
        }

        if (! empty($meta)) {
            $error['meta'] = $meta;
        }

        $this->errors[] = $error;

        return $this;
    }

    /**
     * Add multiple errors
     *
     * @param array<array<string, mixed>> $errors Error objects
     * @return $this
     */
    public function addErrors(array $errors): self
    {
        foreach ($errors as $error) {
            $this->errors[] = $error;
        }

        return $this;
    }

    /**
     * Set meta data
     *
     * @param array<string, mixed> $meta Meta data
     * @return $this
     */
    public function withMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed> JSON:API error document
     */
    public function toArray(): array
    {
        $document = [
            'errors' => $this->errors,
            'jsonapi' => [
                'version' => $this->jsonApiVersion,
            ],
        ];

        if (! empty($this->meta)) {
            $document['meta'] = $this->meta;
        }

        return $document;
    }

    /**
     * Convert to HTTP Response
     *
     * @param int|null $status HTTP status code (uses first error's status if null)
     * @return Response HTTP Response
     */
    public function toResponse(?int $status = null): Response
    {
        // Use first error's status if not provided
        if ($status === null && ! empty($this->errors)) {
            $status = (int) ($this->errors[0]['status'] ?? 500);
        }

        $status = $status ?? 500;

        return Response::json($this->toArray(), $status, [
            'Content-Type' => 'application/vnd.api+json',
        ]);
    }

    /**
     * Create a validation error response
     *
     * @param array<string, array<string>> $errors Validation errors by field
     * @return self Error response
     */
    public static function validationErrors(array $errors): self
    {
        $response = new self();

        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $response->addError(
                    status: '422',
                    title: 'Validation Error',
                    detail: $message,
                    source: ['pointer' => "/data/attributes/{$field}"]
                );
            }
        }

        return $response;
    }

    /**
     * Create a not found error response
     *
     * @param string $resourceType Resource type
     * @param string|int $resourceId Resource ID
     * @return self Error response
     */
    public static function notFound(string $resourceType, string|int $resourceId): self
    {
        return (new self())->addError(
            status: '404',
            title: 'Resource Not Found',
            detail: "The {$resourceType} resource with ID {$resourceId} was not found."
        );
    }

    /**
     * Create an unauthorized error response
     *
     * @param string|null $detail Error detail
     * @return self Error response
     */
    public static function unauthorized(?string $detail = null): self
    {
        return (new self())->addError(
            status: '401',
            title: 'Unauthorized',
            detail: $detail ?? 'Authentication is required to access this resource.'
        );
    }

    /**
     * Create a forbidden error response
     *
     * @param string|null $detail Error detail
     * @return self Error response
     */
    public static function forbidden(?string $detail = null): self
    {
        return (new self())->addError(
            status: '403',
            title: 'Forbidden',
            detail: $detail ?? 'You do not have permission to access this resource.'
        );
    }

    /**
     * Create a server error response
     *
     * @param string|null $detail Error detail
     * @return self Error response
     */
    public static function serverError(?string $detail = null): self
    {
        return (new self())->addError(
            status: '500',
            title: 'Internal Server Error',
            detail: $detail ?? 'An unexpected error occurred.'
        );
    }
}
