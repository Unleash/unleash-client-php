<?php

namespace Unleash\Client\Tests\DTO;

use PHPUnit\Framework\TestCase;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\DefaultVariantPayload;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Enum\VariantPayloadType;

final class DefaultVariantTest extends TestCase
{
    public function testSerializePayload()
    {
        $payload = new DefaultVariantPayload(VariantPayloadType::STRING, 'test');
        $variant = new DefaultVariant('test', true, 0, Stickiness::DEFAULT, $payload);
        $json = json_encode($variant);
        $expected = '{"name":"test","enabled":true,"payload":{"type":"string","value":"test"}}';
        self::assertEquals($expected, $json);
    }
}
