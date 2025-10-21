<?php

declare(strict_types=1);

namespace Elarion\Http\Factories;

use Elarion\Http\Message\Uri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-17 URI factory
 *
 * Creates URI instances.
 * Following Factory pattern and SRP.
 */
final class UriFactory implements UriFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createUri(string $uri = ''): UriInterface
    {
        if ($uri === '') {
            return new Uri();
        }

        return Uri::fromString($uri);
    }
}
