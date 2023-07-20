<?php

namespace Unleash\Client\Tests\Variant;

use PHPUnit\Framework\TestCase;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultInternalVariant;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Variant\DefaultVariantHandler;

final class DefaultVariantHandlerTest extends TestCase
{
    public function testGetDefaultVariant()
    {
        $instance = new DefaultVariantHandler(new MurmurHashCalculator());
        $variant = $instance->getDefaultVariant();
        self::assertFalse($variant->isEnabled());
        self::assertArrayNotHasKey('payload', $variant->jsonSerialize());
    }

    public function testSelectVariant()
    {
        unset($_SERVER['REMOTE_ADDR']);

        $instance = new DefaultVariantHandler(new MurmurHashCalculator());
        $feature = new DefaultFeature(
            'test',
            true,
            [],
            [
                new DefaultInternalVariant('test', true),
                new DefaultInternalVariant('test2', true),
            ]
        );

        self::assertNull($instance->selectVariant($feature, new UnleashContext()));

        $feature = new DefaultFeature(
            'test',
            true,
            [],
            [
                new DefaultInternalVariant('test', true),
                new DefaultInternalVariant('test2', true, 1),
            ]
        );

        self::assertEquals('test2', $instance->selectVariant($feature, new UnleashContext())->getName());

        $feature = new DefaultFeature(
            'test',
            true,
            [],
            [
                new DefaultInternalVariant('test', true, 1, Stickiness::USER_ID),
                new DefaultInternalVariant('test2', true, 1, Stickiness::USER_ID),
            ]
        );
        self::assertEquals('test2', $instance->selectVariant($feature, new UnleashContext('125'))->getName());
        self::assertEquals('test', $instance->selectVariant($feature, new UnleashContext('126'))->getName());
    }
}
