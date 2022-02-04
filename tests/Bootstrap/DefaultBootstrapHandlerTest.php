<?php

namespace Unleash\Client\Tests\Bootstrap;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Unleash\Client\Bootstrap\DefaultBootstrapHandler;
use Unleash\Client\Bootstrap\EmptyBootstrapProvider;
use Unleash\Client\Bootstrap\JsonBootstrapProvider;
use Unleash\Client\Bootstrap\JsonSerializableBootstrapProvider;

final class DefaultBootstrapHandlerTest extends TestCase
{
    public function testGetBootstrapContents()
    {
        $instance = new DefaultBootstrapHandler();

        self::assertNull($instance->getBootstrapContents(new EmptyBootstrapProvider()));
        self::assertEquals(
            '{"features":[]}',
            $instance->getBootstrapContents(new JsonBootstrapProvider('{"features": []}'))
        );
        self::assertEquals(
            '{"features":[]}',
            $instance->getBootstrapContents(new JsonSerializableBootstrapProvider([
                'features' => [],
            ]))
        );

        self::assertEquals(
            '{"features":[]}',
            $instance->getBootstrapContents(new JsonSerializableBootstrapProvider(
                new class implements JsonSerializable {
                    public function jsonSerialize(): array
                    {
                        return [
                            'features' => [],
                        ];
                    }
                }
            ))
        );

        self::assertEquals(
            '[1,2,"string"]',
            $instance->getBootstrapContents(new JsonSerializableBootstrapProvider(
                (function () {
                    yield 1;
                    yield 2;
                    yield 'string';
                })()
            ))
        );
    }
}
