<?php

namespace Rikudou\Tests\Unleash\Variant;

use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\DefaultFeature;
use Rikudou\Unleash\DTO\DefaultVariant;
use Rikudou\Unleash\Enum\Stickiness;
use Rikudou\Unleash\Stickiness\MurmurHashCalculator;
use Rikudou\Unleash\Variant\DefaultVariantHandler;

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
                new DefaultVariant('test', true),
                new DefaultVariant('test2', true),
            ]
        );

        self::assertNull($instance->selectVariant($feature, new UnleashContext()));

        $feature = new DefaultFeature(
            'test',
            true,
            [],
            [
                new DefaultVariant('test', true),
                new DefaultVariant('test2', true, 1),
            ]
        );

        self::assertEquals('test2', $instance->selectVariant($feature, new UnleashContext())->getName());

        $feature = new DefaultFeature(
            'test',
            true,
            [],
            [
                new DefaultVariant('test', true, 1, Stickiness::USER_ID),
                new DefaultVariant('test2', true, 1, Stickiness::USER_ID),
            ]
        );
        self::assertEquals('test2', $instance->selectVariant($feature, new UnleashContext('125'))->getName());
        self::assertEquals('test', $instance->selectVariant($feature, new UnleashContext('126'))->getName());
    }
}
