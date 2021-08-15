<?php

namespace Unleash\Client\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Unleash\Client\Exception\StreamException;
use Unleash\Client\Helper\StringStream;

final class StringStreamTest extends TestCase
{
    /**
     * @var StringStream
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new StringStream('hello there');
    }

    public function testSeek()
    {
        $this->instance->seek(1);
        self::assertEquals('ello there', $this->instance->getContents());
        $this->instance->seek(2);
        self::assertEquals('llo there', $this->instance->getContents());
        $this->instance->seek(30);
        self::assertEquals('', $this->instance->getContents());
    }

    public function testTell()
    {
        self::assertEquals(0, $this->instance->tell());
        $this->instance->seek(1);
        self::assertEquals(1, $this->instance->tell());
        $this->instance->seek(11);
        self::assertEquals(11, $this->instance->tell());
        $this->instance->seek(12);
        $this->expectException(StreamException::class);
        $this->instance->tell();
    }

    public function testClose()
    {
        $this->instance->close();
        $this->expectException(StreamException::class);
        $this->instance->close();
    }

    public function testRewind()
    {
        $this->instance->rewind();
        self::assertEquals(0, $this->instance->tell());

        $this->instance->seek(5);
        $this->instance->rewind();
        self::assertEquals(0, $this->instance->tell());

        $this->instance->seek(11);
        $this->instance->rewind();
        self::assertEquals(0, $this->instance->tell());

        $this->instance->seek(30);
        $this->instance->rewind();
        self::assertEquals(0, $this->instance->tell());

        self::assertEquals('hello there', $this->instance->getContents());
    }

    public function testRead()
    {
        self::assertEquals('h', $this->instance->read(1));
        self::assertEquals('e', $this->instance->read(1));
        self::assertEquals('l', $this->instance->read(1));
        self::assertEquals('l', $this->instance->read(1));
        self::assertEquals('o', $this->instance->read(1));
        self::assertEquals(' ', $this->instance->read(1));
        self::assertEquals('t', $this->instance->read(1));
        self::assertEquals('h', $this->instance->read(1));
        self::assertEquals('e', $this->instance->read(1));
        self::assertEquals('r', $this->instance->read(1));
        self::assertEquals('e', $this->instance->read(1));

        self::assertEquals('', $this->instance->read(1));
        self::assertEquals('', $this->instance->read(5));
    }

    public function testEof()
    {
        self::assertFalse($this->instance->eof());

        $this->instance->read(5);
        self::assertFalse($this->instance->eof());

        $this->instance->read(6);
        self::assertFalse($this->instance->eof());

        $this->instance->read(1);
        self::assertTrue($this->instance->eof());
    }

    public function testIsSeekable()
    {
        self::assertTrue($this->instance->isSeekable());
    }

    public function testIsWritable()
    {
        self::assertTrue($this->instance->isWritable());
    }

    public function testIsReadable()
    {
        self::assertTrue($this->instance->isReadable());
    }

    public function testGetContents()
    {
        self::assertEquals('hello there', $this->instance->getContents());
        self::assertEquals('', $this->instance->getContents());

        $this->instance->seek(10);
        self::assertEquals('e', $this->instance->getContents());

        $this->instance->seek(30);
        self::assertEquals('', $this->instance->getContents());
    }

    public function testGetMetadata()
    {
        self::assertIsArray($this->instance->getMetadata());
        self::assertArrayHasKey('stream_type', $this->instance->getMetadata());
        self::assertIsString($this->instance->getMetadata('stream_type'));
        self::assertArrayNotHasKey('hrumpf', $this->instance->getMetadata());
        self::assertNull($this->instance->getMetadata('hrumpf'));
    }

    public function testGetSize()
    {
        self::assertEquals(11, $this->instance->getSize());
    }

    public function testDetach()
    {
        $this->instance->detach();
        $this->instance->detach();

        self::assertEquals('', (string) $this->instance);

        try {
            $this->instance->close();
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->getSize();
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->tell();
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->eof();
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->isSeekable();
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->seek(1);
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->rewind();
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->isWritable();
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->write('test');
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->isReadable();
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->read(1);
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->getContents();
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->getMetadata();
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }

        try {
            $this->instance->getMetadata('stream_type');
            $this->fail('Expected an exception for detached stream');
        } catch (StreamException $e) {
        }
    }

    public function testWrite()
    {
        $this->instance->write('test');
        self::assertEquals('testo there', (string) $this->instance);

        $this->instance->seek($this->instance->getSize());
        $this->instance->write(', test append');
        self::assertEquals('testo there, test append', (string) $this->instance);
    }

    public function testToString()
    {
        self::assertEquals('hello there', (string) $this->instance);

        $this->instance->seek(3);
        self::assertEquals('hello there', (string) $this->instance);
    }
}
