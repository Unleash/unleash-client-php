<?php

namespace Unleash\Client\Tests\Bootstrap;

use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplFileInfo;
use Unleash\Client\Bootstrap\FileBootstrapProvider;
use Unleash\Client\Exception\InvalidValueException;
use Unleash\Client\Tests\TestHelpers\TemporaryFileHelper;

final class FileBootstrapProviderTest extends TestCase
{
    use TemporaryFileHelper;

    public function testGetBootstrap()
    {
        $instance = new FileBootstrapProvider(__DIR__ . '/../data/bootstrap-file.json');
        self::assertIsArray($instance->getBootstrap());

        $instance = new FileBootstrapProvider(new SplFileInfo(__DIR__ . '/../data/bootstrap-file.json'));
        self::assertIsArray($instance->getBootstrap());

        // built-in stream wrapper
        $data = sprintf(
            'data://application/json;base64,%s',
            base64_encode(file_get_contents(__DIR__ . '/../data/bootstrap-file.json'))
        );
        $instance = new FileBootstrapProvider($data);
        self::assertIsArray($instance->getBootstrap());
    }

    public function testGetBootstrapInvalidContent()
    {
        $instance = new FileBootstrapProvider(sprintf('%s/../data/bootstrap-invalid.json', __DIR__));
        $this->expectException(JsonException::class);
        $instance->getBootstrap();
    }

    public function testGetBootstrapNonObject()
    {
        $instance = new FileBootstrapProvider(sprintf('%s/../data/bootstrap-non-object.json', __DIR__));
        $this->expectException(InvalidValueException::class);
        $instance->getBootstrap();
    }

    public function testGetBootstrapNonexistentFile()
    {
        $instance = new FileBootstrapProvider('/nonexistent-path');
        $this->expectException(InvalidArgumentException::class);
        $instance->getBootstrap();
    }

    public function testGetBootstrapNonexistentFileSpl()
    {
        $instance = new FileBootstrapProvider(new SplFileInfo('/nonexistent-path'));
        $this->expectException(InvalidArgumentException::class);
        $instance->getBootstrap();
    }

    public function testInvalidContent()
    {
        $instance = new FileBootstrapProvider('data://application/json;base64,abcde');
        $this->expectException(RuntimeException::class);
        $instance->getBootstrap();
    }

    public function testUnreadableFile()
    {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            self::markTestSkipped("This test doesn't run correctly on Windows");
        }
        $file = $this->createTemporaryFile();
        chmod($file, 0222);
        $instance = new FileBootstrapProvider($file);
        $this->expectException(JsonException::class);
        $instance->getBootstrap();
    }
}
