<?php

namespace Rikudou\Unleash\Helper;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class StringStream implements StreamInterface
{
    /**
     * @var resource|null
     */
    private $stream;

    private int $size;

    public function __construct(
        string $content
    ) {
        $this->size = strlen($content);
        $stream = fopen('php://temp', 'w+');
        if (!is_resource($stream)) {
            throw new RuntimeException('Cannot create temporary stream for storing data');
        }
        $this->stream = $stream;
        fwrite($this->stream, $content);
        rewind($this->stream);
    }

    public function __toString()
    {
        $stream = $this->stream;
        if ($stream === null) {
            return '';
        }
        $content = stream_get_contents($stream, -1, 0);
        if (!is_string($content)) {
            $content = '';
        }

        return $content;
    }

    public function close()
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }
        fclose($this->stream);
    }

    public function detach()
    {
        $resource = $this->stream;
        $this->stream = null;

        return $resource;
    }

    public function getSize(): int
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }

        return $this->size;
    }

    public function tell(): int
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }
        $tell = ftell($this->stream);
        if ($tell === false) {
            throw new RuntimeException('Could not retrieve stream position');
        }

        return $tell;
    }

    public function eof(): bool
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }

        return feof($this->stream);
    }

    public function isSeekable(): bool
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }

        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }
        fseek($this->stream, $offset, $whence);
    }

    public function rewind(): void
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }
        rewind($this->stream);
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write($string): int
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }
        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new RuntimeException('Failed to write to the stream');
        }

        return $result;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }
        $result = fread($this->stream, $length);
        if ($result === false) {
            throw new RuntimeException('Failed to read from stream');
        }

        return $result;
    }

    public function getContents(): string
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }
        $result = stream_get_contents($this->stream);
        if ($result === false) {
            throw new RuntimeException('Failed to read from stream');
        }

        return $result;
    }

    public function getMetadata($key = null)
    {
        if ($this->stream === null) {
            throw new RuntimeException('The stream is detached');
        }
        $metadata = stream_get_meta_data($this->stream);
        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }
}
