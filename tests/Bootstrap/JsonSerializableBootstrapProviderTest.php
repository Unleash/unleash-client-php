<?php

namespace Unleash\Client\Tests\Bootstrap;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Traversable;
use Unleash\Client\Bootstrap\JsonSerializableBootstrapProvider;

final class JsonSerializableBootstrapProviderTest extends TestCase
{
    public function testGetBootstrap()
    {
        $instance = new JsonSerializableBootstrapProvider(['features' => []]);
        self::assertIsArray($instance->getBootstrap());
        self::assertArrayHasKey('features', $instance->getBootstrap());
        self::assertIsArray($instance->getBootstrap()['features']);

        $instance = new JsonSerializableBootstrapProvider(new class implements JsonSerializable {
            public function jsonSerialize(): array
            {
                return [];
            }
        });
        self::assertInstanceOf(JsonSerializable::class, $instance->getBootstrap());

        $instance = new JsonSerializableBootstrapProvider((function () {
            yield 1;
        })());
        self::assertInstanceOf(Traversable::class, $instance->getBootstrap());
    }
}
