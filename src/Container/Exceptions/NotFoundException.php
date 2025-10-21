<?php

declare(strict_types=1);

namespace Elarion\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when a binding or entry is not found in the container
 *
 * PSR-11 compliant exception for missing entries
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    /**
     * Create a new not found exception for a missing binding
     */
    public static function forAbstract(string $abstract): self
    {
        return new self(
            sprintf(
                'No binding found for [%s]. Did you forget to bind it in the container?',
                $abstract
            )
        );
    }

    /**
     * Create a not found exception with a suggestion
     */
    public static function withSuggestion(string $abstract, string $suggestion): self
    {
        return new self(
            sprintf(
                'No binding found for [%s]. Did you mean [%s]?',
                $abstract,
                $suggestion
            )
        );
    }
}
