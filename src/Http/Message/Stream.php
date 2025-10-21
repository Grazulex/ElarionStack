<?php

declare(strict_types=1);

namespace Elarion\Http\Message;

use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 Stream implementation
 *
 * Wraps PHP streams with PSR-7 interface.
 * Following SRP - only handles stream operations.
 */
final class Stream implements StreamInterface
{
    /** @var resource|null */
    private $resource;

    private bool $seekable;
    private bool $readable;
    private bool $writable;

    /** @var array<string, mixed>|null */
    private ?array $meta = null;

    /**
     * Create stream from resource
     *
     * @param resource $resource PHP stream resource
     */
    public function __construct($resource)
    {
        if (! is_resource($resource)) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }

        $this->resource = $resource;
        $this->meta = stream_get_meta_data($resource);
        $this->seekable = $this->meta['seekable'] ?? false;
        $this->readable = $this->checkReadable($this->meta['mode'] ?? '');
        $this->writable = $this->checkWritable($this->meta['mode'] ?? '');
    }

    /**
     * Create stream from string
     *
     * @param string $content Stream content
     * @return self Stream instance
     */
    public static function fromString(string $content): self
    {
        $resource = fopen('php://temp', 'r+');

        if ($resource === false) {
            throw new \RuntimeException('Failed to create temp stream');
        }

        fwrite($resource, $content);
        rewind($resource);

        return new self($resource);
    }

    /**
     * Create stream from file
     *
     * @param string $filename File path
     * @param string $mode File mode (r, w, a, etc.)
     * @return self Stream instance
     */
    public static function fromFile(string $filename, string $mode = 'r'): self
    {
        $resource = fopen($filename, $mode);

        if ($resource === false) {
            throw new \RuntimeException(
                sprintf('Failed to open file: %s', $filename)
            );
        }

        return new self($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
            $this->detach();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->meta = null;
        $this->seekable = false;
        $this->readable = false;
        $this->writable = false;

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        $stats = fstat($this->resource);

        return $stats['size'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int
    {
        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached');
        }

        $position = ftell($this->resource);

        if ($position === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $position;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        return $this->resource === null || feof($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (! $this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable');
        }

        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached');
        }

        if (fseek($this->resource, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $string): int
    {
        if (! $this->isWritable()) {
            throw new \RuntimeException('Stream is not writable');
        }

        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached');
        }

        $result = fwrite($this->resource, $string);

        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $length): string
    {
        if (! $this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached');
        }

        if ($length < 1) {
            throw new \InvalidArgumentException('Length must be at least 1');
        }

        $result = fread($this->resource, $length);

        if ($result === false) {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string
    {
        if (! $this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached');
        }

        $contents = stream_get_contents($this->resource);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(?string $key = null)
    {
        if ($this->meta === null) {
            return $key === null ? [] : null;
        }

        if ($key === null) {
            return $this->meta;
        }

        return $this->meta[$key] ?? null;
    }

    /**
     * Check if mode is readable
     *
     * @param string $mode File mode
     * @return bool True if readable
     */
    private function checkReadable(string $mode): bool
    {
        return str_contains($mode, 'r') || str_contains($mode, '+');
    }

    /**
     * Check if mode is writable
     *
     * @param string $mode File mode
     * @return bool True if writable
     */
    private function checkWritable(string $mode): bool
    {
        return str_contains($mode, 'w')
            || str_contains($mode, 'a')
            || str_contains($mode, 'x')
            || str_contains($mode, 'c')
            || str_contains($mode, '+');
    }

    /**
     * Close stream on destruction
     */
    public function __destruct()
    {
        $this->close();
    }
}
