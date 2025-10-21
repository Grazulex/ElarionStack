<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Generator;

use Elarion\OpenAPI\Attributes;
use ReflectionClass;
use ReflectionMethod;

/**
 * Attribute Scanner
 *
 * Scans PHP Attributes from controller methods to extract OpenAPI metadata.
 */
final class AttributeScanner
{
    /**
     * Scan attributes from a handler (class method)
     *
     * @param callable|array<mixed> $handler
     * @return array<string, mixed>
     */
    public function scan(callable|array $handler): array
    {
        if (! is_array($handler) || count($handler) !== 2) {
            return [];
        }

        [$class, $method] = $handler;

        if (! is_string($class) && ! is_object($class)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($class);
            $methodReflection = $reflection->getMethod($method);

            return $this->extractMetadata($methodReflection);
        } catch (\ReflectionException) {
            return [];
        }
    }

    /**
     * Extract metadata from method reflection
     *
     * @return array<string, mixed>
     */
    private function extractMetadata(ReflectionMethod $method): array
    {
        $metadata = [
            'operation' => null,
            'parameters' => [],
            'responses' => [],
            'requestBody' => null,
            'tags' => [],
        ];

        $attributes = $method->getAttributes();

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();

            match (true) {
                $instance instanceof Attributes\Get,
                $instance instanceof Attributes\Post,
                $instance instanceof Attributes\Put,
                $instance instanceof Attributes\Patch,
                $instance instanceof Attributes\Delete => $metadata['operation'] = $instance,
                $instance instanceof Attributes\PathParameter,
                $instance instanceof Attributes\QueryParameter => $metadata['parameters'][] = $instance,
                $instance instanceof Attributes\ResponseAttribute => $metadata['responses'][] = $instance,
                $instance instanceof Attributes\RequestBodyAttribute => $metadata['requestBody'] = $instance,
                $instance instanceof Attributes\Tag => $metadata['tags'][] = $instance,
                default => null,
            };
        }

        return $metadata;
    }
}
