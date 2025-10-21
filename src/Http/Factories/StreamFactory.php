<?php

declare(strict_types=1);

namespace Elarion\Http\Factories;

use Elarion\Http\Message\Stream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-17 Stream factory
 *
 * Creates Stream instances.
 * Following Factory pattern and SRP.
 */
final class StreamFactory implements StreamFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::fromString($content);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return Stream::fromFile($filename, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
}
