<?php

declare(strict_types=1);

namespace Elarion\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;

/**
 * Base exception for all container-related errors
 *
 * PSR-11 compliant base exception
 */
class ContainerException extends \Exception implements ContainerExceptionInterface
{
    /**
     * Create an exception for when a class cannot be instantiated
     */
    public static function cannotInstantiate(string $class, string $reason): self
    {
        return new self(
            sprintf(
                'Cannot instantiate [%s]: %s',
                $class,
                $reason
            )
        );
    }

    /**
     * Create an exception for unresolvable dependencies
     */
    public static function unresolvableDependency(
        string $class,
        string $parameter,
        ?string $type = null
    ): self {
        $message = sprintf(
            'Cannot resolve dependency [%s] for class [%s]',
            $parameter,
            $class
        );

        if ($type !== null) {
            $message .= sprintf(
                '. Parameter expects type [%s] but no binding exists.',
                $type
            );
        }

        return new self($message);
    }

    /**
     * Create an exception for when auto-wiring fails
     */
    public static function autoWiringFailed(string $class, string $reason): self
    {
        return new self(
            sprintf(
                'Auto-wiring failed for [%s]: %s',
                $class,
                $reason
            )
        );
    }
}
