<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Message;

use Elarion\Http\Message\Stream;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StreamTest extends TestCase
{
    #[Test]
    public function from_string_creates_readable_stream(): void
    {
        $stream = Stream::fromString('Hello World');

        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isSeekable());
        $this->assertSame('Hello World', $stream->getContents());
    }

    #[Test]
    public function read_returns_specified_length(): void
    {
        $stream = Stream::fromString('Hello World');

        $this->assertSame('Hello', $stream->read(5));
        $this->assertSame(' World', $stream->read(6));
    }

    #[Test]
    public function seek_and_tell_work_correctly(): void
    {
        $stream = Stream::fromString('Hello World');

        $stream->seek(6);
        $this->assertSame(6, $stream->tell());
        $this->assertSame('World', $stream->getContents());
    }

    #[Test]
    public function rewind_resets_position(): void
    {
        $stream = Stream::fromString('Hello');
        $stream->read(5);
        $stream->rewind();

        $this->assertSame(0, $stream->tell());
        $this->assertSame('Hello', $stream->getContents());
    }

    #[Test]
    public function write_adds_content_to_stream(): void
    {
        $stream = Stream::fromString('');

        $bytes = $stream->write('Hello');

        $this->assertSame(5, $bytes);
        $stream->rewind();
        $this->assertSame('Hello', $stream->getContents());
    }

    #[Test]
    public function to_string_returns_full_content(): void
    {
        $stream = Stream::fromString('Test content');
        $stream->read(4); // Move position

        $this->assertSame('Test content', (string) $stream);
    }

    #[Test]
    public function detach_removes_resource(): void
    {
        $stream = Stream::fromString('Test');
        $resource = $stream->detach();

        $this->assertIsResource($resource);
        $this->assertNull($stream->getSize());

        fclose($resource);
    }

    #[Test]
    public function eof_detects_end_of_stream(): void
    {
        $stream = Stream::fromString('Hi');
        $stream->getContents(); // Read all content

        $this->assertTrue($stream->eof());
    }
}
