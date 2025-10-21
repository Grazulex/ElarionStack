<?php

declare(strict_types=1);

namespace Elarion\Http\Factories;

use Elarion\Http\Message\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * PSR-17 Uploaded file factory
 *
 * Creates UploadedFile instances.
 * Following Factory pattern and SRP.
 */
final class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface {
        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }
}
