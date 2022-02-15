<?php

namespace Unleash\Client\Tests\Bootstrap;

use PHPUnit\Framework\TestCase;
use Unleash\Client\Bootstrap\CompoundBootstrapProvider;
use Unleash\Client\Bootstrap\EmptyBootstrapProvider;
use Unleash\Client\Bootstrap\JsonBootstrapProvider;
use Unleash\Client\Exception\CompoundException;

final class CompoundBootstrapProviderTest extends TestCase
{
    public function testGetBootstrap()
    {
        $instance = new CompoundBootstrapProvider();
        self::assertNull($instance->getBootstrap());

        $instance = new CompoundBootstrapProvider(
            new EmptyBootstrapProvider()
        );
        self::assertNull($instance->getBootstrap());

        $instance = new CompoundBootstrapProvider(
            new EmptyBootstrapProvider(),
            new JsonBootstrapProvider('{}')
        );
        self::assertIsArray($instance->getBootstrap());

        $instance = new CompoundBootstrapProvider(
            new JsonBootstrapProvider(''), // invalid json, throws exception
            new JsonBootstrapProvider('{}') // since this is valid, the previous exception is ignored
        );
        self::assertIsArray($instance->getBootstrap());

        $instance = new CompoundBootstrapProvider(
            new JsonBootstrapProvider('')
        );
        $this->expectException(CompoundException::class);
        $instance->getBootstrap();
    }
}
