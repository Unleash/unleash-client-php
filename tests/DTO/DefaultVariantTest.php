<?php

namespace Unleash\Client\Tests\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use PHPUnit\Framework\TestCase;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\DefaultVariantOverride;
use Unleash\Client\DTO\DefaultVariantPayload;
use Unleash\Client\DTO\Variant;
use Unleash\Client\DTO\VariantPayload;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Enum\VariantPayloadType;

final class DefaultVariantTest extends TestCase
{
    public function testSerializePayload()
    {
        $payload = new DefaultVariantPayload(VariantPayloadType::STRING, 'test');
        $variant = new DefaultVariant('test', true, 0, Stickiness::DEFAULT, $payload);
        $json = json_encode($variant);
        $expected = '{"name":"test","enabled":true,"feature_enabled":false,"payload":{"type":"string","value":"test"}}';
        self::assertEquals($expected, $json);
    }

    /**
     * @dataProvider fromVariantData
     */
    public function testFromVariant(Variant $variant, ?bool $featureEnabled, bool $expected)
    {
        $target = DefaultVariant::fromVariant($variant, $featureEnabled);

        self::assertSame($variant->getName(), $target->getName());
        self::assertSame($variant->isEnabled(), $target->isEnabled());
        self::assertSame($variant->getPayload()?->jsonSerialize(), $target->getPayload()?->jsonSerialize());
        self::assertSame($variant->getWeight(), $target->getWeight());
        self::assertSame(serialize($variant->getOverrides()), serialize($target->getOverrides()));
        self::assertSame($variant->getStickiness(), $target->getStickiness());

        self::assertSame($expected, $target->isFeatureEnabled());
    }

    private function fromVariantData(): iterable
    {
        yield [
            new DefaultVariant('test', true, 5, Stickiness::RANDOM, new DefaultVariantPayload(VariantPayloadType::STRING, 'test'), [], true),
            null,
            true,
        ];
        yield [
            new DefaultVariant('test', true, 5, Stickiness::RANDOM, new DefaultVariantPayload(VariantPayloadType::STRING, 'test'), [], false),
            null,
            false,
        ];
        yield [
            new DefaultVariant('test', true, 5, Stickiness::RANDOM, new DefaultVariantPayload(VariantPayloadType::STRING, 'test'), [], true),
            false,
            false,
        ];
        yield [
            new DefaultVariant('test', true, 5, Stickiness::RANDOM, new DefaultVariantPayload(VariantPayloadType::STRING, 'test'), [], false),
            true,
            true,
        ];

        $notFullyImplementedClass = new class implements Variant {
            public function getName(): string
            {
                return 'test';
            }

            public function isEnabled(): bool
            {
                return true;
            }

            public function getPayload(): ?VariantPayload
            {
                return new DefaultVariantPayload(VariantPayloadType::STRING, 'test');
            }

            public function getWeight(): int
            {
                return 5;
            }

            public function getOverrides(): array
            {
                return [
                    new DefaultVariantOverride('test', ['test']),
                ];
            }

            #[ExpectedValues(valuesFromClass: Stickiness::class)]
            public function getStickiness(): string
            {
                return Stickiness::SESSION_ID;
            }

            public function jsonSerialize(): array
            {
                return [];
            }
        };

        yield [
            clone $notFullyImplementedClass,
            null,
            false,
        ];
        yield [
            clone $notFullyImplementedClass,
            true,
            true,
        ];
        yield [
            clone $notFullyImplementedClass,
            false,
            false,
        ];
    }
}
