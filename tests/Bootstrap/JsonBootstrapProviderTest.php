<?php

namespace Unleash\Client\Tests\Bootstrap;

use JsonException;
use PHPUnit\Framework\TestCase;
use Unleash\Client\Bootstrap\JsonBootstrapProvider;
use Unleash\Client\Exception\InvalidValueException;

final class JsonBootstrapProviderTest extends TestCase
{
    public function testGetBootstrap()
    {
        $instance = new JsonBootstrapProvider('{"features": []}');
        self::assertIsArray($instance->getBootstrap());
        self::assertArrayHasKey('features', $instance->getBootstrap());
        self::assertIsArray($instance->getBootstrap()['features']);
    }

    public function testGetBootstrapInvalidJson()
    {
        $instance = new JsonBootstrapProvider('');
        $this->expectException(JsonException::class);
        $instance->getBootstrap();
    }

    public function testGetBootstrapNonObject()
    {
        $instance = new JsonBootstrapProvider('"string"');
        $this->expectException(InvalidValueException::class);
        $instance->getBootstrap();
    }
}
