<?php

declare(strict_types=1);

namespace Elarion\Http\Factories;

use Elarion\Http\Message\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-17 Response factory
 *
 * Creates Response instances.
 * Following Factory pattern and SRP.
 */
final class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, [], null, '1.1', $reasonPhrase);
    }
}
