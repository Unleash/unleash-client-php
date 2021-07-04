<?php

namespace Rikudou\Tests\Unleash\DTO;

use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\DTO\DefaultVariantPayload;
use Rikudou\Unleash\Enum\VariantPayloadType;

final class DefaultVariantPayloadTest extends TestCase
{
    public function testGetValue()
    {
        $instance = new DefaultVariantPayload(VariantPayloadType::STRING, 'test');
        self::assertIsString($instance->getValue());

        $instance = new DefaultVariantPayload(VariantPayloadType::JSON, '{"test": "test"}');
        self::assertIsArray($instance->getValue());

        $instance = new DefaultVariantPayload(VariantPayloadType::CSV, "Name,Value\nTest,Test");
        self::assertIsString($instance->getValue());
    }
}
