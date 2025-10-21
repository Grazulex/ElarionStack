<?php

declare(strict_types=1);

namespace Elarion\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * PSR-7 Uploaded file implementation
 *
 * Represents a file uploaded via HTTP.
 * Following SRP - handles uploaded file operations.
 */
final class UploadedFile implements UploadedFileInterface
{
    private bool $moved = false;

    /**
     * Create uploaded file
     *
     * @param StreamInterface|string $streamOrFile Stream or file path
     * @param int|null $size File size in bytes
     * @param int $error PHP upload error code
     * @param string|null $clientFilename Original filename
     * @param string|null $clientMediaType MIME type
     */
    public function __construct(
        private StreamInterface|string $streamOrFile,
        private ?int $size = null,
        private int $error = UPLOAD_ERR_OK,
        private ?string $clientFilename = null,
        private ?string $clientMediaType = null
    ) {
        if ($error !== UPLOAD_ERR_OK && ! is_string($streamOrFile)) {
            throw new \InvalidArgumentException(
                'Stream must be a file path when there is an upload error'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(): StreamInterface
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(
                sprintf('Cannot retrieve stream due to upload error: %d', $this->error)
            );
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has been moved');
        }

        if ($this->streamOrFile instanceof StreamInterface) {
            return $this->streamOrFile;
        }

        return Stream::fromFile($this->streamOrFile, 'r');
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo(string $targetPath): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(
                sprintf('Cannot move file due to upload error: %d', $this->error)
            );
        }

        if ($this->moved) {
            throw new \RuntimeException('File has already been moved');
        }

        if ($targetPath === '') {
            throw new \InvalidArgumentException('Target path cannot be empty');
        }

        // Move via Stream
        if ($this->streamOrFile instanceof StreamInterface) {
            $this->moveViaStream($targetPath);
        } else {
            $this->moveViaFilesystem($targetPath);
        }

        $this->moved = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Move file via stream operations
     *
     * @param string $targetPath Target file path
     * @return void
     */
    private function moveViaStream(string $targetPath): void
    {
        if (! $this->streamOrFile instanceof StreamInterface) {
            return;
        }

        $target = fopen($targetPath, 'w');

        if ($target === false) {
            throw new \RuntimeException(
                sprintf('Failed to open target file: %s', $targetPath)
            );
        }

        $stream = $this->getStream();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (! $stream->eof()) {
            $chunk = $stream->read(8192);

            if (fwrite($target, $chunk) === false) {
                throw new \RuntimeException('Failed to write to target file');
            }
        }

        fclose($target);
    }

    /**
     * Move file via filesystem operations
     *
     * @param string $targetPath Target file path
     * @return void
     */
    private function moveViaFilesystem(string $targetPath): void
    {
        if (! is_string($this->streamOrFile)) {
            return;
        }

        // Use move_uploaded_file for uploaded files
        if (PHP_SAPI !== 'cli' && is_uploaded_file($this->streamOrFile)) {
            if (! move_uploaded_file($this->streamOrFile, $targetPath)) {
                throw new \RuntimeException('Failed to move uploaded file');
            }

            return;
        }

        // Fallback to rename for non-uploaded files (e.g., in tests)
        if (! rename($this->streamOrFile, $targetPath)) {
            throw new \RuntimeException('Failed to move file');
        }
    }
}
