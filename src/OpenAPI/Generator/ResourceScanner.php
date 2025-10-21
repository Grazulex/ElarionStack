<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Generator;

use Elarion\Http\Resources\Resource;
use Elarion\OpenAPI\Schema\Schema;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * Resource Scanner
 *
 * Scans API Resource classes to extract response structure and generate OpenAPI schemas.
 */
final class ResourceScanner
{
    /**
     * Scan a Resource class and generate OpenAPI schema
     *
     * @param class-string<Resource>|Resource $resource Resource class or instance
     * @param mixed $sampleData Sample data to analyze (optional)
     * @return Schema OpenAPI schema for the resource
     */
    public function scan(string|Resource $resource, mixed $sampleData = null): Schema
    {
        // Get resource class name
        $resourceClass = is_string($resource) ? $resource : $resource::class;

        // Create reflection
        $reflection = new ReflectionClass($resourceClass);

        // Analyze toArray method to extract structure
        $schema = $this->analyzeToArrayMethod($reflection, $sampleData);

        return $schema;
    }

    /**
     * Analyze toArray method to extract response structure
     *
     * @param ReflectionClass<Resource> $reflection Resource class reflection
     * @param mixed $sampleData Sample data to analyze
     * @return Schema Generated schema
     */
    private function analyzeToArrayMethod(ReflectionClass $reflection, mixed $sampleData): Schema
    {
        $properties = [];
        $required = [];

        // Try to get structure from PHPDoc
        $method = $reflection->getMethod('toArray');
        $docComment = $method->getDocComment();

        if ($docComment !== false) {
            $properties = $this->extractPropertiesFromDocComment($docComment);
        }

        // If we have sample data, analyze it
        if ($sampleData !== null) {
            $sampleProperties = $this->analyzeDataStructure($sampleData);
            $properties = array_merge($properties, $sampleProperties);
        }

        // If no properties found, create a generic object schema
        if (empty($properties)) {
            return Schema::object();
        }

        return Schema::object($properties, $required);
    }

    /**
     * Extract properties from PHPDoc comment
     *
     * @param string $docComment PHPDoc comment
     * @return array<string, Schema> Properties map
     */
    private function extractPropertiesFromDocComment(string $docComment): array
    {
        $properties = [];

        // Match @property or @return annotations with array structure
        // Example: @return array{id: int, name: string, email: string}
        if (preg_match('/@return\s+array\{([^}]+)\}/', $docComment, $matches)) {
            $arrayDef = $matches[1];
            $fields = explode(',', $arrayDef);

            foreach ($fields as $field) {
                $field = trim($field);
                if (preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)\s*:\s*(.+)/', $field, $fieldMatches)) {
                    $fieldName = $fieldMatches[1];
                    $fieldType = trim($fieldMatches[2]);

                    $properties[$fieldName] = $this->typeStringToSchema($fieldType);
                }
            }
        }

        return $properties;
    }

    /**
     * Analyze actual data structure to infer schema
     *
     * @param mixed $data Data to analyze
     * @return array<string, Schema> Properties map
     */
    private function analyzeDataStructure(mixed $data): array
    {
        $properties = [];

        if (!is_array($data)) {
            return $properties;
        }

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $properties[$key] = $this->inferSchemaFromValue($value);
        }

        return $properties;
    }

    /**
     * Infer OpenAPI schema from value type
     *
     * @param mixed $value Value to analyze
     * @return Schema Inferred schema
     */
    private function inferSchemaFromValue(mixed $value): Schema
    {
        return match (true) {
            is_int($value) => Schema::integer(),
            is_float($value) => Schema::number(),
            is_bool($value) => Schema::boolean(),
            is_array($value) => $this->inferArraySchema($value),
            is_string($value) => $this->inferStringSchema($value),
            is_null($value) => Schema::string(), // Nullable, default to string
            default => Schema::object(),
        };
    }

    /**
     * Infer schema for array value
     *
     * @param array<mixed> $value Array to analyze
     * @return Schema Inferred schema
     */
    private function inferArraySchema(array $value): Schema
    {
        if (empty($value)) {
            return Schema::array();
        }

        // Check if it's an associative array (object) or indexed array
        $isIndexed = array_keys($value) === range(0, count($value) - 1);

        if ($isIndexed) {
            // Indexed array - infer item type from first element
            $itemSchema = $this->inferSchemaFromValue($value[0]);
            return Schema::array($itemSchema);
        }

        // Associative array - treat as object
        $properties = $this->analyzeDataStructure($value);
        return Schema::object($properties);
    }

    /**
     * Infer schema for string value
     *
     * @param string $value String to analyze
     * @return Schema Inferred schema
     */
    private function inferStringSchema(string $value): Schema
    {
        // Try to detect format from value
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return Schema::string('email');
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return Schema::string('uri');
        }

        // Check for date/datetime format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Schema::string('date');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}:\d{2}/', $value)) {
            return Schema::string('date-time');
        }

        return Schema::string();
    }

    /**
     * Convert type string to OpenAPI schema
     *
     * @param string $typeString Type string from PHPDoc
     * @return Schema Corresponding schema
     */
    private function typeStringToSchema(string $typeString): Schema
    {
        // Handle nullable types
        $isNullable = str_contains($typeString, '|null') || str_contains($typeString, 'null|');
        $typeString = str_replace(['|null', 'null|'], '', $typeString);
        $typeString = trim($typeString);

        // Handle array types
        if (str_ends_with($typeString, '[]')) {
            $itemType = substr($typeString, 0, -2);
            $itemSchema = $this->typeStringToSchema($itemType);
            return Schema::array($itemSchema);
        }

        // Handle array<type> syntax
        if (preg_match('/^array<(.+)>$/', $typeString, $matches)) {
            $itemType = $matches[1];
            $itemSchema = $this->typeStringToSchema($itemType);
            return Schema::array($itemSchema);
        }

        // Basic type mapping
        return match ($typeString) {
            'int', 'integer' => Schema::integer(),
            'float', 'double', 'number' => Schema::number(),
            'bool', 'boolean' => Schema::boolean(),
            'string' => Schema::string(),
            'array' => Schema::array(),
            'object' => Schema::object(),
            default => Schema::object(), // Unknown type, default to object
        };
    }

    /**
     * Generate schema from resource instance with sample data
     *
     * @param Resource $resource Resource instance
     * @param ServerRequestInterface $request Request instance
     * @return Schema Generated schema
     */
    public function scanFromInstance(Resource $resource, ServerRequestInterface $request): Schema
    {
        // Get actual data from resource
        $data = $resource->toArray($request);

        // Analyze the structure
        return $this->scan($resource::class, $data);
    }
}
